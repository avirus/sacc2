#include <string.h>
#include "aggregator.h"

using namespace traffic_aggregator;

SrcDst::SrcDst(ip_t ip_s, ip_t ip_d, port_t port_s, port_t port_d, protocol_t protocol) 
	: ip_s(ip_s), ip_d(ip_d), port_s(port_s), port_d(port_d), protocol(protocol) {}

bool SrcDst::operator< (SrcDst const & rhs) const {
	return ip_s < rhs.ip_s ||	
		(ip_s == rhs.ip_s && 
		(ip_d < rhs.ip_d || 
		(ip_d == rhs.ip_d && 
		(port_s < rhs.port_s || 
		(port_s == rhs.port_s && 
		(port_d < rhs.port_d ||
		(port_d == rhs.port_d &&
		protocol < rhs.protocol)))))));
}

bool SrcDst::operator== (SrcDst const & rhs) const {
	return ip_s == rhs.ip_s &&
		ip_d == rhs.ip_d &&
		port_s == rhs.port_s &&
		port_d == rhs.port_d &&
		protocol == rhs.protocol;
}


Aggregator::Aggregator(size_t limit) : traffic(NULL), limit(limit) {
	pthread_mutex_init(&mutex, NULL);
}

Aggregator::~Aggregator() {
	pthread_mutex_lock(&mutex);
	if (NULL != traffic)
		delete traffic;
	pthread_mutex_unlock(&mutex);
	pthread_mutex_destroy(&mutex);
}

bool Aggregator::collect(SrcDst const & src_dst, Sizes const & sizes) {
	pthread_mutex_lock(&mutex);
	if (NULL == traffic)
		traffic = new Traffic();
	Sizes const * pvl;
	if (src_dst.ip_s > src_dst.ip_d 
		|| (src_dst.ip_s == src_dst.ip_d 
		&& src_dst.port_s > src_dst.port_d)) {
		SrcDst rv_sd(src_dst.ip_d, src_dst.ip_s, 
			src_dst.port_d, src_dst.port_s, src_dst.protocol);
		Sizes rv_sizes(sizes.size_out, sizes.size_in,
			sizes.pkts_out, sizes.pkts_in);
		pvl = &(traffic->operator[](rv_sd) += rv_sizes);
	} else {
		pvl = &(traffic->operator[](src_dst) += sizes);
	}
	bool overlimit = (TO_CHECK_OVERFLOW && pvl->isOverlimit()) 
		|| traffic->size() > limit;
	pthread_mutex_unlock(&mutex);
	return overlimit;
}

bool Aggregator::collect(ip_t ip_s, ip_t ip_d, port_t port_s, port_t port_d, protocol_t protocol, Sizes const & sizes) {
	return collect(SrcDst(ip_s, ip_d, port_s, port_d, protocol), sizes);
}

Traffic * Aggregator::store() {
	pthread_mutex_lock(&mutex);
	Traffic * res = traffic;
	if (NULL == res)
		res = new Traffic();
	traffic = NULL;
	pthread_mutex_unlock(&mutex);
	return res;
}

void Aggregator::free(Traffic * traffic) {
	delete traffic;
}

Sizes::Sizes() {
	memset(this, 0, sizeof(Sizes));
}

Sizes::Sizes(size_t size_in, size_t size_out, size_t pkts_in, size_t pkts_out)
	: size_in(size_in), size_out(size_out), 
	pkts_in(pkts_in), pkts_out(pkts_out) {}

Sizes & Sizes::operator+= (Sizes const & rhs) {
	size_in += rhs.size_in;
	size_out += rhs.size_out;
	pkts_in += rhs.pkts_in;
	pkts_out += rhs.pkts_out;
	return *this;
}

bool Sizes::isOverlimit() const {
	return (size_in & SIZE_HIGH_BIT) || (size_out & SIZE_HIGH_BIT);
}

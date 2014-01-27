/********************************************************************************
#  Copyright (C) 2008 Nickolay 'SirWiz' Ganichev
# SAcc system v2
# $Author: slavik $ $Date: 2008-12-23 10:20:55 +0500 (Втр, 23 Дек 2008) $
# $Id: aggregator.h 34 2008-12-23 05:20:55Z slavik $
#
#                This file is part of SAcc system.
#                    [http://sacc.cybersec.ru]
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
******************************************************************************/
#ifndef TRAFFIC_AGGREGATOR_INCLUDED_CE54ED44_BA07_4330
#define TRAFFIC_AGGREGATOR_INCLUDED_CE54ED44_BA07_4330

#include <map>
#include "pthread.h"
#include <sys/types.h>

namespace traffic_aggregator {

	const bool	TO_CHECK_OVERFLOW = true;
//	const size_t	SIZE_HIGH_BIT = 1 << sizeof(size_t) * 8 - 1;
	const size_t    SIZE_HIGH_BIT = (~0) << 1;

	typedef unsigned int	ip_t;
	typedef unsigned short	port_t;
	typedef u_int8_t	protocol_t;
	
	struct SrcDst {
		ip_t		ip_s;
		ip_t		ip_d;
		port_t		port_s;
		port_t		port_d;
		protocol_t	protocol;

		SrcDst(ip_t ip_s, ip_t ip_d, port_t port_s, port_t port_d, protocol_t protocol);
		
		bool operator< (SrcDst const & rhs) const;
		bool operator== (SrcDst const & rhs) const;
	};

	struct Sizes {	
		size_t	size_in;
		size_t	size_out;
		size_t	pkts_in;
		size_t	pkts_out;

		Sizes();

		Sizes(size_t size_in, size_t size_out, size_t pkts_in, size_t pkts_out);

		Sizes & operator+= (Sizes const & rhs);

		bool isOverlimit() const;
	};

	typedef	std::map<SrcDst, Sizes>	Traffic;

	class Aggregator {
		size_t			limit;
		Traffic			* traffic;
		pthread_mutex_t	mutex;

		public: 
			Aggregator(size_t limit);
			~Aggregator();

			// if result is true - it's time to call store()
			bool collect(SrcDst const & src_dst, Sizes const & sizes);
			bool collect(ip_t ip_s, ip_t ip_d, port_t port_s, port_t port_d, protocol_t protocol, Sizes const & sizes);

			// allways return not NULL
			Traffic * store();
		
			static void free(Traffic * traffic);
	};

}

#endif // #ifndef TRAFFIC_AGGREGATOR_INCLUDED_CE54ED44_BA07_4330

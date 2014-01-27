/********************************************************************************
#  Copyright (C) 2008 Nickolay 'SirWiz' Ganichev
# SAcc system v2
# $Author: slavik $ $Date: 2008-12-23 10:20:55 +0500 (Втр, 23 Дек 2008) $
# $Id: aggregator-test.cpp 34 2008-12-23 05:20:55Z slavik $
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
#include <iostream>
#include <algorithm>
#include <stdlib.h>
#include <time.h>
#include "aggregator.h"



using namespace traffic_aggregator;

const SrcDst test_src_dst(5, 5, 2, 2);
size_t res_ok	= 0;
size_t res_fail = 0;
size_t size_sum = 0;

void parse_traffic(Traffic::value_type const & item);

int main() {
	clock_t t1;
	Aggregator agg(895);
	std::cout << "Started!" << std::endl;
	srand((unsigned int)time(NULL));
	size_t i = 0;
	t1=clock();
	int j=0;
	for (; i < 3000000; i++) {
		const ip_t		ip_s = rand()%10;
		const ip_t		ip_d = rand()%10;
		const port_t	port_s = rand()%3;
		const port_t	port_d = rand()%3;
		const size_t	size = rand()%500;

		const SrcDst sd1(ip_s, ip_d, port_s, port_d);

		if (sd1 == test_src_dst)
			size_sum += size;

		if (agg.collect(sd1, size)) {
			Traffic * traffic = agg.store();
#ifdef DEBUG
			std::cout << "Stored! On iterate " << i << std::endl;
			std::cout << "Size for dst port 2 should be " << size_sum << std::endl;
#endif
			std::for_each(traffic->begin(), traffic->end(), parse_traffic);
			std::cout << ".";
			j++;
			if (50==j) {std::cout << std::endl;j=0;};
			// don't forgive to free memory from map:
			agg.free(traffic);
			size_sum = 0;
		}
	}

	// ...it's good idea to call agg.store() from time to time,
	// independed on agg.collect() result...
	Traffic * traffic = agg.store();
#ifdef DEBUG
	std::cout << "Stored! After loop." << std::endl;
	std::cout << "Size for (5, 5, 2, 2) should be " << size_sum << std::endl;
#endif
	std::for_each(traffic->begin(), traffic->end(), parse_traffic);
#ifdef DEBUG
	std::cout << std::endl;
#endif
	agg.free(traffic);
	size_sum = 0;

	traffic = agg.store();
#ifdef DEBUG
	std::cout << "Stored! After no collect()." << std::endl;
	std::cout << "Size for (5, 5, 2, 2) should be " << size_sum << std::endl;
	std::cout << "It's zero and no parse_traffic() call should be. See:" << std::endl;
#endif
	std::for_each(traffic->begin(), traffic->end(), parse_traffic);
#ifdef DEBUG
	std::cout << std::endl;
#endif
	agg.free(traffic);
	double t3;
	t3 =  (((double)clock() - t1) / CLOCKS_PER_SEC);
	std::cout << std::endl;
	std::cout << "Done." << std::endl;
	std::cout << "elapsed time: " << t3 << std::endl;
	std::cout << "iterations: " << i << std::endl;
	std::cout << "Tests OK: " << res_ok << std::endl;
	std::cout << "Tests FAILED: " << res_fail << std::endl;

	return 0;
}

// called for each SrcDst in traffic.
void parse_traffic(Traffic::value_type const & item) {

	const SrcDst & src_dst = item.first;
	size_t size = item.second;

	// Just for example:
	const ip_t		ip_s = src_dst.ip_s;
	const ip_t		ip_d = src_dst.ip_d;
	const port_t	port_s = src_dst.port_s;
	const port_t	port_d = src_dst.port_d;

	if (src_dst == test_src_dst) {
#ifdef DEBUG
		std::cout << "For (5, 5, 2, 2) " << port_d << " size is " << size << std::endl;
#endif
		if (size_sum == size)
			res_ok++;
		else
			res_fail++;
	}

	// It's time to place values into DB.
}

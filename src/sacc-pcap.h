/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Р’С‚СЂ, 08 РСЋР» 2008) $
# $Id: sacc-pcap.h 16 2008-07-08 07:45:33Z slavik $
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
#ifndef SACCPCAP_H_
#define SACCPCAP_H_


// в соответствии с совместимостью надо будет заменить на uint8_t и подобные
struct my_ip 
{
	u_int8_t	ip_vhl;		/* header length, version */
#define IP_V(ip)	(((ip)->ip_vhl & 0xf0) >> 4)
#define IP_HL(ip)	((ip)->ip_vhl & 0x0f)
	u_int8_t	ip_tos;		/* type of service */
	u_int16_t	ip_len;		/* total length */
	u_int16_t	ip_id;		/* identification */
	u_int16_t	ip_off;		/* fragment offset field */
#define	IP_DF 0x4000			/* dont fragment flag */
#define	IP_MF 0x2000			/* more fragments flag */
#define	IP_OFFMASK 0x1fff		/* mask for fragmenting bits */
	u_int8_t	ip_ttl;		/* time to live */
	u_int8_t	ip_p;		/* protocol */
	u_int16_t	ip_sum;		/* checksum */
	struct	in_addr ip_src, ip_dst;	/* source and dest address */
};

struct sacc_host
{
	u_int16_t	user_id;	// SAcc system user id
	u_int16_t	rule_id;	// rule id
//	u_int8_t	tid;		// tariff id
	u_int8_t	status;		// host status
	u_int16_t	ccount;		// change count
	in_addr		hip;		// host addr
	ether_addr	hmac;		// host mac
	u_int64_t	tin;		// traffic in
	u_int64_t	tout;		// traffic out
	u_int64_t	pin;		// packets in
	u_int64_t	pout;		// packets out
	u_int64_t	sess_id;	// session id
//	u_int64_t	lin;		// limit traffic in	
//	u_int64_t	lout;		// limit traffic out	
};

struct sacc_detail
{
	u_int16_t	host_id;	// host id
	in_addr		sip;		// source ip
	in_addr		dip;		// destination ip
	u_int64_t	tin;			// traffic in
	u_int64_t	tout;			// traffic out
	u_int64_t	pin;			// packets in
	u_int64_t	pout;			// packets out
};

#endif /*SACCPCAP_H_*/


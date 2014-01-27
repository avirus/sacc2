/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Р’С‚СЂ, 08 РСЋР» 2008) $
# $Id: connection.cpp 16 2008-07-08 07:45:33Z slavik $
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
#include <time.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <sys/poll.h>
#include <sys/socket.h>
#include <string.h>
#include <strings.h>
#include <stdlib.h>
#include <stdio.h>
#include <sys/types.h>
#include <errno.h>
#include <sys/uio.h>
#include <unistd.h>
#include "connection.h"
//#include "main.h"
#define STRMAX 4096
#define _V2PC(data) (char*)(data)
#define _T(txt) (char*)(txt)

cconnection::cconnection(void)
{
	temp=_V2PC(malloc(STRMAX+1));
	//printf("connection, ");
	inbound.mode=0;
	inbound.watermark=16384;
	outbound.mode=0;
	outbound.watermark=16384;
	// создадим приёмный и передающий буфера
	if ((inbound.buffer=(char*)malloc(BUFFER_SIZE))==NULL) {
		sprintf(temp, "malloc(): %s\n", strerror(errno));
		return;
	}
	inbound.maxlen=BUFFER_SIZE-1;
	if ((outbound.buffer=(char*)malloc(BUFFER_SIZE))==NULL) {
		sprintf(temp, "malloc(): %s\n", strerror(errno));
		return;
	}
	outbound.maxlen=BUFFER_SIZE-1;
	if ((saddr = (struct sockaddr_in *) malloc(sizeof(struct sockaddr_in))) == NULL) {
		sprintf(temp, "malloc(): %s\n", strerror(errno));
		return;
	}
	memset(saddr, 0, sizeof(struct sockaddr_in));

	fd=-1;
	timeout=10000;
	inbound.length=0;
	outbound.length=0;
//	FD_ZERO(read_set);
//	FD_ZERO(write_set);
}
void cconnection::cclose(void)
	{
		if (fd>0) 
			{
			shutdown(fd, SHUT_RDWR);
			close(fd);
			};
	}
cconnection::~cconnection(void)
{
	printf("connection, ");
	free(outbound.buffer);
	free(inbound.buffer);
	free(saddr);
	free(temp);
	//FD_ZERO(&read_set);
	//FD_ZERO(&write_set);
	cclose();
}
void cconnection::cread(void)
{
	fd_timeout.tv_sec=0;
	fd_timeout.tv_usec=50;
	int selr=0;
	FD_ZERO(&read_set);
	FD_SET(fd,&read_set);// core if fd=-1
	selr=select(fd+1, &read_set, 0, 0 , &fd_timeout);
	if (selr!=0)
	{
		inbound.length=read(fd, inbound.buffer, inbound.maxlen);
	if (inbound.length==0)
	{
		        printf("(%d) recv(): %d %s\n", (int)pthread_self(),errno, strerror(errno));
			printf("(%d) connection closed %d\n",(int)pthread_self(), fd);			
			close(fd);
			inbound.length=0;
			fd=-2;
			return;
	}
//		printf("cread %d",inbound.length);
	if (errno==ECONNRESET)
		{
		        printf("(%d) recv(): %d %s\n", (int)pthread_self(),errno, strerror(errno));
			printf("(%d) connection closed %d\n",(int)pthread_self(), fd);			
			close(fd);
			inbound.length=0;
			fd=-2;
			return;
		}
}
//        printf("(%d) recv(): %d %s\n", (int)pthread_self(),errno, strerror(errno));
	//if (FD_ISSET(fd,&read_set)=1)
}
void cconnection::cwrite(void)
{
	//int writed=0;
	if (outbound.length>0) 
	{
		fd_timeout.tv_sec=0;
		fd_timeout.tv_usec=50;
		FD_ZERO(&write_set); 
		FD_SET(fd,&write_set);
		if (select(fd+1, 0, &write_set, 0 , &fd_timeout)==1)
//			while (writed<outbound.length) {
//				writed+=write(fd,(outbound.buffer+writed),(outbound.length-writed));
				write(fd,outbound.buffer,outbound.length);
				if (errno==EPIPE) 
				{
					close(fd);
					fd=-2;
					printf("cwrite. thread %d: connection closed %d\n",(int)pthread_self(), fd);
					return;
				};
//			}
		outbound.length=0;
			//outbound.length-writed;
	}
}

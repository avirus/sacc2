/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Втр, 08 Июл 2008) $
# $Id: client.cpp 16 2008-07-08 07:45:33Z slavik $
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
#include <unistd.h>
#include "connection.h"
#include "sql.h"
#include "client.h"

cclient::cclient(void)
{
uid=-1;

//logdbg("initializing client structures");
//cconnection* 

client = new cconnection();
//printf("cfd %d\n",client->fd);
//logdbg("sql connection");
//cconnection* 
//server = new cconnection();
//printf("sfd %d\n",server->fd);
//printf("constructor\n");
}

cclient::~cclient(void)
{
//printf("\ncfd %d, ",client->fd);
//printf("sfd %d ",server->fd);
//printf("executing client, ");
delete client;
//printf("server, ");
//delete server;
//printf("destructor\n");
}

void cclient::crunner(void)
	{
//struct sockaddr_in serv_sa;
		//clog log;
		//log.log("clent thread", false);
		if (client->fd==-1) 
		{
			snprintf(temp, STRMAX,"client: entering in thread with client->fd=-1");
			return;
		}; // ������� � �������������������� ��������
		//printf("thread %d entering crunner, cfd %d, sfd %d\n", (int)pthread_self(),client->fd, server->fd);
		size_t read_len=0;
		while (client->fd>0)
		{
			usleep(50000);
			//���������� �������, �������� ������.
//			printf("(%d) before cread %d\n",(int)pthread_self(),client->inbound.length);
			read_len=client->inbound.length;
			client->cread();
//			printf("(%d) after cread %d\n",(int)pthread_self(),client->inbound.length);
			if (read_len==client->inbound.length) continue; 
//				{printf("(%d) +%d\n",(int)pthread_self(), client->inbound.length);} 
				
// ���� ��������� �� POST
			snprintf(client->outbound.buffer, STRMAX, "HTTP/1.1 302 Found\nDate: Tue, 08 Aug 2006 15:34:18 GMT\nServer: SAcc/2.0.00 (Unix) mod_www/1.00\nLocation: %s\nTransfer-Encoding: chunked\nContent-Type: text/html\n", url_stat );
//			strncpy(client->outbound.buffer,"HTTP/1.1 302 Found\nDate: Tue, 08 Aug 2006 15:34:18 GMT\nServer: SAcc/2.0.00 (Unix) mod_www/1.00\nLocation: http://192.168.8.7/sacc2/user.php\nTransfer-Encoding: chunked\nContent-Type: text/html\n", 4096);
			client->outbound.length=strlen(client->outbound.buffer);
			client->cwrite();
			close(client->fd);
			client->fd=-2;
			return;
		}
		//if (server->fd>0) { close(server->fd);}
	return;
	}
	
	
void cclient::errget(char* buffer, int bsize)
{
	(void)strncpy(buffer, temp, bsize);	
	return;
}
	

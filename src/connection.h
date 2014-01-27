/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Втр, 08 Июл 2008) $
# $Id: connection.h 16 2008-07-08 07:45:33Z slavik $
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
#ifndef CONN_H_
#define CONN_H_
//#pragma once
// ����� ��������� �����������.
#define BUFFER_SIZE 200*1024
#include <pthread.h>
struct conn_data
	{
	int mode;	// ������� ������� (����)
	// 0 - ��� ���������� ����������.
	// 1 - �� watermark	
	// 2 - �� timeout
	// 3 - ����������.
	size_t watermark;	// ������ ������� ������ ��� �������
	char* buffer;		// ��������� �� ������. ���������������� ��� ������������ ����������, ������������� �� ��������.
	size_t length;	// ������ ������.
	size_t maxlen;	// ������ ������.
	//int timeout=10000;		// ������� �������
	};

class cconnection
	{
	public:
		cconnection(void);
		~cconnection(void);
		void cread(void);
		void cwrite(void);
		void cclose(void);
		int fd;	// ���������� ������
					// -1 - ���������� ���������
					// 0 - ���������� �� ����������������.	
		struct sockaddr_in *saddr;
		conn_data inbound;	// ��������� �� ���������� ������ 
		conn_data outbound; // ��������� �� ������������ ������
		int timeout;		// ������� ����������.
	private:
		fd_set	read_set;	// fdset ��� ������ �� ������.
		fd_set  write_set;	// fdset ��� ������ � �����.
		timeval fd_timeout; // ������� ��� select-�
		char* temp;
	};

#endif

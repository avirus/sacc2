/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Втр, 08 Июл 2008) $
# $Id: sql.h 16 2008-07-08 07:45:33Z slavik $
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
#ifndef SQL_H_
#define SQL_H_
#include <mysql/mysql.h>

#define STRMAX 4096

class csql
{
public:
	csql(void);
	~csql(void);
	int connect (const char* db_hostname, const char* db_username, const char* db_passwd,const char* db_db, int db_port);
	int query(const char* sql_query);
	void disconnect(void);
	//int count(void);
	int ping(void);
	void errget(char* buffer, int bsize); 
	MYSQL dblink; // ���������� �����������	
	//void db_close(void* lp_dblink)
private:
	//cconnection* server;
	bool active;
	char* temp;
};

#endif


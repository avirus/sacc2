/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Втр, 08 Июл 2008) $
# $Id: sql.cpp 16 2008-07-08 07:45:33Z slavik $
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
// ���� ���������� �����
#include "sql.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define _V2PC(data) (char*)(data)
#define _T(txt) (char*)(txt)


csql::~csql(void)
{
    if (true==active) {mysql_close(&dblink);};
    //printf("sql: free sqltemp");
    free(temp);
    //printf("sql: free sqltemp, ok");
    return;
}

csql::csql(void)
{
	temp=_V2PC(malloc(STRMAX+1));
	active=false;
	return;
}

void csql::disconnect(void)
{
	if (true==active) {mysql_close(&dblink); active=false;};
	
}


int csql::connect (const char* db_hostname, const char* db_username, const char* db_passwd, const char* db_db, int db_port)
{
        mysql_init(&dblink);
        if (&dblink!=mysql_real_connect(&dblink, db_hostname, db_username, db_passwd, db_db, db_port, NULL, 0))
        {
            snprintf(temp, STRMAX,"can't connect to server, MySQL Error - %d: %s\n",mysql_errno(&dblink), mysql_error(&dblink));
            return 0;
        }
    my_bool opt_reconnect;
    opt_reconnect = 1;  
    mysql_options(&dblink, MYSQL_OPT_RECONNECT, &opt_reconnect );        
    /* try to select DB */
    if(mysql_select_db(&dblink,db_db))
    {
        snprintf(temp, STRMAX,"Unable to select db, MySQL Error - %d: %s\n",mysql_errno(&dblink), mysql_error(&dblink));
        mysql_close(&dblink);
        active=false;
        return 0;
    }
    active=false;
    return 1;
}

int csql::query(const char* sql_query)
{
	if (mysql_query(&dblink, sql_query))
	{
        snprintf(temp, STRMAX,"MySQL Error - %d: %s\n",mysql_errno(&dblink), mysql_error(&dblink));
		return 0;		
	}
	else return 1;
}

int csql::ping(void)
{
        if (mysql_ping(&dblink))
        {
        	snprintf(temp, STRMAX,"database connection lost, %d %s",mysql_errno(&dblink), mysql_error(&dblink));
            return 0;
        }
        else return 1;
}

void csql::errget(char* buffer, int bsize)
{
	(void)strncpy(buffer, temp, bsize);	
	return;
}


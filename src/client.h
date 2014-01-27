/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Р’С‚СЂ, 08 РСЋР» 2008) $
# $Id: client.h 16 2008-07-08 07:45:33Z slavik $
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
//#pragma once
// класс обработки подключения.
// Создается и заполняется при подключении клиента, освобожюдается при закрытии подключения.
#ifndef CLIENT_H_
#define CLIENT_H_

class cclient
{
public:
	cclient(void);
	~cclient(void);
	void crunner(void); // потоковая процедура обработки клиента
	void errget(char* buffer, int bsize);
	cconnection* client;
	int uid;		// Индентификатор пользователя. Или 0 если клиент ещё не авторизован.
					// в нашей стадии -1 как индикатор что авторизация не работает.
	char* url_stat;
private:
	cconnection* server;
	//csql sql; // класс работы с субд
	char* temp;
};

#endif /*CLIENT_H_*/

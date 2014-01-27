/********************************************************************************
#  Copyright (C) 2002-2009  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2009-03-10 17:35:59 +0500 (Втр, 10 Мар 2009) $
# $Id: pcap.cpp 56 2009-03-10 12:35:59Z slavik $
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
// todo
// 1. есть некий непонятный косяк с отключением сессий
#include <pcap.h>
#include <stdio.h>
#include <stdlib.h>
#include <errno.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <sys/types.h>
#ifdef FBSD
#include <netinet/in_systm.h>
#include <netinet/ip.h>
#include <sys/sockio.h>
#include <net/if_types.h>
#endif
#include <netinet/tcp.h>
#include <netinet/udp.h>
#include <netinet/ip_icmp.h>
#include <arpa/inet.h>
#include <netinet/if_ether.h>
#include <net/ethernet.h>
#include <string.h>
#include <unistd.h>
#include <pthread.h>
#include <mysql/mysql.h>
#include "sql.h"
#include "connection.h"
#include "mylog.h"
#include "pid.h"
#include "client.h"
#include <signal.h>
#include <fcntl.h>
#include <sys/stat.h>
#include "aggregator.h"
#include <stdlib.h>
#include <iostream>
#include <algorithm>

#define HAVE_SETPROCTITLE 1
//#define DETAIL_TRAFFIC_MULT 2
//#define DEBUG_DETAIL 1
// STATONLY mean that only count, don't manipulate firewall.
//#define STATONLY 1
//#define LNX
//#define FBSD

//#include "collect.h"
// freebsd6
//#include <net/pfvar.h>
//#include <netinet/ip_fw.h>

//int db_ping(void* lp_dbdesc);
//int db_query(void* lp_dblink,const char* sql_query);
void* thr_webrun(void* client_fd);
//int db_connect (void* lp_dblink);
void exit_all();

#define _V2PC(data) (char*)(data)
#define _T(txt) (char*)(txt)

//
char* my_net=_V2PC(malloc(STRMAX+1));
char* url_stat=_V2PC(malloc(STRMAX+1));

#ifndef STATONLY
bool usefw = true;
#else
bool usefw = false;
#endif

#ifndef FBSD
#include <stropts.h>
#endif
#include <net/if.h>

#ifdef bsd_with_getifaddrs
#include <ifaddrs.h>
#include <net/if_types.h>
#endif

#ifdef LNX
 #ifndef SIOCGIFADDR
  #include <linux/sockios.h>
 #endif
#undef HAVE_SETPROCTITLE
#include <netinet/ether.h>
#endif

#ifdef SUN
 #include <sys/sockio.h>
#endif

#include "sacc-pcap.h"

#define PID_FILE "/tmp/SAcc-nat.pid"
#define DB_HOSTNAME "localhost"
#define DB_USERNAME "sacc2"
#define DB_PASSWORD "chencazpeg"
#define DB_DATABASE "sacc2"
#ifndef DEBUG
int config_nodaemon = 0;
int config_nosyslog = 0;
int loglevel = 4;
#else
int config_nodaemon = 1;
int config_nosyslog = 1;
int loglevel = 7;
#endif
// my objects
cpid PID;
clog logger;
int err_value=-1;
int ok_value=0;
int sys_logfile=0;
char *dev=_V2PC(malloc(STRMAX+1));
char *ifnet=_V2PC(malloc(STRMAX+1));
char *ifmask=_V2PC(malloc(STRMAX+1));
char *ifaddr=_V2PC(malloc(STRMAX+1));
struct in_addr if_addr, if_mask;
using namespace traffic_aggregator;
Aggregator agg(100000);
Traffic * traffic=NULL;
csql dtpsql;
pthread_mutex_t	dtpmutex;
        static char* dtqsql=_V2PC(malloc(STRMAX+1));
        static char* dttemp=_V2PC(malloc(STRMAX+1));

void parse_traffic(Traffic::value_type const & item);
/* signals processing */
#define SIG_RECONFIG 1
#define SIG_SHUTDOWN 2
#define SIG_ROTATE 3
#define SIG_RECOUNT 4
static sig_atomic_t sig_num=0;

#define MAX_HOSTS 1000
struct sacc_host hosts[MAX_HOSTS];
int hostc=0;
u_int16_t DAEMON_PORT=8899;
u_int16_t DETAIL_TRAFFIC_MULT=2;
#define PCAP_PCOUNT 10
#define BACKLOG         5
#define CLIENTS_MAX 32
#ifndef STRMAX
#define STRMAX 4096
#endif

#ifndef MAXLEN
#define MAXLEN = STRMAX
#endif

//поток ловли исходящих соединений, и редиректа (через http code 302) на интерфейс авторизации.
// сюда соединение попадает после обработки правилами firewall iptables/ipfw
// ipfw add 40004 fwd 127.0.0.1,8899 ip from 192.168.8.0/24 to not 192.168.8.0/24
void* thr_web(void*)
{
timeval fd_timeout;
struct sockaddr_in *sain;
socklen_t len;

struct sockaddr_in *satl;
int sfdtl = -1;
        //,sfdta = -1;
fd_set	accept_set;
//char* tmp=_V2PC(malloc(STRMAX+1));
//откроем слушающий сокет
    if ((satl = (struct sockaddr_in *) malloc(sizeof(struct sockaddr_in))) == NULL) {
        logcrt("malloc(): %s\n", strerror(errno));
        pthread_exit(&err_value);
		}
    memset(satl, 0, sizeof(struct sockaddr_in));
    satl->sin_family = AF_INET;
    satl->sin_port = htons(DAEMON_PORT);
    satl->sin_addr.s_addr = INADDR_ANY;

	if ((sfdtl = socket(PF_INET, SOCK_STREAM, IPPROTO_TCP)) < 0) {
        logcrt("socket(): %s\n", strerror(errno));
        pthread_exit(&err_value);
    }

    if (bind(sfdtl, (struct sockaddr *) satl, sizeof(struct sockaddr)) < 0) {
        logcrt("bind(): %s\n", strerror(errno));
        pthread_exit(&err_value);
    }
    if (listen(sfdtl, BACKLOG) < 0) {
        logcrt("listen(): %s\n", strerror(errno));
        pthread_exit(&err_value);
    }
/* This ignores the SIGPIPE signal.  This is usually a good idea, since
   the default behaviour is to terminate the application.  SIGPIPE is
   sent when you try to write to an unconnected socket.
   Check your return codes to make sure you catch this error! */
	struct sigaction sig;
	sig.sa_handler = SIG_IGN;
	sig.sa_flags = 0;
	sigemptyset(&sig.sa_mask);
	sigaction(SIGPIPE,&sig,NULL);

        if ((sain = (struct sockaddr_in *) malloc(sizeof(struct sockaddr_in))) == NULL) {
            logcrt("malloc(): %s\n", strerror(errno));
            pthread_exit(&err_value);
	}
	memset(sain, 0, sizeof(struct sockaddr_in));

    logdbg("web: Initialization complete.");
	int cli_fd=0;
	// ждем соединения от клиента
	while (1) {
		// init select structures.
		usleep(50000);
		FD_ZERO(&accept_set);
		FD_SET(sfdtl, &accept_set);
		fd_timeout.tv_sec=0;
		fd_timeout.tv_usec=50;
		if (select(sfdtl+1,&accept_set,NULL,NULL,&fd_timeout)>0) {
    logdbg("web: event.");
			len=sizeof(struct sockaddr_in);
			if ((cli_fd=accept(sfdtl, (struct sockaddr *)sain,&len))<0)
			{
				if (errno==ECONNABORTED)
				{
					logdbg("aborted connection from %s",inet_ntoa(sain->sin_addr));
					continue;
				}
				logerr("%d, cant accept connection. %s",errno,  strerror(errno));
				pthread_exit(&err_value);
			}
			logdbg("connection from %s",inet_ntoa(sain->sin_addr));
			//printf("connection accepted fd=%d\n",cli_conn->client->fd);
			pthread_t webrun;
// создадим новый поток для обработки клиента
			if (0!=pthread_create(&webrun, NULL, &thr_webrun, &cli_fd))
			{
				logerr("Error creating thread %d %s", errno,  strerror(errno));
				pthread_exit(&err_value);
			}
		}
	}
}

//поток обработки клиентского подключения, проверяем авторизован ли пользователь
// если да - то возможно его баланс закончился.
void* thr_webrun(void* client_fd)
{
	pthread_detach(pthread_self());
	logdbg("entered\n");
	cclient* this_client= new cclient();
#ifdef DEBUG
	logdbg("thread %d: created instance %d\n", (int)pthread_self(), (int)this_client);
#endif
	this_client->client->fd=*(int*)client_fd;
	this_client->url_stat=url_stat;
#ifdef DEBUG
	logdbg("thread: instance initialized\n");
#endif
	this_client->crunner();
#ifdef DEBUG
	logdbg("thread %d: returned %d\n",(int)pthread_self(), (int)this_client);
#endif
	delete this_client;
	logdbg("exit\n");
	pthread_exit(&ok_value);
}

void my_exec(char* buf, const size_t ssize, const char *fmt, ...)
{
va_list ap;
va_start(ap, fmt);
va_start(ap, fmt);
if (fmt != NULL) {
                (void)vsnprintf(buf, ssize, fmt, ap);
}
va_end(ap);
#ifdef DEBUG
logerr(buf);
#endif
system(buf);
}
// поток контроля доступа, модифицирует ipfw/ipchains
// при авторизации - выходе пользователей, или отрицательном балансе.
void* thr_access(void*)
{
	// todo, в данный момент будем открывать дергая скрипты
MYSQL_RES *result;
MYSQL_ROW row;
u_int32_t db_alive=0;
u_int32_t lastrule=0;
u_int32_t rulenum=0;
#ifdef FBSD
u_int32_t tablenum=0;
u_int32_t setnum=0;
u_int32_t tablep=0;
u_int32_t tabled=0;
#define IPFW_RULE 40000
#define IPFW_TABLE_ALLOW 100
#define IPFW_TABLE_DENY 110
#define IPFW_SET 30
// initialize ipfw2
rulenum=IPFW_RULE;
tablep=IPFW_TABLE_ALLOW;
tabled=IPFW_TABLE_DENY;
setnum=IPFW_SET;
#endif //FreeBSD
#define FWPORT 8899
csql sql;
csql sql2;
u_int32_t fwport=FWPORT;
char* temp=_V2PC(malloc(STRMAX+1));
char* qsql=_V2PC(malloc(STRMAX+1));
#ifdef DEBUG
printf("thread access started, id %u\n", (u_int)pthread_self());
#endif
// тут надо сделать вычитку данных из СУБД, чтобы админ мог менять параметры.
//FBSD
//40001        0          0 fwd 127.0.0.1,8899 ip from table(110) to not 192.168.8.0/24
//40004      209      73439 fwd 127.0.0.1,8899 ip from 192.168.8.0/24 to not 192.168.8.0/24
//LNX
//iptables -A INPUT -d ! 11.0.0.0/24 -p tcp --dport 80 -j REDIRECT --to-port 8899
//
if (true==usefw) {
#ifdef FBSD
my_exec(temp,STRMAX, "/sbin/ipfw -q delete %u",rulenum);
my_exec(temp,STRMAX, "/sbin/ipfw -q add %u set %u allow ip from table\\(%u\\) to any",rulenum, setnum, tablep);
my_exec(temp,STRMAX, "/sbin/ipfw -q add %u set %u allow ip from any to me") ;
my_exec(temp,STRMAX, "/sbin/ipfw -q add %u set %u allow ip from me to any") ;
my_exec(temp,STRMAX, "/sbin/ipfw -q add %u set %u allow ip from any to table\\(%u\\)",rulenum,setnum,tablep);
rulenum++;
my_exec(temp,STRMAX, "/sbin/ipfw -q delete %u",rulenum);
my_exec(temp,STRMAX, "/sbin/ipfw -q add %u set %u fwd 127.0.0.1,%u ip from %s:%s to not %s:%s",rulenum, setnum, fwport, ifnet,ifmask,ifnet,ifmask);
//my_exec(temp,STRMAX, "/sbin/ipfw -q add %u set %u deny ip from not %s:%s to %s:%s",rulenum,setnum,ifnet,ifmask ,ifnet,ifmask) ;
my_exec(temp,STRMAX, "/sbin/ipfw  -q table %u flush",tabled);
my_exec(temp,STRMAX, "/sbin/ipfw  -q table %u flush",tablep);
// ipfw add 65000 allow ip from any to any via rl0
my_exec(temp,STRMAX, "/sbin/ipfw -q add 65000 allow ip from any to any via %s",dev);
#endif // FBSD

// linux part begins
#ifdef LNX
// remove sacc2 chains
my_exec(temp,STRMAX, "/sbin/iptables -D POSTROUTING -t nat -j sacc_fwd;/sbin/iptables -D PREROUTING -t nat -j sacc_rdr;/sbin/iptables -F sacc_fwd -t nat;/sbin/iptables -X sacc_fwd -t nat;/sbin/iptables -N sacc_fwd -t nat");
my_exec(temp,STRMAX, "/sbin/iptables -D POSTROUTING -t nat -j sacc_fwd;/sbin/iptables -D PREROUTING -t nat -j sacc_rdr;/sbin/iptables -F sacc_rdr -t nat;/sbin/iptables -X sacc_rdr -t nat;/sbin/iptables -N sacc_rdr -t nat;/sbin/iptables -A POSTROUTING -t nat -j sacc_fwd;/sbin/iptables -A PREROUTING -t nat -j sacc_rdr;");
my_exec(temp,STRMAX, "/sbin/iptables -A sacc_rdr -t nat -d \\! %s/%s -p tcp -s %s/%s --dport 80 -j REDIRECT --to-ports %u",ifnet, ifmask, ifnet, ifmask, fwport);
my_exec(temp,STRMAX, "/sbin/iptables -A INPUT -d \\! %s/%s -p tcp -s %s/%s --dport 80 -j ACCEPT",ifnet, ifmask, ifnet, ifmask);
my_exec(temp,STRMAX, "/sbin/iptables -A INPUT  -d \\! %s/%s -p tcp -s %s/%s -j REJECT --reject-with icmp-admin-prohibited",ifnet, ifmask, ifnet, ifmask);

//-A FORWARD -i ppp0 -o eth2 -m state --state RELATED,ESTABLISHED -j ACCEPT
//-A FORWARD -i ppp0 -o eth2 -j ACCEPT
// -t nat -A POSTROUTING -o ppp0 -j MASQUERADE

#endif // linux
}; // true=usefw

while (1)
{
	if (0==db_alive)
	{
		if (1==sql.connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, 3306))
		{
			logdbg("established database connection");
			db_alive=1;
			continue;
		};
		sleep(60);
		continue;
	}
	else
	{
		sleep(10);
		//проверим не отвалилось ли соединение снова
		if (0==sql.ping())
		{
			sql.errget(temp, STRMAX);
			logcrt("sql [%s] exec error [%s], dropping this connection", qsql, temp);
                        sql.disconnect();
			db_alive=0;
			continue;
		};
//получим список сессий для которых доступ ещё не открыт, и которых нет в структуре hosts
	snprintf(qsql, STRMAX, "select id, u_id, usn, hip, tin, tout, pin, pout, hmac from natsess where id>%u order by id;",lastrule);
	if (!sql.query(qsql))
	{
		sql.errget(temp, STRMAX);
		logcrt("sql [%s] exec error [%s], dropping this connection", qsql, temp);
                sql.disconnect();
		db_alive=0;
		continue;
	};
	result = mysql_store_result(&sql.dblink);
	if (result==NULL)
	{
		logerr(_T("thr_access: can't get new nat sessions, returned NULL, skip."));
		sleep(60);
		continue;
	};

	for (unsigned int i=0; i<mysql_num_rows(result); i++)
	{
		row = mysql_fetch_row(result);
		hosts[hostc].hip.s_addr = htonl((u_int32_t)strtoll(row[3], (char **)NULL, 10));
#ifdef DEBUG
		printf("thr_access: new host added %d id %s ip %s\n",hostc, row[0], inet_ntoa(hosts[hostc].hip));
#endif
		hosts[hostc].sess_id=(u_int64_t)strtoll(row[0], (char **)NULL, 10);
		hosts[hostc].user_id=(u_int16_t)strtol(row[1], (char **)NULL, 10);
		hosts[hostc].ccount=(u_int16_t)strtol(row[2], (char **)NULL, 10);
		hosts[hostc].tout=(u_int64_t)strtoll(row[5], (char **)NULL, 10);
		hosts[hostc].pout=(u_int64_t)strtoll(row[7], (char **)NULL, 10);
		hosts[hostc].tin=(u_int64_t)strtoll(row[4], (char **)NULL, 10);
		hosts[hostc].pin=(u_int64_t)strtoll(row[6], (char **)NULL, 10);
                hosts[hostc].status=1;
		hosts[hostc].rule_id=(u_int16_t)rulenum;
		//hosts[hostc].pin=(u_int64_t)strtoll(row[6], (char **)NULL, 10);
		logdbg("thr_access: grant (%d) id %s from %s (%u)", hostc, row[0], inet_ntoa(hosts[hostc].hip), (u_int32_t)strtoll(row[3], (char **)NULL, 10));
		if (true==usefw)
		{
#ifdef FBSD
		my_exec(temp,STRMAX, "/sbin/ipfw -q table %u delete %s",tabled, inet_ntoa(hosts[hostc].hip));
		my_exec(temp,STRMAX, "/sbin/ipfw  -q table %u add %s %u",tablep, inet_ntoa(hosts[hostc].hip), (u_int32_t)hosts[hostc].sess_id);
#endif //FBSD
#ifdef LNX
// @todo в дальнейшем надо будет вынести платформозависимые вещи в процедуры, которые будут подменятся по ифдеф, а то нечитабельно совсем.
                my_exec(temp,STRMAX, "/sbin/iptables -t nat  -D sacc_fwd -o %s -s %s -d \\! %s/%s -j MASQUERADE", dev,inet_ntoa(hosts[hostc].hip),  ifnet, ifmask);
                my_exec(temp,STRMAX, "/sbin/iptables -t nat  -A sacc_fwd -o %s -s %s -d \\! %s/%s -j MASQUERADE", dev,inet_ntoa(hosts[hostc].hip),  ifnet, ifmask);
                my_exec(temp,STRMAX, "/sbin/iptables -I sacc_rdr -t nat -d \\! %s/%s -p tcp -s %s -j RETURN",ifnet, ifmask, inet_ntoa(hosts[hostc].hip));
                my_exec(temp,STRMAX, "/sbin/iptables -A INPUT  -d \\! %s/%s -p tcp -s %s -j ACCEPT",ifnet, ifmask, inet_ntoa(hosts[hostc].hip));
#endif // LNX
		}; // true==usefw
		lastrule=hosts[hostc].sess_id;
		//rulenum++;
		hostc++;
	}
	mysql_free_result(result);
//select * from natsess where closing>0;
	if (!sql.query("select id from natsess where closing>0;")) {
				sql.errget(temp, STRMAX);
                                logcrt("sql [%s] exec error [%s]", qsql, temp);
                                sql.disconnect();
				db_alive=0;
				continue;
			};
	result = mysql_store_result(&sql.dblink);
	if (result==NULL) {
				logerr(_T("can't get nat sessions for close, returned NULL, skip."));
				sleep(60);
				continue;
			};
#ifdef DEBUG
	logerr("sessions for close: %d\n", mysql_num_rows(result));
#endif
	for (unsigned int i=0; i<mysql_num_rows(result); i++)
	{
#ifdef DEBUG
		logerr("iteration %d", i);
#endif
		u_int64_t sid=0;
		int element=-1;
		row = mysql_fetch_row(result);
		sid=(u_int64_t)strtoll(row[0], (char **)NULL, 10);
		for (int ii=0; ii<hostc; ii++) {
					if (sid==hosts[ii].sess_id) {
						element=ii;
						break;
					};
				}
#ifdef DEBUG
		logerr("found host, iteration %d, element %d", i, element);
#endif
		if (-1==element) {
					logerr("cant find session in hosts[]");
					break;
				};
// first - close firewall
		if (true==usefw)
		{
#ifdef FBSD
		my_exec(temp,STRMAX, "/sbin/ipfw -q table %u delete %s",tablep, inet_ntoa(hosts[element].hip));
		my_exec(temp,STRMAX, "/sbin/ipfw -q table %u add %s %u",tabled, inet_ntoa(hosts[element].hip), (u_int32_t)hosts[element].sess_id);
#endif //FBSD
#ifdef LNX
// @todo в дальнейшем надо будет вынести платформозависимые вещи в процедуры, которые будут подменятся по ифдеф, а то нечитабельно совсем.
                my_exec(temp,STRMAX, "/sbin/iptables -t nat  -D sacc_fwd -o %s -s %s -d \\! %s/%s -j MASQUERADE", dev,inet_ntoa(hosts[element].hip),  ifnet, ifmask);
                my_exec(temp,STRMAX, "/sbin/iptables -D sacc_rdr -t nat -d \\! %s/%s -p tcp -s %s -j RETURN",ifnet, ifmask, inet_ntoa(hosts[element].hip));
                my_exec(temp,STRMAX, "/sbin/iptables -D INPUT  -d \\! %s/%s -p tcp -s %s -j ACCEPT",ifnet, ifmask, inet_ntoa(hosts[element].hip));
#endif
		}; // true==usefw
// second, update stat
                sql2.disconnect();
		if (1==sql2.connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, 3306))
		{
			logdbg("thr_access: established second database connection");
			}
		else
		{
                        sql2.disconnect();
			db_alive=0;
                        continue;
		};
		if (0<hosts[element].ccount) {
					snprintf(
							qsql,
							STRMAX,
							"select nat_session_alter(%lu, %lu, %lu, %lu, %lu);",
							(unsigned long)hosts[element].sess_id,
							(unsigned long)hosts[element].tin,
							(unsigned long)hosts[element].tout,
							(unsigned long)hosts[element].pin,
							(unsigned long)hosts[element].pout);
#ifdef DEBUG
					logdbg(qsql);
#endif
					if (!sql2.query(qsql)) {
                                            sql2.errget(temp, STRMAX);
                                            logcrt("sql [%s] exec error [%s]", qsql, temp);
                                            break;
					};
					MYSQL_RES *result2;
					result2=mysql_store_result(&sql2.dblink);
					mysql_free_result(result2);
					hosts[i].ccount=0;
		};
// step 2, detail stat dump
// @todo сброс подробных данных
                pthread_mutex_lock(&dtpmutex);
                traffic = agg.store();
                if (NULL!=traffic) {
                    // upload traffic data
                    std::for_each(traffic->begin(), traffic->end(), parse_traffic);
                    agg.free(traffic);
                    traffic=NULL;
                }
                pthread_mutex_unlock(&dtpmutex);
                pthread_mutex_destroy(&dtpmutex);
//step 4, report session closed
                hosts[element].status=0;
		snprintf(qsql, STRMAX, "CALL nat_session_stop(%lu);", (long unsigned int)sid);
#ifdef DEBUG
		logdbg(qsql);
#endif
		if (!sql2.query(qsql)) {
			sql2.errget(temp, STRMAX);
                        logcrt("sql [%s] exec error [%s]", qsql, temp);
			break;
		};
		logmsg("tclose (%d) id %s from %s (iteration %d, element %d)", hostc, row[0], inet_ntoa(hosts[element].hip),i, element);
		//sql2.~csql();
	} // cycle select id from natsess where closing>0
	mysql_free_result(result);
	// теперь надо сравнить текущую структуру и записи в таблице
	// убрать правила для сессий которых больше в таблице нет (их траффик придется видимо просто скипать)
	//todo
	}  // else (0==db_alive)
} // while
return 0;
}

// поток контроля системы.
void* thr_system(void*)
{
	//todo (some shit written there)
		//1. нужно дефрагментировать hosts
		//2. нужно удалять записи досуп которым уже закрыт
		//3 нужно сохранять историю дельт, и раз в час делать агрегаты.
	return 0;
	}

	// поток синхронизации данных хранимых во внутренних структурах с данными в субд.
void* thr_datapump(void*)
{
	//MYSQL mysql;
	MYSQL_RES *result;
	MYSQL_ROW row;
        csql dpsql;
	int db_alive=0;
        int itr=0;
	char* temp=_V2PC(malloc(STRMAX+1));
	char* qsql=_V2PC(malloc(STRMAX+1));
#ifdef DEBUG
printf("pump started, thread id %u\n", (u_int)pthread_self());
#endif
	while (1)
	{
#ifdef DEBUG
        printf("begin dp\n");
#endif
		if (0==db_alive) {
			dpsql.disconnect();
			if (1==dpsql.connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, 3306))
			{
				logcrt("established database connection, entering managed mode");
				db_alive=1;
			};
		};
		if (0==db_alive)
		{
			sleep(60);
			continue;
		}
		else {
                    sleep(10);
                };
#ifdef DEBUG
        printf("rb enter\n");
#endif
		if (0==dpsql.ping()) {
                    dpsql.errget(temp, STRMAX);
                    logcrt("database connection lost, [%s] entering standalone mode", temp);
                    dpsql.disconnect();
                    db_alive=0;
                };
		if (1==db_alive)
		for (int i=0; i<hostc; i++)
		{
#ifdef DEBUG
        printf("host enter\n");
#endif
			if (0==hosts[i].sess_id)
			{
//FUNCTION `nat_session_start`(us_id INT, hst_ip INT, hst_mac INT) RETURNS int(11)
				snprintf(qsql, STRMAX, "select nat_session_start(1, %u, 0);",hosts[i].hip.s_addr);

				logdbg(qsql);
				dpsql.query(qsql);
				result = mysql_store_result(&dpsql.dblink);
				if (result==NULL)
				{
					logcrt(_T("sync: can't start nat session, entering standalone mode."));
                                        dpsql.disconnect();
					db_alive=0;
					break;
				};
				if (mysql_num_rows(result)==0)
				{
					logcrt(_T("sync: can't start nat session, entering standalone mode."));
                                        dpsql.disconnect();
					db_alive=0;
					break;
				}
				row = mysql_fetch_row(result);
				hosts[i].sess_id=(u_int64_t)strtoll(row[0], (char **)NULL, 10);
				mysql_free_result(result);
				hosts[i].status=0;
			}
			if ((0<hosts[i].ccount)&&(1==hosts[i].status))
			{
// FUNCTION `nat_session_alter`(s_id INT, s_tin INT, s_tout INT, s_pin INT, s_pout INT) RETURNS bigint(20)
				//alter record in db
				snprintf(qsql, STRMAX,"select nat_session_alter(%lu, %lu, %lu, %lu, %lu);",(unsigned long)hosts[i].sess_id, (unsigned long)hosts[i].tin, (unsigned long)hosts[i].tout, (unsigned long)hosts[i].pin, (unsigned long)hosts[i].pout);
				logdbg(qsql);
				if (!dpsql.query(qsql)) {
                                    dpsql.errget(temp, STRMAX);
                                    logcrt("sql [%s] exec error [%s], disconnect", qsql, temp);
                                    dpsql.disconnect();
                                    db_alive=0;
                                    break;
                                };
				result = mysql_store_result(&dpsql.dblink);
				if (result==NULL)
				{
                                        logcrt("sql [%s] result NULL, disconnect", qsql);
                                        dpsql.disconnect();
					db_alive=0;
					break;
				};
				if (mysql_num_rows(result)==0)
				{
                                        logcrt("sql [%s] result row count=0, disconnect", qsql);
                                        dpsql.disconnect();
					db_alive=0;
					break;
				}
				row = mysql_fetch_row(result);
				hosts[i].status=(u_int16_t)strtol(row[0], (char **)NULL, 10);
				hosts[i].ccount=0;
				mysql_free_result(result);
			}
#ifdef DEBUG
        logdbg("host leave, %u\n", itr);
#endif
		}

		// @todo обработка детального трафа, на время разработки - каждую минуту, потом надо сделать пореже (думаю по умолчанию час самое то, а так - настройка)
                if ((itr>DETAIL_TRAFFIC_MULT)&&(NULL==traffic)) {
                    pthread_mutex_lock(&dtpmutex);
                    traffic = agg.store();
#ifdef DEBUG
        printf("rb enter\n");
#endif
                    std::for_each(traffic->begin(), traffic->end(), parse_traffic);
#ifdef DEBUG
        printf("rb done\n");
#endif
                    agg.free(traffic);
                    traffic=NULL;
#ifdef DEBUG
        printf("rb freed\n");
#endif
                    pthread_mutex_unlock(&dtpmutex);
                    pthread_mutex_destroy(&dtpmutex);
                    itr=0;
                };
                if (NULL!=traffic) {
                    pthread_mutex_lock(&dtpmutex);
                    // upload traffic data
                    std::for_each(traffic->begin(), traffic->end(), parse_traffic);
                    agg.free(traffic);
                    traffic=NULL;
                    pthread_mutex_unlock(&dtpmutex);
                    pthread_mutex_destroy(&dtpmutex);
                    itr=0;
                };
                itr++;
	}
return 0;
}
// called for each SrcDst in traffic.
void parse_traffic(Traffic::value_type const & item) {
#ifdef DEBUG
        printf("entered traffic\n");
#endif
        MYSQL_RES *result;
	const SrcDst & src_dst = item.first;
	size_t size_out = item.second.size_out;
        size_t size_in = item.second.size_in;
        size_t pkt_out = item.second.pkts_out;
        size_t pkt_in = item.second.pkts_in;
#ifdef DEBUG
        in_addr src, dst;
        src.s_addr=src_dst.ip_s;
        dst.s_addr=src_dst.ip_d;
        printf("sql for %lu to %lu (%s ->", (unsigned long)htonl(src_dst.ip_s), (unsigned long)htonl(src_dst.ip_d),  inet_ntoa(src));
        printf(" %s)\n",inet_ntoa(dst) );
#endif
	// It's time to place values into DB.
	snprintf(dtqsql, STRMAX,"select nat_detail_add(%lu, %lu, %lu, %lu, %lu, %lu, %lu, %lu, %u);\n",(unsigned long)size_in,(unsigned long)size_out,(unsigned long)pkt_in, (unsigned long)pkt_out, (unsigned long)htonl(src_dst.ip_s), (unsigned long)htonl(src_dst.ip_d), (unsigned long)src_dst.port_s, (unsigned long)src_dst.port_s, (u_int32_t)src_dst.protocol);
#ifdef DEBUG_DETAIL
printf(dtqsql);
#endif
#ifdef DEBUG
logdbg(dtqsql);
#endif
	if (!dtpsql.query(dtqsql)) {
        dtpsql.errget(dttemp, STRMAX);
        logcrt("sql [%s] exec error [%s], immediately exit", dtqsql, dttemp);
#ifdef DEBUG
        printf("unable to store detailed traffic data, nahuj vihodim\n sql [%s] ", dtqsql);
#endif
                                    exit_all();
                                };
#ifdef DEBUG
        printf("query\n");
#endif
                                result = mysql_store_result(&dtpsql.dblink);
                                mysql_free_result(result);
#ifdef DEBUG
        printf("traffic done\n");
#endif

                                //free(temp);
                                //free(qsql);
}
/*
 * callback функция обработки траффика.
 */
void my_callback(u_char *args,const struct pcap_pkthdr* pkthdr,const u_char*
        packet)
{
    struct ether_header *eptr;  /* net/ethernet.h */
    u_int len;

    /* lets start with the ether header... */
    eptr = (struct ether_header *) packet;
#ifdef DEBUGTRF
    fprintf(stdout,"ethernet header source: %s"
            ,ether_ntoa(eptr->ether_shost));
    fprintf(stdout," destination: %s \n"
            ,ether_ntoa(eptr->ether_dhost));
#endif
    const struct my_ip* ip;
    u_int length = pkthdr->len;
    u_int hlen,version;
    u_int16_t portsrc,portdst;
    /* jump pass the ethernet header */
    ip = (struct my_ip*)(packet + sizeof(struct ether_header));
    length -= sizeof(struct ether_header);

    /* check to see we have a packet of valid length */
    if (length < sizeof(struct my_ip))
    {
#ifdef DEBUG
        printf("truncated ip %d",length);
#endif
        return;
    }

    len     = ntohs(ip->ip_len);
    hlen    = IP_HL(ip); /* header length */
    version = IP_V(ip);/* ip version */

    /* check version */
    if(version != 4)
    {
      //fprintf(stdout,"Unknown version %d\n",version);
      return ;
    }
#ifdef DEBUGTRF
        fprintf(stdout,"IP: ");
        fprintf(stdout,"from %s ",
                inet_ntoa(ip->ip_src));
        fprintf(stdout,"to %s len %d (%d)\n",
                inet_ntoa(ip->ip_dst),
                len, hostc);
#endif
// надо добавить ещё один признак, session_id
    u_int hfound=0;
//47       GRE              General Routing Encapsulation            [Li]
//50       ESP              Encap Security Payload                   [RFC4303]
//51       AH               Authentication Header                    [RFC4302]
    switch (ip->ip_p) {
case IPPROTO_TCP: // TCP
    const struct tcphdr* tcp;
    tcp = (struct tcphdr*)(packet + sizeof(struct ether_header)+sizeof(struct my_ip));
#ifdef LNX
    portdst=tcp->dest;
    portsrc=tcp->source;
#endif
#ifdef FBSD
    portdst=tcp->th_dport;
    portsrc=tcp->th_sport;
#endif
    break;
case IPPROTO_UDP: // UDP
    const struct udphdr* udp;
    udp = (struct udphdr*)(packet + sizeof(struct ether_header)+sizeof(struct my_ip));
#ifdef LNX
    portdst=udp->dest;
    portsrc=udp->source;
#endif
#ifdef FBSD
    portdst=udp->uh_dport;
    portsrc=udp->uh_sport;
#endif
    break;
case IPPROTO_ICMP: // ICMP
    const struct icmphdr* icmp;
    icmp = (struct icmphdr*)(packet + sizeof(struct ether_header)+sizeof(struct my_ip));
#ifdef LNX
    portdst=icmp->type;
    portsrc=icmp->code;
#endif
#ifdef FBSD
    portdst=icmp->icmp_type;
    portsrc=icmp->icmp_code;
#endif
    break;
default: // default, unknown proto
    portdst=0;
    portsrc=0;
}; /* of switch */
    		if (agg.collect((unsigned int)(ip->ip_dst.s_addr),(unsigned int)(ip->ip_src.s_addr) ,portdst , portsrc, ip->ip_p, Sizes(len, 0,1,0))) {
                    pthread_mutex_lock(&dtpmutex);
                         traffic = agg.store();
                    pthread_mutex_unlock(&dtpmutex);
                    pthread_mutex_destroy(&dtpmutex);
                        // @todo нужно выставить флажок "немедленно обработать данные трафа", но в принципе 100к таблицы должно хватить БОЛЕЕ чем на 10 секунд
                };
	for (int i=0; i<hostc; i++)
	{
            if (0==hosts[i].status) continue;
		if (0==memcmp(&hosts[i].hip,&ip->ip_dst, sizeof(in_addr)))
		{
			hosts[i].tin=hosts[i].tin+len;
			hosts[i].pin++;
			hosts[i].ccount++;
			hfound=1;
			break;
		};
		if (0==memcmp(&hosts[i].hip,&ip->ip_src, sizeof(in_addr)))
		{
			hosts[i].tout=hosts[i].tout+len;
			hosts[i].pout++;
			hosts[i].ccount++;
			hfound=1;
			break;
		};
	}
}

void exit_all()
{
#ifdef STAT
#endif
	close(1);
	close(2);
	close(sys_logfile);
    exit(1);
}

/* fork */
void _fork(void)
{
    switch (fork()) {
case -1:
        logcrt(_T("fork() step failed, exit"));
        exit_all();
case 0:
#ifdef DEBUG
        logmsg(_T("fork() step ok"));
#endif
        return;
default:
        exit(0);
};
}

void sys_daemonize ()
{
    chdir ("/tmp");
    /* close STDIN and STDOUT */
    mode_t old_umask;
    old_umask = umask(133);
    umask(old_umask);
    _fork();
	setsid(); // set as group leader
	_fork();
	umask(0); //we need complete control over the permissions of anything we write.
	/* close STDIN and STDOUT */
        (void)close(STDIN_FILENO);
        (void)close(STDOUT_FILENO);
        (void)close(STDERR_FILENO);
	sys_logfile = creat("/tmp/sacc-pcap.log",777);
//("/tmp/sacc-pcap.log", O_CREAT|O_RDWR|O_TRUNC);
	dup2(sys_logfile, 1);
	dup2(sys_logfile, 2);
}

#define SIG_RECONFIG 1
#define SIG_SHUTDOWN 2
#define SIG_ROTATE 3
#define SIG_DUMP 4

/* shut down signal handler */
void sig_shutdown(int)
{
#ifdef DEBUG
	printf("recieved TERM signal");
#endif
	sig_num = SIG_SHUTDOWN;
}
/* signal handler recount */
void sig_dump(int)
{
#ifdef DEBUG
	printf("recieved USR2 signal");
#endif
	sig_num = SIG_DUMP;
}
/* signal handler logrotate */
void sig_rotate(int)
{
#ifdef DEBUG
	printf("recieved USR1 signal");
#endif
	sig_num = SIG_ROTATE;
}
/* reconfugure signal handler */
void sig_reconf(int)
{
#ifdef DEBUG
	printf("recieved HUP signal");
#endif
//	logmsg(_T("SIGHUP - reconfiguration begin..."));
	sig_num = SIG_RECONFIG;
}

void getip(char *p, char *device)
{
	int	fd;
	struct  sockaddr_in *sin;
	struct	ifreq ifr;
	struct	in_addr z;
	*p = 0;		// remove old address
	if ((fd = socket(AF_INET, SOCK_DGRAM, 0)) < 0) {
	    printf("Can't talk to kernel! (%d)\n", errno);
	    return;
	}
	strcpy(ifr.ifr_name, device);
	if (ioctl(fd, SIOCGIFFLAGS, &ifr) < 0)  {
	    printf("Can't get status for %s. (%d)\n", device, errno);
	    close(fd);
	    return;
	}
	if ((ifr.ifr_flags & IFF_UP) == 0)  {
	    printf("Interface %s not active.\n", device);
	    return;
	}
	if (ioctl(fd, SIOCGIFADDR, &ifr) != 0) {
	    printf("Can't get IP address for %s. (%d)\n", device, errno);
	    return;
	}
	close(fd);
	sin = (struct sockaddr_in *)&ifr.ifr_addr;
	z = sin->sin_addr;
	strncpy(p, inet_ntoa(z), STRMAX-1);
#ifdef DEBUG
	    fprintf(stderr,"detected device %s IP address is %s\n", device ,p);
#endif
}

int main(int argc,char **argv)
{
	char* temp=_V2PC(malloc(STRMAX+1));
//  ---------------------------------------------- init
	PID.init(PID_FILE);
	if (PID.running()) {printf("another copy already running");exit(1);};

#ifdef DEBUG
printf("ready to daemonize...");
#endif  /* DEBUG */
csql sql;
// init mysql connection

printf("Loading settings, connecting to db: ");
int i=0;
while (true)
{
		if (1==sql.connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, 3306))
		{
			logcrt("established database connection, entering managed mode");
			break;
		};
	sleep(10);
	printf(".");
	i++;
	if (i>24) {
		fprintf(stderr, "failed\n");
		sql.errget(temp, STRMAX);
		fprintf(stderr,"%s", temp);
		exit(2);
		};
}


printf(" connected\n");
// connect detailed traffic connection
// @todo - убрать нахуй этот воркараунд, должна быть отложенная выгрузка, т.е. на случай перезапуска СУБД
if (0==dtpsql.connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, 3306))
{
    printf("unable to open detailed traffic datapump database connection, immediate shutdown!");
    exit_all();
};
        MYSQL_RES *result;
	MYSQL_ROW row;
	sql.query("select value from sacc2.options where name='IF';");
	result = mysql_store_result(&sql.dblink);
	if ((NULL==result) || (mysql_num_rows(result)==0))
	{
		fprintf(stdout,"no interface specified in database, I will use ");
		dev = pcap_lookupdev(temp);
		if (dev == NULL)
		{
			fprintf(stderr, "ABORT: Couldn't find default device: %s\n", dev);
			exit(2);
		}
	} else	{
		row = mysql_fetch_row(result);
		strncpy(dev, row[0], STRMAX);
	}
	fprintf(stdout,"device: %s\n", dev);
    bpf_u_int32 maskp;          /* subnet mask               */
    bpf_u_int32 netp;           /* ip                        */
#ifndef STATONLY
    if(-1 == pcap_lookupnet(dev,&netp,&maskp,temp))
	{
	   printf("%s\n",temp);
	   exit(1);
	}
    if_addr.s_addr = netp;
    if_mask.s_addr = maskp;
    strncpy(ifnet, inet_ntoa(if_addr),STRMAX);
    strncpy(ifmask, inet_ntoa(if_mask),STRMAX);
    //inet_ntoa(addr);
    fprintf(stdout,"ip/mask: %s/%s\n",ifnet,ifmask);
//URL_STAT
	sql.query("select value from options where name='URL_STAT';");
	result = mysql_store_result(&sql.dblink);
	if ((NULL==result) || (mysql_num_rows(result)==0))
	{
			fprintf(stderr, "ABORT: please specify URL_STAT in database, table options\n");
			exit(2);
	} else	{
		row = mysql_fetch_row(result);
		//strncpy(url_stat, row[0], STRMAX);
                getip(ifaddr, dev);
                fprintf(stdout,"device ip addr %s\n", ifaddr);
                snprintf(url_stat, STRMAX, "http://%s%s", ifaddr, row[0]);
	}
	fprintf(stdout,"redirect unknown to: %s\n", url_stat);
#endif //STATONLY
//DAEMON_PORT
	sql.query("select value from options where name='DAEMON_PORT';");
	result = mysql_store_result(&sql.dblink);
	if ((NULL==result) || (mysql_num_rows(result)==0))
	{
		fprintf(stderr, "WARNING: no parameter DAEMON_PORT in database, table options, using default (%u)\n", DAEMON_PORT);
	} else	{
		row = mysql_fetch_row(result);
		DAEMON_PORT=atoi(row[0]);
	}
	fprintf(stdout,"using port %u for captive portal redirector\n", DAEMON_PORT);
//DETAIL_TRAFFIC_MULT
	sql.query("select value from options where name='DETAIL_TRAFFIC_MULT';");
	result = mysql_store_result(&sql.dblink);
	if ((NULL==result) || (mysql_num_rows(result)==0))
	{
		fprintf(stderr, "WARNING: no parameter DETAIL_TRAFFIC_MULT in database, table options, using default (%u)\n", DETAIL_TRAFFIC_MULT);
	} else	{
		row = mysql_fetch_row(result);
		DETAIL_TRAFFIC_MULT=atoi(row[0]);
	}
	fprintf(stdout,"using %u as multiplier (x10 seconds) for detailed traffic dump\n", DETAIL_TRAFFIC_MULT);

if (!config_nodaemon) sys_daemonize();
/* set system signals handlers */
signal(SIGHUP ,&sig_reconf);
signal(SIGTERM,&sig_shutdown);
signal(SIGUSR1,&sig_rotate);
signal(SIGUSR2,&sig_dump);
//  ---------------------------------------------- end of init
	pthread_t thrd_dp,thrd_web, thrd_access; // threads
//    char *dev;
    char errbuf[PCAP_ERRBUF_SIZE];
    pcap_t* descr;
    struct bpf_program fp;      /* hold compiled program     */
    u_char* args = NULL;
    char filter_exp[] = "ether proto ip";
	u_int pcap_count=PCAP_PCOUNT;


	logger.log("sacc-pcap", true, 7); // set 7 for debug output
	printf("main started, thread id %u \n", (u_int)pthread_self());
#ifdef DEBUG
printf("main started, thread id %u ", (u_int)pthread_self());
#endif
    if(argc < 3){
        fprintf(stdout,"no numpackets specified, assigned default=%d packets\n", PCAP_PCOUNT);

    } else {pcap_count=atoi(argv[2]); printf("counter %d\n", pcap_count);};

	if (0!=pthread_create(&thrd_dp, NULL, &thr_datapump, NULL))
	{
		logcrt("can't create datapump thread");
		exit_all();
	}
	printf("datapump started\n");
#ifndef STATONLY
	if (0!=pthread_create(&thrd_web, NULL, &thr_web, NULL))
	{
		logcrt("can't create web thread");
		exit_all();
	}
	printf("web started\n");
#endif //STATONLY
	//thr_access
	if (0!=pthread_create(&thrd_access, NULL, &thr_access, NULL))
	{
		logcrt("can't create access thread");
		exit_all();
	}
	printf("access started\n");
// todo, init aggr

    /* change my name */
    #ifdef HAVE_SETPROCTITLE
    setproctitle("-%s [%s]","SAcc traffic collector", dev );
    #endif


    /* ask pcap for the network address and mask of the device */
    pcap_lookupnet(dev,&netp,&maskp,errbuf);

    /* open device for reading. NOTE: defaulting to
     * non promiscuous mode*/
int promisc;
#ifndef STATONLY
promisc=0;
#else
promisc=1;
#endif

    descr = pcap_open_live(dev,BUFSIZ,promisc,1000,errbuf);
    if(descr == NULL)
    { printf("pcap_open_live(): %s\n",errbuf); exit(1); }
    if(argc > 3)
    {
        /* Lets try and compile the program.. non-optimized */
        if(pcap_compile(descr,&fp,filter_exp,0,netp) == -1)
        { fprintf(stderr,"Error calling pcap_compile\n"); exit(1); }

        /* set the compiled program as the filter */
        if(pcap_setfilter(descr,&fp) == -1)
        { fprintf(stderr,"Error setting filter\n"); exit(1); }
    }

    /* ... and loop */
    sql.disconnect();
    while (sig_num!=SIG_SHUTDOWN) {
    pcap_loop(descr,pcap_count,my_callback,args);
    }
#ifdef DEBUG
    fprintf(stdout,"\nfinished\n dumping array:");
	for (int i=0; i<hostc; i++)
	{
		printf("%d: ip %s pin %llu, tin %llu, pout %llu tout %llu\n",
		i, inet_ntoa(hosts[i].hip), hosts[i].pin, hosts[i].tin, hosts[i].pout, hosts[i].tout );
	}
	pcap_stat ps;
	pcap_stats(descr,&ps);
	printf("recv: %d drop: %d if_drop: %d\n", ps.ps_recv, ps.ps_drop, ps.ps_ifdrop);
#endif
    return 0;
}

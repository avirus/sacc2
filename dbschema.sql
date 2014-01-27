-- $Author: slavik $ $Rev: 55 $
-- $Id: dbschema.sql 55 2009-03-10 11:23:18Z slavik $

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/`sacc2` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `sacc2`;

/*Table structure for table `auth` */

DROP TABLE IF EXISTS `auth`;

CREATE TABLE `auth` (
  `id` bigint(20) unsigned NOT NULL auto_increment COMMENT 'mode id',
  `type` varchar(15) NOT NULL default 'test' COMMENT 'system name',
  `prog` text COMMENT 'auth parameter 1',
  `param` text NOT NULL COMMENT 'auth parameter 2',
  `name` text character set koi8r COMMENT 'visible name',
  `sysname` text character set koi8r COMMENT 'short name',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

/*Data for the table `auth` */

LOCK TABLES `auth` WRITE;

insert  into `auth`(`id`,`type`,`prog`,`param`,`name`,`sysname`) values ('1','mysql','buildin','1','mysql authorization (bundled)','mysql'),('2','msad','@russia.local','11.0.0.70','microsoft active directory auth in @russia.local domain at DC 11.0.0.70','russia@AD'),('3','ncsa','/tmp/passwd','1','ncsa auth via file /usr/local/sacc2/etc/passwd (WiP)','htpasswd@ncsa'),('4','msad','@zuik.ru',' 192.168.1.1','msad auth over zuik domain','zuik@AD');

UNLOCK TABLES;

/*Table structure for table `natchng` */

DROP TABLE IF EXISTS `natchng`;

CREATE TABLE `natchng` (
  `id` bigint(32) unsigned NOT NULL auto_increment COMMENT 'log entry uniq id',
  `dt` datetime NOT NULL COMMENT 'session len in seconds',
  `tin` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes recv by client',
  `tout` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes sent by client',
  `pin` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets recv by client',
  `pout` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets sent by client',
  `cost` bigint(32) unsigned NOT NULL default '0' COMMENT 'session cost',
  `s_id` bigint(32) unsigned NOT NULL default '0' COMMENT 'session id',
  `chng` bigint(32) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `natchng` */

LOCK TABLES `natchng` WRITE;

UNLOCK TABLES;

/*Table structure for table `natdet` */

DROP TABLE IF EXISTS `natdet`;

CREATE TABLE `natdet` (
  `id` bigint(32) unsigned NOT NULL auto_increment COMMENT 'record id',
  `sip` int(10) unsigned NOT NULL default '0' COMMENT 'source ip',
  `stime` int(10) unsigned NOT NULL default '0' COMMENT 'start time',
  `etime` int(10) unsigned NOT NULL default '0' COMMENT 'end time',
  `tin` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes in',
  `tout` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes out',
  `dport` smallint(5) unsigned NOT NULL default '0' COMMENT 'destination port',
  `sport` smallint(5) unsigned NOT NULL default '0' COMMENT 'source port',
  `dip` int(10) unsigned NOT NULL default '0' COMMENT 'destination ip',
  `ns_id` bigint(32) unsigned NOT NULL default '0' COMMENT 'nat session id',
  `pin` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets in',
  `pout` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets out',
  `ipproto` smallint(4) unsigned NOT NULL default '2' COMMENT 'ip protocol number',
  PRIMARY KEY  (`id`),
  KEY `ip_id` (`sip`,`dip`,`ns_id`,`etime`,`dport`,`sport`,`ipproto`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED COMMENT='nat detailed data';

/*Data for the table `natdet` */

LOCK TABLES `natdet` WRITE;

UNLOCK TABLES;

/*Table structure for table `natlog` */

DROP TABLE IF EXISTS `natlog`;

CREATE TABLE `natlog` (
  `id` bigint(32) unsigned NOT NULL auto_increment COMMENT 'log entry uniq id',
  `hip` bigint(32) unsigned NOT NULL default '0' COMMENT 'host IP addr',
  `hmac` bigint(32) unsigned NOT NULL default '0' COMMENT 'host MAC addr',
  `stime` datetime default NULL COMMENT 'session start datetime',
  `slen` bigint(32) unsigned NOT NULL default '0' COMMENT 'session len in seconds',
  `tin` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes recv by client',
  `tout` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes sent by client',
  `pin` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets recv by client',
  `pout` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets sent by client',
  `u_id` bigint(32) unsigned NOT NULL default '0' COMMENT 'user uniq id',
  `cost` bigint(32) unsigned NOT NULL default '0' COMMENT 'session cost',
  `s_id` bigint(32) unsigned NOT NULL default '0' COMMENT 'session id',
  PRIMARY KEY  (`id`),
  KEY `uid_hip_stime` (`hip`,`stime`,`u_id`)
) ENGINE=MyISAM DEFAULT CHARSET=koi8r ROW_FORMAT=DYNAMIC COMMENT='nat session log';

/*Data for the table `natlog` */

LOCK TABLES `natlog` WRITE;

UNLOCK TABLES;

/*Table structure for table `natsess` */

DROP TABLE IF EXISTS `natsess`;

CREATE TABLE `natsess` (
  `id` bigint(32) unsigned NOT NULL auto_increment COMMENT 'uniq host id',
  `u_id` bigint(32) unsigned NOT NULL default '1' COMMENT 'user id',
  `tin` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes recv by client',
  `tout` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes sent by client',
  `pin` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets recv by client',
  `pout` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets sent by client',
  `usn` bigint(32) unsigned NOT NULL default '0' COMMENT 'change number',
  `hip` bigint(32) unsigned NOT NULL default '0' COMMENT 'host IP addr',
  `hmac` bigint(32) unsigned NOT NULL default '0' COMMENT 'host MAC addr',
  `stime` datetime default NULL COMMENT 'session start datetime',
  `bill` bigint(32) unsigned NOT NULL default '0' COMMENT 'session cost',
  `closing` int(10) unsigned NOT NULL default '0' COMMENT 'is session closing?',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=306 DEFAULT CHARSET=koi8r ROW_FORMAT=DYNAMIC COMMENT='nat session pool';

/*Data for the table `natsess` */

LOCK TABLES `natsess` WRITE;

insert  into `natsess`(`id`,`u_id`,`tin`,`tout`,`pin`,`pout`,`usn`,`hip`,`hmac`,`stime`,`bill`,`closing`) values ('268','1','0','189295','0','1558','0','3232235879','0','2009-05-01 04:42:02','0','0'),('264','1','0','0','0','0','0','184549439','0','2009-03-06 17:28:11','0','0'),('266','5','0','0','0','0','0','184549455','0','2009-03-06 17:28:19','0','0'),('305','1','193481','267101','725','1353','0','3232235816','0','2009-05-31 10:56:15','0','0'),('303','1','0','0','0','0','0','3232235620','0','2009-03-22 07:24:49','0','0'),('270','1','0','0','0','0','0','16909060','0','2009-02-21 20:17:12','0','0'),('304','1','1223691','83254803','3067','1019947','0','3232235786','0','2009-06-01 04:42:03','0','0'),('296','11','0','0','0','0','0','174328330','0','2009-03-10 18:25:13','0','0'),('298','1','0','0','0','0','0','3232235890','0','2009-05-31 10:56:17','0','0');

UNLOCK TABLES;

/*Table structure for table `natstat` */

DROP TABLE IF EXISTS `natstat`;

CREATE TABLE `natstat` (
  `id` bigint(32) unsigned NOT NULL auto_increment COMMENT 'uniq stat id',
  `u_id` bigint(32) unsigned NOT NULL default '1' COMMENT 'uniq user id',
  `tin` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes recv by client',
  `tout` bigint(32) unsigned NOT NULL default '0' COMMENT 'bytes sent by client',
  `pin` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets recv by client',
  `pout` bigint(32) unsigned NOT NULL default '0' COMMENT 'packets sent by client',
  `sesscount` bigint(32) unsigned NOT NULL default '0' COMMENT 'sessions count',
  `timtotal` bigint(32) unsigned NOT NULL default '0' COMMENT 'total active time in seconds',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=koi8r;

/*Data for the table `natstat` */

LOCK TABLES `natstat` WRITE;

UNLOCK TABLES;

/*Table structure for table `options` */

DROP TABLE IF EXISTS `options`;

CREATE TABLE `options` (
  `id` smallint(6) unsigned NOT NULL auto_increment,
  `name` varchar(127) NOT NULL default '',
  `value` text,
  `descr` text,
  `help` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=koi8r;

/*Data for the table `options` */

LOCK TABLES `options` WRITE;

insert  into `options`(`id`,`name`,`value`,`descr`,`help`) values ('1','language','1','language','0 - ru, 1 - en.'),('2','office_mode','0','office mode','shall we display anything about money?'),('3','admin_mail','nowhere','alert mail',NULL),('5','detailed','1','detailed traffic',NULL),('6','delimiter',' ','delimiter',NULL),('7','def_tariff','2','default tariff system name',NULL),('8','std_limit','20','default payment sum',NULL),('23','IF','eth0','internal interface name',NULL),('10','order_main','0','main frame sort order','0-6 sort order'),('11','order_uhist','1','history sort order','0-6 sort order'),('12','main_ch','1','use color highlight in user manager','on/off'),('13','uhist_ch','1','use color highlight in user history','on/off'),('14','origin','SAcc-2.00alpha5','webinterface header','=)'),('15','pagelen','10','length of page','numeric'),('16','timezone','5','delta from UTC','time offset from UTC'),('17','cisco','0','we need to show cisco ipacc stat','no/yes'),('18','pcap','1','we need to show nat service usage stat','no/yes'),('19','ovpn','0','we need to show openvpn service usage stat','no/yes'),('20','DEBUG','0','debugging mode','0 -no, 1 - yes.'),('21','URL_STAT','/login.php','redirect unknown users to','url, like \"/sacc2/login.php\" translate to \"http://[internal interface name]/sacc2/login.php\"'),('24','DAEMON_PORT','8899','captive portal daemon tcp port','number of captive portal redirector daemon tcp port (must be free, and inside 1025-65534), default 8899'),('25','DETAIL_TRAFFIC_MULT','2','detailed traffic dump period',' measured in tens of seconds');

UNLOCK TABLES;

/*Table structure for table `payments` */

DROP TABLE IF EXISTS `payments`;

CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL auto_increment COMMENT 'payment uniq id',
  `u_id` int(10) unsigned NOT NULL default '1' COMMENT 'uniq user id',
  `amount` bigint(20) unsigned NOT NULL default '0' COMMENT 'amount',
  `stat` tinyint(10) unsigned NOT NULL default '0' COMMENT 'payment status',
  `paytime` datetime NOT NULL COMMENT 'payment datetime',
  `cmnt` text COMMENT 'payment comment',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=koi8r ROW_FORMAT=DYNAMIC COMMENT='payments log table';

/*Data for the table `payments` */

LOCK TABLES `payments` WRITE;

insert  into `payments`(`id`,`u_id`,`amount`,`stat`,`paytime`,`cmnt`) values ('1','1','1','1','0000-00-00 00:00:00','test');

UNLOCK TABLES;

/*Table structure for table `tariffs` */

DROP TABLE IF EXISTS `tariffs`;

CREATE TABLE `tariffs` (
  `id` int(10) unsigned NOT NULL auto_increment COMMENT 'tariff uniq id',
  `stype` int(10) unsigned NOT NULL default '2' COMMENT 'service type, 1- vpn, 2-nat, 3-squid',
  `usize` int(10) unsigned NOT NULL default '1048576' COMMENT 'unit size, by default =1Mbyte',
  `dcost` int(10) unsigned NOT NULL default '1' COMMENT 'download, unit cost',
  `ucost` int(10) unsigned NOT NULL default '0' COMMENT 'upload, unit cost',
  `tstart` int(10) unsigned NOT NULL default '0' COMMENT 'tariff start time',
  `tstop` int(10) unsigned NOT NULL default '0' COMMENT 'tariff stop time',
  `sysname` text COMMENT 'system name',
  `vname` text COMMENT 'user visible name',
  `act` int(10) unsigned NOT NULL default '1' COMMENT 'is active?',
  `mbonus` int(10) unsigned NOT NULL default '0' COMMENT 'monthly bonus payment',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=koi8r ROW_FORMAT=DYNAMIC COMMENT='tariffs table';

/*Data for the table `tariffs` */

LOCK TABLES `tariffs` WRITE;

insert  into `tariffs`(`id`,`stype`,`usize`,`dcost`,`ucost`,`tstart`,`tstop`,`sysname`,`vname`,`act`,`mbonus`) values ('1','2','1048576','0','1','0','0','nat','nat access','1','0'),('2','2','1048576','0','0','0','0','unlnat','unlimited nat','1','0'),('3','2','1','0','0','0','0','UNL','unlnat','0','10');

UNLOCK TABLES;

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint(32) unsigned NOT NULL auto_increment COMMENT 'user uniq id',
  `a_id` bigint(20) unsigned NOT NULL COMMENT 'auth mode id',
  `login` varchar(25) NOT NULL COMMENT 'user login name',
  `passwd` varchar(55) NOT NULL COMMENT 'user password',
  `vname` text,
  `balans` bigint(32) NOT NULL default '0' COMMENT 'cash',
  `bill` bigint(32) NOT NULL default '0' COMMENT 'locked by services',
  `active` int(11) NOT NULL default '1' COMMENT 'boolean flag',
  `overdraft` bigint(32) unsigned NOT NULL default '0' COMMENT 'user overdraft',
  `t_id` int(10) unsigned NOT NULL default '1' COMMENT 'user tariff group',
  `email` text COMMENT 'user email',
  `perm` int(10) unsigned NOT NULL default '3' COMMENT 'user permissions',
  `enotifyed` int(10) unsigned NOT NULL default '0' COMMENT 'enotified',
  `a_domain` text COMMENT 'auth realm',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=koi8r;

/*Data for the table `users` */

LOCK TABLES `users` WRITE;

insert  into `users`(`id`,`a_id`,`login`,`passwd`,`vname`,`balans`,`bill`,`active`,`overdraft`,`t_id`,`email`,`perm`,`enotifyed`,`a_domain`) values ('1','1','slavik','1','the.virus','-100081','1000','1','1234567890','2','slavik@cybersec.ru','777','0',NULL),('6','1','1','1','10000','0','0','1','22221212','1','1@ru.ru','777','0',NULL),('5','2','admin','1','admin','1','1','1','999','3','1@rrr.ru','14','0',NULL);

UNLOCK TABLES;

/*Table structure for table `vpnlog` */

DROP TABLE IF EXISTS `vpnlog`;

CREATE TABLE `vpnlog` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `ip` int(10) unsigned NOT NULL default '0',
  `tout` bigint(20) unsigned NOT NULL default '0',
  `tin` bigint(20) unsigned NOT NULL default '0',
  `stime` datetime default NULL,
  `len` int(10) unsigned NOT NULL default '0',
  `u_id` int(10) unsigned NOT NULL default '0',
  `bill` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=koi8r;

/*Data for the table `vpnlog` */

LOCK TABLES `vpnlog` WRITE;

UNLOCK TABLES;

/*Table structure for table `vpnsess` */

DROP TABLE IF EXISTS `vpnsess`;

CREATE TABLE `vpnsess` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `u_id` int(10) unsigned NOT NULL default '0',
  `starttime` datetime NOT NULL,
  `tin` bigint(20) unsigned NOT NULL default '0',
  `tout` bigint(20) unsigned NOT NULL default '0',
  `bill` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=koi8r;

/*Data for the table `vpnsess` */

LOCK TABLES `vpnsess` WRITE;

UNLOCK TABLES;

/*Table structure for table `vpnstat` */

DROP TABLE IF EXISTS `vpnstat`;

CREATE TABLE `vpnstat` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `u_id` int(10) unsigned NOT NULL default '1',
  `tin` bigint(20) unsigned NOT NULL default '0',
  `tout` bigint(20) unsigned NOT NULL default '0',
  `sesscount` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=koi8r;

/*Data for the table `vpnstat` */

LOCK TABLES `vpnstat` WRITE;

UNLOCK TABLES;

/* Function  structure for function  `nat_detail_add` */

/*!50003 DROP FUNCTION IF EXISTS `nat_detail_add` */;
DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` FUNCTION `nat_detail_add`(d_size_in BIGINT, d_size_out BIGINT, d_pkt_in BIGINT, d_pkt_out BIGINT, d_sip BIGINT, d_dip BIGINT, d_sport BIGINT, d_dport BIGINT, d_ipproto INT) RETURNS bigint(20)
BEGIN
DECLARE my_ns_id BIGINT;
DECLARE tmp BIGINT;
select count(id) into tmp  from natsess where hip = d_sip or hip= d_dip;
IF (tmp>0) 
THEN
select id into my_ns_id from natsess where hip = d_sip or hip= d_dip limit 1;
	insert into natdet (etime, sip, dip, tin, tout, pin, pout, sport, dport, ipproto, ns_id) values (UNIX_TIMESTAMP(), d_sip, d_dip, d_size_in, d_size_out, d_pkt_in, d_pkt_out, d_sport, d_dport, d_ipproto,my_ns_id);
RETURN 0;
end if;
	insert into natdet (etime, sip, dip, tin, tout, pin, pout, sport, dport, ipproto, ns_id) values (UNIX_TIMESTAMP(), d_sip, d_dip, d_size_in, d_size_out, d_pkt_in, d_pkt_out, d_sport, d_dport, d_ipproto,0);
RETURN 1;
END */$$
DELIMITER ;

/* Function  structure for function  `nat_session_alter` */

/*!50003 DROP FUNCTION IF EXISTS `nat_session_alter` */;
DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` FUNCTION `nat_session_alter`(s_id BIGINT, s_tin BIGINT, s_tout BIGINT, s_pin BIGINT, s_pout BIGINT) RETURNS bigint(20)
BEGIN
 DECLARE s1_tin BIGINT;
 DECLARE sl_dt TEXT;
 DECLARE s1_tout BIGINT;
 DECLARE s1_pin BIGINT;
 DECLARE s1_pout BIGINT;
 DECLARE us_bal BIGINT;
 DECLARE us_bill BIGINT;
 DECLARE se_dbill BIGINT;
 DECLARE se_dtin BIGINT;
 DECLARE se_dtout BIGINT;
 DECLARE se_dpin BIGINT;
 DECLARE se_dpout BIGINT;
 DECLARE us_over BIGINT;
 DECLARE t_ucost BIGINT;
 DECLARE t_dcost BIGINT;
 DECLARE t_usize BIGINT;
 DECLARE ta_id BIGINT;
 DECLARE us_id BIGINT;
 DECLARE tmp BIGINT;
 SELECT (date_format(now(),'%Y%m%d%H%i00')) into sl_dt;
 SELECT u_id, tin, tout, pin, pout, count(id) into us_id, s1_tin, s1_tout, s1_pin, s1_pout, tmp FROM natsess WHERE id=s_id;
if (tmp=0) 
then
RETURN 0;
end if;
set se_dtin=(s_tin - s1_tin);
set se_dtout=(s_tout - s1_tout);
set se_dpin=(s_pin - s1_pin);
set se_dpout=(s_pout - s1_pout);
SELECT balans, bill, overdraft, t_id into us_bal, us_bill, us_over, ta_id FROM users WHERE id=us_id;
select dcost, ucost, usize into t_dcost, t_ucost, t_usize from tariffs where (id=ta_id and stype=2);
set se_dbill = (((s_tin - s1_tin)/t_usize)*t_dcost)+(((s_tout - s1_tout)/t_usize)*t_ucost);
select 	count(id) into tmp from natchng  where dt = sl_dt limit 1;
IF (tmp=0) 
then 
    insert into natchng (dt, tin, tout, pin, pout, cost, s_id) values (sl_dt, 0, 0, 0, 0, 0, s_id);
end if;
update natchng set tin=tin+se_dtin, tout=tout+se_dtout, pin=pin+se_dpin,  pout= pout+se_dpout, cost=cost+ se_dbill, chng=chng+1 where dt = sl_dt;
update users set bill=bill+se_dbill where id=us_id;
update natsess set tin=s_tin, tout=s_tout, pin=s_pin, pout=s_pout, bill=bill+se_dbill WHERE id=s_id;
IF (us_bill+se_dbill)>(us_bal+us_over) 
then 
    update natsess set closing=1 WHERE id=s_id;
    RETURN 0;
end if;
RETURN 1;
END */$$
DELIMITER ;

/* Function  structure for function  `nat_session_start` */

/*!50003 DROP FUNCTION IF EXISTS `nat_session_start` */;
DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` FUNCTION `nat_session_start`(us_id BIGINT, hst_ip BIGINT, hst_mac BIGINT) RETURNS int(11)
BEGIN
 DECLARE s_id BIGINT;
 DECLARE se_id BIGINT;
select count(id) into s_id from natsess where hip=hst_ip;
if s_id >0 then
update natsess set closing=1 where hip=hst_ip;
end if;
 insert into natsess (u_id, stime, hip, hmac) values (us_id, now(), hst_ip, hst_mac);
 SELECT LAST_INSERT_ID() into se_id;
 RETURN se_id;
 END */$$
DELIMITER ;

/* Function  structure for function  `vpn_session_alter` */

/*!50003 DROP FUNCTION IF EXISTS `vpn_session_alter` */;
DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` FUNCTION `vpn_session_alter`(s_id INT, s_tin INT, s_tout INT) RETURNS int(11)
BEGIN
 DECLARE s1_tin INT;
 DECLARE s1_tout INT;
 DECLARE us_bal BIGINT;
 DECLARE us_bill BIGINT;
 DECLARE se_dbill BIGINT;
 DECLARE us_over BIGINT;
 DECLARE t_ucost BIGINT;
 DECLARE t_dcost BIGINT;
 DECLARE t_usize BIGINT;
 DECLARE ta_id INT;
 DECLARE us_id BIGINT;
 SELECT u_id, tin, tout into us_id, s1_tin, s1_tout FROM vpnsess WHERE id=s_id;
 SELECT balans, bill, overdraft, t_id into us_bal, us_bill, us_over, ta_id FROM users WHERE id=us_id;
 select dcost, ucost, usize into t_dcost, t_ucost, t_usize from tariffs where (id=ta_id and stype=1);
 set se_dbill = (((s_tin - s1_tin)/t_usize)*t_dcost)+(((s_tout - s1_tout)/t_usize)*t_ucost);
 update users set bill=bill+se_dbill where id=us_id;
 update vpnsess set tin=s_tin, tout=s_tout, bill=bill+se_dbill WHERE id=s_id;
 IF (us_bill+se_dbill)>(us_bal+us_over) 
 then RETURN 0;
 end if;
 RETURN 1;
 END */$$
DELIMITER ;

/* Function  structure for function  `vpn_session_start` */

/*!50003 DROP FUNCTION IF EXISTS `vpn_session_start` */;
DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` FUNCTION `vpn_session_start`(us_id INT) RETURNS int(11)
BEGIN
 DECLARE se_id INT;
 insert into vpnsess (u_id, starttime, tin, tout) values (us_id, now(), 0,0);
 SELECT LAST_INSERT_ID() into se_id;
 RETURN se_id;
 END */$$
DELIMITER ;

/* Procedure structure for procedure `nat_sessions_checkpoint` */

/*!50003 DROP PROCEDURE IF EXISTS  `nat_sessions_checkpoint` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` PROCEDURE `nat_sessions_checkpoint`(se_id BIGINT)
BEGIN
 DECLARE us_login VARCHAR(25);
 DECLARE ta_id INT;
 DECLARE us_id INT DEFAULT 5;
 DECLARE t_usize INT;
 DECLARE t_dcost INT;
 DECLARE t_ucost INT;
 DECLARE s_start DATETIME;
 DECLARE s_tin BIGINT;
 DECLARE s_tout BIGINT;
 DECLARE s_pin BIGINT;
 DECLARE s_pout BIGINT;
 DECLARE s_hip BIGINT;
 DECLARE s_hmac BIGINT;
 DECLARE s_time BIGINT;
 DECLARE s_bill BIGINT;
 DECLARE natlog_id BIGINT;
repeat
 set us_id=0;
 SELECT id
 into us_id
 FROM natsess WHERE tin>0 or tout>0 limit 1;
 IF us_id>0 
	then
	call nat_session_checkpoint(us_id);
 end if;
 SELECT count(u_id)  into us_id  FROM natsess WHERE tin>0 or tout>0;
until us_id=0 end repeat;
END */$$
DELIMITER ;

/* Procedure structure for procedure `nat_session_checkpoint` */

/*!50003 DROP PROCEDURE IF EXISTS  `nat_session_checkpoint` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` PROCEDURE `nat_session_checkpoint`(se_id BIGINT)
BEGIN
 DECLARE us_login VARCHAR(25);
 DECLARE ta_id INT;
 DECLARE us_id INT;
 DECLARE t_usize INT;
 DECLARE t_dcost INT;
 DECLARE t_ucost INT;
 DECLARE s_start DATETIME;
 DECLARE s_tin BIGINT;
 DECLARE s_tout BIGINT;
 DECLARE s_pin BIGINT;
 DECLARE s_pout BIGINT;
 DECLARE s_hip BIGINT;
 DECLARE s_hmac BIGINT;
 DECLARE s_time BIGINT;
 DECLARE s_bill BIGINT;
 DECLARE natlog_id BIGINT;
 SELECT u_id, stime, tin, tout, pin, pout, bill, hmac, hip 
 into us_id, s_start, s_tin, s_tout, s_pin, s_pout, s_bill, s_hmac, s_hip
 FROM natsess WHERE id=se_id;
IF us_id>0 
 then 
 select TIME_TO_SEC(timediff(now(), s_start)) into s_time;
 update users set balans=balans-s_bill, bill=bill-s_bill where id=us_id;
 insert into natlog (u_id, tin, tout, hip, hmac, stime, slen, pin, pout, cost, s_id) 
 values (us_id, s_tin, s_tout, s_hip, s_hmac, s_start, s_time, s_pin, s_pout, s_bill, se_id);
SELECT LAST_INSERT_ID() into natlog_id;
 update natstat set tin=tin+s_tin, tout=tout+s_tout, sesscount=sesscount+1, timtotal=timtotal+s_time, pin=pin+s_pin, pout=pout+s_pout where u_id=us_id;
update natchng set s_id=natlog_id where s_id=se_id;
update natdet set ns_id=natlog_id where ns_id=se_id;
update natsess set tin=0, tout=0, pin=0, pout=0, stime=now() WHERE id=se_id;
end if;
 END */$$
DELIMITER ;

/* Procedure structure for procedure `nat_session_stop` */

/*!50003 DROP PROCEDURE IF EXISTS  `nat_session_stop` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` PROCEDURE `nat_session_stop`(se_id BIGINT)
BEGIN
 DECLARE us_login VARCHAR(25);
 DECLARE ta_id INT;
 DECLARE us_id INT;
 DECLARE t_usize INT;
 DECLARE t_dcost INT;
 DECLARE t_ucost INT;
 DECLARE s_start DATETIME;
 DECLARE s_tin BIGINT;
 DECLARE s_tout BIGINT;
 DECLARE s_pin BIGINT;
 DECLARE s_pout BIGINT;
 DECLARE s_hip BIGINT;
 DECLARE s_hmac BIGINT;
 DECLARE s_time BIGINT;
 DECLARE s_bill BIGINT;
 DECLARE natlog_id BIGINT;
 SELECT u_id, stime, tin, tout, pin, pout, bill, hmac, hip 
 into us_id, s_start, s_tin, s_tout, s_pin, s_pout, s_bill, s_hmac, s_hip
 FROM natsess WHERE id=se_id;
IF us_id>0 
 then 
 select TIME_TO_SEC(timediff(now(), s_start)) into s_time;
 update users set balans=balans-s_bill, bill=bill-s_bill where id=us_id;
 insert into natlog (u_id, tin, tout, hip, hmac, stime, slen, pin, pout, cost, s_id) 
 values (us_id, s_tin, s_tout, s_hip, s_hmac, s_start, s_time, s_pin, s_pout, s_bill, se_id);
SELECT LAST_INSERT_ID() into natlog_id;
 update natstat set tin=tin+s_tin, tout=tout+s_tout, sesscount=sesscount+1, timtotal=timtotal+s_time, pin=pin+s_pin, pout=pout+s_pout where u_id=us_id;
 delete from natsess WHERE id=se_id;
update natchng set s_id=natlog_id where s_id=se_id;
update natdet set ns_id=natlog_id where ns_id=se_id;
end if;
 END */$$
DELIMITER ;

/* Procedure structure for procedure `vpn_session_stop` */

/*!50003 DROP PROCEDURE IF EXISTS  `vpn_session_stop` */;

DELIMITER $$

/*!50003 CREATE DEFINER=`sacc2`@`localhost` PROCEDURE `vpn_session_stop`(s_id INT)
BEGIN
 DECLARE ta_id INT;
 DECLARE us_id INT;
 DECLARE s_start DATETIME;
 DECLARE s_tin BIGINT;
 DECLARE s_tout BIGINT;
 DECLARE s_time BIGINT;
 DECLARE s_bill BIGINT;
 SELECT u_id, starttime, tin, tout, bill into us_id, s_start, s_tin, s_tout, s_bill FROM vpnsess WHERE id=s_id;
 select TIME_TO_SEC(timediff(now(), s_start)) into s_time;
 insert into vpnlog (u_id, tout, tin, stime, len, bill) 
 values (us_id, s_tout, s_tin, s_start, s_time, us_id, s_bill);
 update vpnstat set tin=tin+s_tin, tout=tout+s_tout, sesscount=sesscount+1 where u_id=us_id;
 update users set balans=balans-s_bill, bill=bill-s_bill where id=us_id;
 delete from vpnsess WHERE id=s_id;
 END */$$
DELIMITER ;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
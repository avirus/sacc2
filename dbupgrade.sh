#!/bin/sh
# $Author: slavik $ $Rev: 55 $
# $Id: dbschema.sql 55 2009-03-10 11:23:18Z slavik $

mysqldump sacc2 users > sacc_1.sql
mysqldump sacc2 natsess >> sacc_1.sql
mysql <dbgrant.sql
mysql <dbschema.sql
mysql sacc2 <sacc_1.sql

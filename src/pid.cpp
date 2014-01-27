/********************************************************************************
#  Copyright (C) 2002-2008  Vyacheslav 'Slavik' Nikitin
# SAcc system v2
# $Author: slavik $ $Date: 2008-07-08 13:45:33 +0600 (Втр, 08 Июл 2008) $
# $Id: pid.cpp 16 2008-07-08 07:45:33Z slavik $
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
#ifdef HAVE_CONFIG_H
#include <config.h>
#endif
/* includes */
#include <signal.h>
#include "pid.h"
#define _V2PC(data) (char*)(data) /* void -> char* */
#include <stdio.h>
#include <signal.h>
#include <stdlib.h>
#include <string.h>
#include <strings.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

char* pid_fname;
pid_t __pid;
/* initialize class
in: pid filename
*/
void cpid::init(const char* fname)
{
pid_fname = (char *)malloc(MAXLEN * sizeof(char));
strncpy(pid_fname, fname, MAXLEN * sizeof(char));
}
/* check for running copy 
out: 
true - if copy is running
false - if not
printf("%s is already running!  Process ID %d\n", APP_NAME, pid);
*/
//#include <sys/dirent.h>
bool cpid::running(void)
{
    read();
    if (__pid < 2) return false;
    if (kill(__pid, 0) < 0) return false;
    return true;
}
// /* Read PID file */
void cpid::read(void)
{
    FILE *pid_fp = NULL;
    //char *f = pid_fname;
    __pid = -1;
    int i;
    pid_fp = fopen(pid_fname, "r");
    if (pid_fp != NULL) {
        __pid = 0;
        if (fscanf(pid_fp, "%d", &i) == 1)
            __pid = (pid_t) i;
        fclose(pid_fp);
    }
    //return pid;
}
/* write pid file
out: 
true - if sucsess
false - if not
*/
bool cpid::create(void)
{
    FILE *fd = NULL;
    mode_t old_umask;
    char buf[32];
    if (!strcmp(pid_fname, "none"))
        return false;
    old_umask = umask(022);
    fd = fopen(pid_fname, "w");
    umask(old_umask);
    if (fd == NULL) return false;
    snprintf(buf, 32, "%d\n", (int) getpid());
    fwrite(buf, (size_t)1, strlen(buf), fd);
//    if (0 != fsync((int)fd)) logerr(_T("PID: sync error.")); /* some bugs */
    fclose(fd);
    return true;
}
// cleanup
cpid::~cpid()
{
	unlink(pid_fname);
	free(pid_fname);
}

#!/bin/bash

sleep 5
DATE="May 23th, 2022"
IP=`ip addr | grep inet | sed 's/[ ]\+//' | cut -d" " -f 2 | grep -v 127.0.0 | grep -v ::1/128 | grep -v fe80 | sed 's@/.*@@'`
HOST=`hostname`
REGHOST=`dig +short ${HOST}`
URL="http://${HOST}"
#if [ "${REGHOST}" != "${IP}" ]; then
#  URL="http://${IP}"
#fi

echo "Debian GNU/Linux 11 \n \l

Welcome to the Dreambox ReStream Live Image

The default login for SSH
                    user: restream
                password: restream

This image has IP number(s): ${IP}
                hostname(s): ${HOST}

Open the Dreambox ReStream in your browser on the address(es):
                          ${URL}

If the above url fails, try the IP number instead

                            Version: ${DATE}
" > /etc/issue


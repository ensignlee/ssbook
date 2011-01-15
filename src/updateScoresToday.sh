#!/bin/sh
CONSOLE=/home/loyd/git/ssbook/src/cake/console
cd /home/loyd/git/ssbook/src/app
./vendors/cakeshell scores -cli /usr/bin -console $CONSOLE -type espn -date `date "+%F"`

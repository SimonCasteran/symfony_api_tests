#!/bin/sh

LSBRELEASE=`lsb_release -sc`
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $LSBRELEASE main" | tee /etc/apt/sources.list.d/php.list
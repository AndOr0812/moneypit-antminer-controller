# moneypit-antminer-controller

Typically installed on a Raspberry Pi.

Provides a set of REST APIs to monitor / maintain / deploy Antminers in moneypit crypto mines.

## Dependencies

> Recommend running `sudo apt-get update` on Raspberry Pi prior to install

- Git
   `sudo apt-get install git`

- PHP
  `sudo apt-get install php7.0`

## install

- Clone repo

```
git clone https://github.com/moneypit/moneypit-antminer-controller`
cd moneypit-antminer-controller

```

- Install dependencies

```
wget https://raw.githubusercontent.com/composer/getcomposer.org/1b137f8bf6db3e79a38a5bc45324414a6b1f9df2/web/installer -O - -q | php -- --quiet
php composer.phar install

```

- Configure to start API on reboot using `/etc/rc.local`

```

	#!/bin/sh -e
	#
	# rc.local
	#
	# This script is executed at the end of each multiuser runlevel.
	# Make sure that the script will "exit 0" on success or any other
	# value on error.
	#
	# In order to enable or disable this script just change the execution
	# bits.
	#
	# By default this script does nothing.

	# Print the IP address
	_IP=$(hostname -I) || true
	if [ "$_IP" ]; then
	  printf "My IP address is %s\n" "$_IP"
	fi

  # Start pigpiod
  /usr/bin/php composer.phar start &

	exit 0

```

## APIs

- Following start of server, goto `http://[hostname]:3000/` for swagger doc

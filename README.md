# Portknock

[![Build](https://github.com/conFrituur/portknock/actions/workflows/build.yml/badge.svg)](https://github.com/conFrituur/portknock/actions/workflows/build.yml)
[![Coverage](https://raw.githubusercontent.com/conFrituur/portknock/refs/heads/coverage-badge/coverage.svg)](https://github.com/conFrituur/portknock/actions/workflows/build.yml)
[![php](https://img.shields.io/badge/PHP-8.4-brightgreen?logo=php&style=flat&logoColor=lightgrey)](https://www.php.net/supported-versions.php)

This application serves to allow for externally opening my OPNsense firewall to a network by performing a 'knock', similar to [Portknocking](https://en.wikipedia.org/wiki/Port_knocking). 
At the same time it also serves as a showcase of my current PHP programming skills.
Hence, why this application is arguably a bit overengineered. All human written, no generated AI code used in this project.

# Sequence of Operation

### 1. First knock
A knock is a done by sending a HTTP GET request to the index.php at the correct URL where (in my case by nginx) it's served. 
For authentication this request must contain a `x-sesam` header with a token, which is matched with a user in the `data/users.json` file.
When the user has correct permissions (`WRITE_ONLY`), the first part of the knock request is completed. 
The IP that has made the HTTP request will be saved to the `allowlist.json`.

>When the request is made from a IPv6 address, the whole /64 range will be saved to the Allowlist. 
I want to grant access to every device within a network (e.g. every device at a friends house).
IPv4 usually has this covered with 1 IP through NAT. With IPv6 all devices have unique global addresses through SLAAC.

### 2. Second (optional) knock - IPv4 && IPv6
Once a knock is successful, a `HTTP 307 Temp Redirect` will be returned to a DNS host with either only an IPv4 or IPv6 address (if configured in `data/config.json`).
This will force a followup request to be made from a IPv4 or IPv6 address, depending on the IP version that was used in the original knock.
Once done, both the IPv4 and the IPv6(range) of the network are Allowlisted.

### 3. OPNsense Allowlist
OPNsense has functionality to retrieve a dynamic list with networks to use as *Alias* in its firewall (URL Table IPs). 
This alias will periodically refresh with an HTTP GET request to fetch a list with IPs. This request can be done to the `/view`, 
also authenticating with na `x-sesam` header token of a user with `READ_ONLY` permissions.


# Which parts go where?

## The one that knocks
The most easily accessible device to knock from would be your phone. In IOS an automation can be made to created to make a HTTP request with a header.
You can trigger it manually or automatically, when you connect to a WiFi network for instance.

## Portknock application
This PHP application should be hosted on a publicly accessible webserver. The knocker must be able to knock from anywhere.
Example URLs: 
- `https://portknock.example.com/knock`
- `https://portknock.example.com/knock/view`

### Webserver 

Expose the `Public` folder only!

NGINX example config:
```
location /knock {
      alias /var/www/portknock.example.com/knock/public/;
      index index.php
      try_files $uri $uri/ /knock/index.php;

      location ~ \.php$ {
        include fastcgi.conf;
        fastcgi_param SCRIPT_FILENAME /var/www/portknock.example.com/knock/public/index.php;
        fastcgi_index index.php;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
      }
```



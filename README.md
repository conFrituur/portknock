# Portknock

[![Build](https://github.com/conFrituur/portknock/actions/workflows/ci.yml/badge.svg)](https://github.com/conFrituur/portknock/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/dynamic/regex?url=https%3A%2F%2Fraw.githubusercontent.com%2FconFrituur%2Fportknock%2Frefs%2Fheads%2Fcoverage-badge%2Foutput.txt&search=Lines%3A%5Cs%2B(%5Cd%2B%5C.%5Cd%2B%25)&replace=%241&style=flat&label=Coverage)](https://github.com/conFrituur/portknock/blob/coverage-badge/output.txt)
[![php](https://img.shields.io/badge/PHP-^8.4-brightgreen?logo=php&style=flat&logoColor=lightgrey)](https://www.php.net/supported-versions.php)

This application provides an authenticated way of externally opening my OPNsense firewall to a remote IP. Performing a 'knock', similar to [Portknocking](https://en.wikipedia.org/wiki/Port_knocking), will allowlist the IPv4 and IPv6Range of the originating network. 
This project simultaneously serves as a showcase of my current PHP programming skills, hence why it's arguably a bit overengineered.

# Sequence of Operation

```
┌────────┐                      ┌───────────┐                  ┌──────────┐   
│ iPhone │                      │ Webserver │                  │ OPNsense │   
└────┬───┘                      └─────┬─────┘                  └─────┬────┘   
     │                                │                              │        
     │  GET knock.example.nl (IPv4)   │                              │        
     │────────────────────────────────▶                              │        
     │                                │                              │        
     │    307 REDIRECT (to IPv6)      │                              │        
     ◁╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌│                              │        
     │                                │                              │        
     │       ┌──────────────┐         │                              │        
     │       │ Second Knock │         │                              │        
     │       └──────────────┘         │                              │        
     │                                │                              │        
     │    GET v6.knock.example.nl     │                              │        
     │────────────────────────────────▶                              │        
     │                                │                              │        
     │     200 OK - Allowlisted       │                              │        
     ◁╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌│                              │        
     │                                │                              │        
     │                            ┌loop [Every minute]───────────────────┐    
     │                            │   │                              │   │    
     │                            │   │  GET knock.example.nl/list   │   │    
     │                            │   ◀──────────────────────────────│   │    
     │                            │   │                              │   │    
     │                            │   │  200 OK (data: allowlist)    │   │    
     │                            │   │╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌▷   │    
     │                            │   │                              │   │    
     │                            └──────────────────────────────────────┘    
     │                                │                              │        
┌────┴───┐                      ┌─────┴─────┐                  ┌─────┴────┐   
│ iPhone │                      │ Webserver │                  │ OPNsense │   
└────────┘                      └───────────┘                  └──────────┘   
```

### 1. First knock
The first knock consists of an HTTP GET request with a secret token in the `x-sesam` header for authentication. The tokens are defined as a hash in `data/users.json`.
When the user has correct permissions (`WRITE_ONLY`), the IP that has made the HTTP request will be saved to `data/allowlist.json`.

>When the request is made from a IPv6 address, the whole /64 range will be saved to the Allowlist.  
>This is based on the assumption that the remote network is a typical home-style network, with all unique global addresses for its devices (SLAAC).

### 2. Second (optional) knock - IPv4 <-> IPv6
Once the first knock is successful, a `HTTP 307 Temp Redirect` will be returned to a DNS host record with either only an IPv4 or IPv6 address (if configured in `data/config.json`).
This will force a followup request to be made from either a IPv4 or IPv6 address, depending on the IP version that was used in the original knock.
Once done, both the IPv4 and the IPv6(range) of the remote network are Allowlisted.

>The redirect URL from the first knock comes with an 'amendKey' in the query parameters, as to identify it as an amendement to the AllowlistEntry from the first knock.  
> The `x-sesam` header is also required for the second knock 

### 3. OPNsense Allowlist
OPNsense has builtin functionality to retrieve a dynamic list with ips/networks to use as *Alias* in its firewall (URL Table IPs). 
This firewall will once a minute retrieve the list for updates. Authentication is also done with a `x-sesam` header token. Although the user will need `READ_ONLY` permissions for this request.

# Which parts go where?

## The one that knocks
The most easily accessible device to knock from would be your phone. In IOS a shortcut/automation can be crafted to make an HTTP GET request with a header.
You can also trigger this shortcut automatically (automation) when you connect to a WiFi network for instance.

## Portknock application
This PHP application should be hosted on a publicly accessible webserver. The knocker must be able to knock from anywhere.
Example URLs: 
- `https://knock.example.nl/knock`
- `https://v4-knock.example.nl/knock` (ipv4 only for second knock)
- `https://v6-knock.example.nl/knock` (ipv6 only for second knock)
- `https://knock.example.nl/knock/list`

### Webserver 

- Expose the `Public` folder only!
- Combine the v4/v6 hosts to all go to the same root directory

### NGINX example config:
```
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name knock.example.nl v4-knock.example.nl v6-knock.example.nl;
    root /var/www;
    index index.php;

    location /knock {
      alias /var/www/portknock.example.nl/knock/public/;
      try_files $uri $uri/ /knock/index.php;

      location ~ \.php$ {
        include fastcgi.conf;
        fastcgi_param SCRIPT_FILENAME /var/www/portknock.example.nl/knock/public/index.php;
        fastcgi_param LOG_LEVEL debug; # can omit, default level = info
        fastcgi_index index.php;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
      }
}
```

# Config

## data/config.json

Only the hostname should be configured. The rest of the URL will be parsed from the REQUEST_URI header and reused from the first knock.  
Delete one or both settings from the file if you do not want to do a second knock.

```
{
    "v4host": "v4-knock.example.nl",
    "v6host": "v6-knock.example.nl"
}
```

## data/.key

The key will be autogenerated on the first knock. but may also be specified manually.  
It will be used for the hashing of the *x-sesam header token* in `data/users.json`

## data/users.json

The long identifier for each record is a SHA265 hash of the users *x-sesam header token* combined with the key specified in `data/.key`.

### Hash
The hash can be generated with PHP:  
`echo hash_hmac('sha256', '<x-sesam-token>', '<key>');`

### users.json
```
{
    "617769b3580c06c1d11826bb9662b8914681023db2daec33fc785dfece326f86": {
        "name": "conFrituur",
        "access": "WRITE_ONLY"
    },
    "d11e18f4c73471125562888e19bb28a677b098107c3db6beffb0c594fce7bdb1": {
        "name": "OPNsense",
        "access": "READ_ONLY"
    }
}
```

Access can either be `WRITE_ONLY` for the knockers, or `READ_ONLY` for OPNsense.

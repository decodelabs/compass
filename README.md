# Compass

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/compass?style=flat)](https://packagist.org/packages/decodelabs/compass)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/compass.svg?style=flat)](https://packagist.org/packages/decodelabs/compass)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/compass.svg?style=flat)](https://packagist.org/packages/decodelabs/compass)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/compass/integrate.yml?branch=develop)](https://github.com/decodelabs/compass/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/compass?style=flat)](https://packagist.org/packages/decodelabs/compass)

### Parse, inspect and test IP addresses

Compass provides an accurate disassembly of both v4 and v6 IP addresses and the means to inspect and compare them.

_Get news and updates on the [DecodeLabs blog](https://blog.decodelabs.com)._

---

## Installation

Install via Composer:

```bash
composer require decodelabs/compass
```

## Usage

Parse IP strings, integers and binaries and inspect them:

```php
use DecodeLabs\Compass\Ip;

$ip = Ip::parse('127.0.0.1');

if($ip->isV4()) {
    // Do something
}

if($ip->isLoopback()) {
    // Do something else
}

$v6Hybrid = $ip->toV6(); // ::ffff:127.0.0.1

$v6 = Ip::parse('fe80:0:0:0:202:b3ff:fe1e:8329');

if($ip->isV6()) {
    // The future
}
```

Check to see if an IP is within a range:

```php
if($ip->isIn('127.0.0.0/8')) {} // CIDR
if($ip->isIn('127.0.0.0/255.0.0.0')) {} // Netmask
if($ip->isIn('127.0.0.4-127.0.0.10')) {} // Range
if($ip->isIn('127.0.0.4+6')) {} // Relative range
if($ip->isIn('127.0.0.*')) {} // Wildcards

if($v6->isIn('fe80:0:0:0:202:b3ff:fe1e:0/128')) {} // CIDR
if($v6->isIn('fe80:0:0:0:202:b3ff:fe1e:0-fe80:0:0:0:202:b3ff:fe1e:ffff')) {} // Range
if($v6->isIn('fe80:0:0:0:202:b3ff:fe1e:0+9999')) {} // Relative range
if($v6->isIn('fe80:0:0:0:202:b3ff:fe1e:*')) {} // Wildcards
```


## Licensing
Compass is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.

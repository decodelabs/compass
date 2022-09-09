<?php

/**
 * @package Compass
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Compass;

interface V6Blocks
{
    // Private
    public const PRIVATE = [
        'fc00::/7',
        '2001:2::/48',
        '100::/64',
        '64:ff9b:1::/48'
    ];



    // Reserved
    public const RESERVED = [
        '::/128',
        '::1/128',
        '::ffff:0:0/96',
        '64:ff9b::/96',
        '64:ff9b:1::/48',
        '100::/64',
        '2001::/23',
        '2001::/32',
        '2001:1::1/128',
        '2001:1::2/128',
        '2001:2::/48',
        '2001:3::/32',
        '2001:4:112::/48',
        '2001:10::/28',
        '2001:20::/28',
        '2001:db8::/32',
        '2002::/16',
        '2620:4f:8000::/48',
        'fc00::/7',
        'fe80::/10',
    ];


    // Loopback
    public const LOOPBACK = '::1/128';


    // Link Local
    public const LINK_LOCAL = 'fe80::/10';
}

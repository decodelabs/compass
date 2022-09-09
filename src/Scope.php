<?php

/**
 * @package Compass
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Compass;

use Brick\Math\BigInteger;

interface Scope
{
    /**
     * Does the range contain the IP or Scope
     */
    public function contains(
        Ip|Scope|string|int|BigInteger $scope
    ): bool;

    /**
     * Does the range contain any part of Scope
     */
    public function overlaps(
        Ip|Scope|string|int|BigInteger $scope
    ): bool;

    public function getFirstIp(): Ip;
    public function getLastIp(): Ip;

    public function isV4(): bool;
    public function isV6(): bool;
}

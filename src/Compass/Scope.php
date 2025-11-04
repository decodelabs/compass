<?php

/**
 * Compass
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Compass;

use Brick\Math\BigInteger;
use Stringable;

interface Scope extends Stringable
{
    public Ip $firstIp { get; }
    public Ip $lastIp { get; }

    public function contains(
        Ip|Scope|string|int|BigInteger $scope
    ): bool;

    public function overlaps(
        Ip|Scope|string|int|BigInteger $scope
    ): bool;

    public function isV4(): bool;
    public function isV6(): bool;
}

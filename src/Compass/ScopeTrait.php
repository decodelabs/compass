<?php

/**
 * Compass
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Compass;

use Brick\Math\BigInteger;

/**
 * @phpstan-require-implements Scope
 */
trait ScopeTrait
{
    public function contains(
        Ip|Scope|string|int|BigInteger $scope
    ): bool {
        $range = Range::parse($scope);

        return
            $this->firstIp->isLessThanOrEqualTo(
                $range->firstIp
            ) &&
            $this->lastIp->isGreaterThanOrEqualTo(
                $range->lastIp
            );
    }

    public function overlaps(
        Ip|Scope|string|int|BigInteger $scope
    ): bool {
        $range = Range::parse($scope);

        return !(
            $this->lastIp->isLessThan(
                $range->firstIp
            ) ||
            $this->firstIp->isGreaterThan(
                $range->lastIp
            )
        );
    }

    public function isV4(): bool
    {
        return $this->firstIp->isV4();
    }

    public function isV6(): bool
    {
        return $this->firstIp->isV6();
    }
}

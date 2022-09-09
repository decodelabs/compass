<?php

/**
 * @package Compass
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Compass;

use Brick\Math\BigInteger;

trait ScopeTrait
{
    public function contains(
        Ip|Scope|string|int|BigInteger $scope
    ): bool {
        $range = Range::parse($scope);

        return
            $this->getFirstIp()->isLessThanOrEqualTo(
                $range->getFirstIp()
            ) &&
            $this->getLastIp()->isGreaterThanOrEqualTo(
                $range->getLastIp()
            );
    }

    public function overlaps(
        Ip|Scope|string|int|BigInteger $scope
    ): bool {
        $range = Range::parse($scope);

        return !(
            $this->getLastIp()->isLessThan(
                $range->getFirstIp()
            ) ||
            $this->getFirstIp()->isGreaterThan(
                $range->getLastIp()
            )
        );
    }
}

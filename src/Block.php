<?php

/**
 * @package Compass
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Compass;

use Brick\Math\BigInteger;
use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;
use Stringable;

class Block implements
    Scope,
    Stringable,
    Dumpable
{
    use ScopeTrait;

    protected Ip $givenIp;
    protected Ip $firstIp;
    protected Ip $lastIp;

    protected int $prefixLength;
    protected ?Ip $netmask = null;
    protected ?Ip $delta = null;

    protected ?BigInteger $maxAddresses = null;


    /**
     * Parse Block or null
     */
    public static function parse(
        Ip|Block|string $block
    ): Block {
        if ($block instanceof Block) {
            return $block;
        }

        return new self($block);
    }

    /**
     * Init with blcok def
     */
    public function __construct(
        Ip|string $block
    ) {
        $prefixLength = 0;

        if (is_string($block)) {
            if (false !== strpos($block, '/')) {
                list($block, $prefixLength) = explode('/', $block, 2);
            }


            $block = Ip::parse($block);
        }

        $this->givenIp = $block;
        $this->prefixLength = $this->normalizePrefixLength($prefixLength);

        $this->firstIp = $this->givenIp->and($this->getNetmask());
        $this->lastIp = $this->firstIp->or($this->getDelta());
    }


    /**
     * Ensure prefix length is valid
     */
    protected function normalizePrefixLength(
        int|string $prefix
    ): int {
        if ($prefix === '') {
            $prefix = 0;
        }

        // Check validity
        if ($this->givenIp->isV4()) {
            if (
                is_string($prefix) &&
                isset(V4Blocks::NETMASK_PREFIXES[$prefix])
            ) {
                $prefix = V4Blocks::NETMASK_PREFIXES[$prefix];
            }

            if (!is_numeric($prefix)) {
                throw Exceptional::InvalidArgument(
                    'Prefix length is invalid: ' . $prefix
                );
            }
        }

        $prefix = (int)$prefix;

        if (
            $prefix < 0 ||
            $prefix > $this->givenIp->getBits()
        ) {
            throw Exceptional::OutOfBounds(
                'Prefix length is out of bounds: ' . $prefix
            );
        }

        return $prefix;
    }


    public function getFirstIp(): Ip
    {
        return $this->firstIp;
    }

    public function getLastIp(): Ip
    {
        return $this->lastIp;
    }


    /**
     * Get netmask
     */
    public function getNetmask(): Ip
    {
        if ($this->netmask === null) {
            if ($this->prefixLength === 0) {
                $this->netmask = new Ip(0);
            } else {
                $max = $this->givenIp->getMax();
                $bits = $this->givenIp->getBits();

                $this->netmask = new Ip(
                    $max->shiftedLeft($bits - $this->prefixLength)
                        ->and($max),
                    $this->givenIp->isV6() ? true : null
                );
            }
        }

        return $this->netmask;
    }


    /**
     * Get delta
     */
    public function getDelta(): Ip
    {
        if ($this->delta === null) {
            if ($this->prefixLength === 0) {
                $this->delta = new Ip($this->givenIp->getMax());
            } else {
                $bits = $this->givenIp->getBits();

                $this->delta = new Ip(
                    BigInteger::of(1)->shiftedLeft($bits - $this->prefixLength)
                        ->minus(1),
                    $this->givenIp->isV6() ? true : null
                );
            }
        }

        return $this->delta;
    }



    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->firstIp . '/' . $this->prefixLength;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->__toString();

        yield 'properties' => [
            'firstIp' => $this->firstIp,
            'lastIp' => $this->lastIp,
            'netmask' => $this->netmask,
            'delta' => $this->delta
        ];
    }
}

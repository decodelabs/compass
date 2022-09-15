<?php

/**
 * @package Compass
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Compass;

use Brick\Math\BigInteger;
use DecodeLabs\Exceptional;
use DecodeLabs\Fluidity\SingleParameterFactory;
use DecodeLabs\Fluidity\SingleParameterFactoryTrait;
use DecodeLabs\Glitch\Dumpable;

/**
 * @implements SingleParameterFactory<Ip|Block|string>
 */
class Block implements
    SingleParameterFactory,
    Scope,
    Dumpable
{
    /**
     * @use SingleParameterFactoryTrait<Ip|Block|string>
     */
    use SingleParameterFactoryTrait;

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
    ): static {
        if ($block instanceof static) {
            return $block;
        }

        return new static($block);
    }

    /**
     * Init with blcok def
     *
     * @param Ip|Block|string $block
     */
    public function __construct(mixed $block)
    {
        $prefixLength = 0;

        // Reparse block
        if ($block instanceof Block) {
            $block = (string)$block;
        }

        // Split string
        if (is_string($block)) {
            if (false !== strpos($block, '/')) {
                list($block, $prefixLength) = explode('/', $block, 2);
            }

            $block = Ip::parse($block);
        }

        // Check block
        if (!$block instanceof Ip) {
            throw Exceptional::InvalidArgument(
                'Block must contain an IP',
                null,
                $block
            );
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

    public function isV4(): bool
    {
        return $this->givenIp->isV4();
    }

    public function isV6(): bool
    {
        return $this->givenIp->isV6();
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

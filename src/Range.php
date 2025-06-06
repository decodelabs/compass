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
use DecodeLabs\Nuance\Dumpable;
use DecodeLabs\Nuance\Entity\NativeObject as NuanceEntity;

/**
 * @implements SingleParameterFactory<Ip|Scope|string|int|BigInteger>
 */
class Range implements
    SingleParameterFactory,
    Scope,
    Dumpable
{
    /**
     * @use SingleParameterFactoryTrait<Ip|Scope|string|int|BigInteger>
     */
    use SingleParameterFactoryTrait;

    use ScopeTrait;

    public protected(set) Ip $firstIp;
    public protected(set) Ip $lastIp;

    protected ?Scope $originalScope = null;

    /**
     * Parse input value to Range
     */
    public static function parse(
        Ip|Scope|string|int|BigInteger $range
    ): static {
        if ($range instanceof static) {
            return $range;
        }

        return new static($range);
    }

    /**
     * Init with input value
     *
     * @param Ip|Scope|string|int|BigInteger $range
     */
    public function __construct(
        mixed $range
    ) {
        // Single IP
        if (
            is_int($range) ||
            $range instanceof BigInteger
        ) {
            $range = Ip::parse($range);
        }

        if ($range instanceof Ip) {
            $this->setRange(
                $range,
                $range
            );
            return;
        }


        // CIDR
        if (
            is_string($range) &&
            false !== strpos($range, '/')
        ) {
            $range = Block::parse($range);
        }


        // Scope
        if ($range instanceof Scope) {
            $this->setRange(
                $range->firstIp,
                $range->lastIp,
                $range
            );
            return;
        }


        // Range string
        if (false !== strpos($range, '-')) {
            $this->parseRange($range);
            return;
        }


        // Plus string
        if (false !== strpos($range, '+')) {
            $this->parsePlus($range);
            return;
        }


        // Wildcards
        if (false !== strpos($range, '*')) {
            $this->parseWildcards($range);
            return;
        }


        // Single IP string
        $ip = Ip::parse($range);
        $this->setRange($ip, $ip);
        return;
    }


    protected function setRange(
        Ip $start,
        Ip $end,
        ?Scope $originalScope = null
    ): void {
        $this->firstIp = $start;
        $this->lastIp = $end;
        $this->originalScope = $originalScope;

        if ($this->firstIp->isGreaterThan($this->lastIp)) {
            throw Exceptional::UnexpectedValue(
                message: 'Start IP is higher than range end IP',
                data: $this
            );
        }
    }


    /**
     * Parse hyphenated range
     */
    protected function parseRange(
        string $range
    ): void {
        $parts = explode('-', $range, 2);

        $this->setRange(
            Ip::parse($parts[0]),
            Ip::parse($parts[1])
        );
    }


    /**
     * Parse + range
     */
    protected function parsePlus(
        string $range
    ): void {
        $parts = explode('+', $range, 2);
        $start = Ip::parse($parts[0]);
        $end = $start->plus($parts[1]);

        $this->setRange($start, $end);
    }



    /**
     * Parse V4 range
     */
    protected function parseWildcards(
        string $range
    ): void {
        $v4Parts = $v6Parts = $start4 = $start6 = $end4 = $end6 = [];

        if (false !== strpos($range, '.')) {
            $v4Parts = explode('.', $range);
        }

        if (false !== ($pos = strrpos($v4Parts[0] ?? '', ':'))) {
            $v6 = substr($v4Parts[0], 0, $pos);
            $v4Parts[0] = substr($v4Parts[0], $pos + 1);
            $v6Parts = explode(':', $v6);
        } elseif (false !== strpos($range, ':')) {
            $v6Parts = explode(':', $range);
        }

        foreach ($v6Parts as $part) {
            $start6[] = $this->parseWildcardPart($range, $part, true, false);
            $end6[] = $this->parseWildcardPart($range, $part, true, true);
        }

        foreach ($v4Parts as $part) {
            $start4[] = $this->parseWildcardPart($range, $part, false, false);
            $end4[] = $this->parseWildcardPart($range, $part, false, true);
        }

        $this->firstIp = Ip::parse($this->reconstructIp($start6, $start4));
        $this->lastIp = Ip::parse($this->reconstructIp($end6, $end4));
    }

    protected function parseWildcardPart(
        string $range,
        string $part,
        bool $v6,
        bool $high
    ): string {
        if ($part === '') {
            return '';
        }

        if (
            false !== strpos($part, '*') &&
            $part !== '*'
        ) {
            throw Exceptional::InvalidArgument(
                message: 'Invalid wildcard range: ' . $range
            );
        }

        if ($part !== '*') {
            return $part;
        }

        if ($v6) {
            return $high ? 'ffff' : '0000';
        } else {
            return $high ? '255' : '0';
        }
    }

    /**
     * @param array<string> $v4
     * @param array<string> $v6
     */
    protected function reconstructIp(
        array $v6,
        array $v4
    ): string {
        $output = '';

        if (!empty($v6)) {
            $output .= implode(':', $v6);
        }

        if (!empty($v4)) {
            if (!empty($output)) {
                $output .= ':';
            }

            $output .= implode('.', $v4);
        }

        return $output;
    }




    public function __toString(): string
    {
        if ($this->originalScope !== null) {
            return $this->originalScope->__toString();
        }

        return $this->firstIp . '-' . $this->lastIp;
    }


    public function toNuanceEntity(): NuanceEntity
    {
        $entity = new NuanceEntity($this);
        $entity->text = $this->__toString();
        return $entity;
    }
}

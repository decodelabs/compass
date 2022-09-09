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

class Range implements
    Scope,
    Stringable,
    Dumpable
{
    use ScopeTrait;

    protected Ip $start;
    protected Ip $end;

    /**
     * Parse input value to Range
     */
    public static function parse(
        Ip|Scope|string|int|BigInteger $range
    ): Range {
        if ($range instanceof Range) {
            return $range;
        }

        return new self($range);
    }

    /**
     * Init with input value
     */
    public function __construct(
        Ip|Scope|string|int|BigInteger $range
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


        // Scope
        if ($range instanceof Scope) {
            $this->setRange(
                $range->getFirstIp(),
                $range->getLastIp()
            );
            return;
        }


        // CIDR
        if (false !== strpos($range, '/')) {
            $block = Block::parse($range);

            $this->setRange(
                $block->getFirstIp(),
                $block->getLastIp()
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
        Ip $end
    ): void {
        $this->start = $start;
        $this->end = $end;

        if ($this->start->isGreaterThan($this->end)) {
            throw Exceptional::UnexpectedValue(
                'Start IP is higher than range end IP',
                null,
                $this
            );
        }
    }


    /**
     * Parse hyphenated range
     */
    protected function parseRange(string $range): void
    {
        $parts = explode('-', $range, 2);

        $this->setRange(
            Ip::parse($parts[0]),
            Ip::parse($parts[1])
        );
    }


    /**
     * Parse + range
     */
    protected function parsePlus(string $range): void
    {
        $parts = explode('+', $range, 2);

        $this->setRange(
            $start = Ip::parse($parts[0]),
            $start->plus($parts[1])
        );
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

        $this->start = Ip::parse($this->reconstructIp($start6, $start4));
        $this->end = Ip::parse($this->reconstructIp($end6, $end4));
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
                'Invalid wildcard range: ' . $range
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


    public function getFirstIp(): Ip
    {
        return $this->start;
    }

    public function getLastIp(): Ip
    {
        return $this->end;
    }




    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->start . '-' . $this->end;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->__toString();
    }
}

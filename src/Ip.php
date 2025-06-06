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
use Stringable;

/**
 * @implements SingleParameterFactory<Ip|string|int|BigInteger>
 */
class Ip implements
    SingleParameterFactory,
    Stringable,
    Dumpable
{
    /**
     * @use SingleParameterFactoryTrait<Ip|string|int|BigInteger>
     */
    use SingleParameterFactoryTrait;

    protected const V4Max = '4294967295';
    protected const V4Size = 32 / 4;
    protected const V4Bits = 32;

    protected const V6Max = '340282366920938463463374607431768211455';
    protected const V6Size = 32;
    protected const V6Bits = 128;

    protected BigInteger $ip;

    protected bool $v4 = false;
    protected bool $v6 = false;

    protected ?bool $private = null;
    protected ?bool $reserved = null;
    protected ?bool $linkLocal = null;
    protected ?bool $loopback = null;

    protected ?string $string = null;


    public int $version {
        get => $this->getVersion();
    }

    public BigInteger $max {
        get => $this->getMax();
    }

    public int $size {
        get => $this->getSize();
    }

    public int $bits {
        get => $this->getBits();
    }



    /**
     * Parse IP or return null
     */
    public static function parse(
        Ip|string|int|BigInteger $ip,
        ?bool $isV6 = null
    ): static {
        if ($ip instanceof static) {
            return $ip;
        }

        return new static($ip, $isV6);
    }

    /**
     * Validate IP or return null
     */
    public static function isValid(
        Ip|string|int|BigInteger|null $ip,
    ): bool {
        if ($ip === null) {
            return false;
        }

        if ($ip instanceof Ip) {
            return true;
        }

        if (is_string($ip)) {
            return filter_var($ip, FILTER_VALIDATE_IP) !== false;
        }

        if (is_int($ip)) {
            $ip = BigInteger::of($ip);
        }

        return
            !$ip->isLessThan(0) &&
            !$ip->isGreaterThan(self::V6Max);
    }

    /**
     * Init with IP string or int
     *
     * @param Ip|string|int|BigInteger $ip
     */
    public function __construct(
        mixed $ip,
        ?bool $isV6 = null
    ) {
        // Ip
        if ($ip instanceof Ip) {
            $ip = (string)$ip;
        }

        // Number
        if (is_int($ip)) {
            $ip = BigInteger::of($ip);
        }

        // String
        elseif (is_string($ip)) {
            $ip = $this->parseString($ip);
        }


        // V6
        if (
            $isV6 ||
            $ip->isGreaterThan(self::V4Max)
        ) {
            $this->v6 = true;
        }


        // Bounds
        $this->testBounds($ip);
        $this->ip = $ip;
    }


    /**
     * Convert string representation to BigInteger
     */
    protected function parseString(
        string $ip
    ): BigInteger {
        // Number
        if (ctype_digit($ip)) {
            return BigInteger::of($ip);
        }


        // Check for separators
        $hasV4 = strpos($ip, '.') > 0;
        $hasV6 = $this->v6 = strpos($ip, ':') !== false;


        // Binary
        if (
            !$hasV4 &&
            !$hasV6
        ) {
            if (false === ($hex = unpack('H*hex', $ip))) {
                throw Exceptional::Runtime(
                    message: 'Unable to unpack hex: ' . $ip
                );
            }

            /** @var array{ hex: string } $hex */
            return BigInteger::fromBase($hex['hex'], 16);
        }


        // IPv6 with IPv4 compat - strip the compat
        $ipV4 = null;

        if (
            $hasV4 &&
            $hasV6
        ) {
            $ipV4 = substr($ip, strrpos($ip, ':') + 1);
            $hasV6 = false;
        } elseif ($hasV4) {
            $ipV4 = $ip;
        }


        $in = $ip;

        // V4
        if ($hasV4) {
            // Check blocks
            $ipV4 = array_pad(explode('.', (string)$ipV4), 4, 0);

            if (count($ipV4) > 4) {
                throw Exceptional::InvalidArgument(
                    $in . ' is not a valid IPv4 address'
                );
            }

            for ($i = 0; $i < 4; $i++) {
                if ($ipV4[$i] > 255) {
                    throw Exceptional::InvalidArgument(
                        $in . ' is not a valid IPv4 address'
                    );
                }
            }
        }

        // V6
        if ($hasV6) {
            $ip = strtolower($ip);
            $ip = (string)preg_replace('/::(:+)/', '::', $ip);
        }


        // Convert to bin
        if (false === ($bin = inet_pton($ip))) {
            throw Exceptional::InvalidArgument(
                message: 'Could not parse IPV6 address: ' . $in
            );
        }

        // To hex
        if (false === ($hex = unpack("H*hex", $bin))) {
            throw Exceptional::Runtime(
                message: 'Unable to unpack hex: ' . $bin
            );
        }

        /** @var array{ hex: string } $hex */
        return BigInteger::fromBase($hex['hex'], 16);
    }


    /**
     * Check address is within valid bounds
     */
    protected function testBounds(
        BigInteger $ip
    ): void {
        if ($ip->isLessThan(0)) {
            throw Exceptional::InvalidArgument(
                message: 'IP integer value cannot be less than zero: ' . $ip
            );
        } elseif ($ip->isGreaterThan(self::V6Max)) {
            throw Exceptional::InvalidArgument(
                message: 'IP integer value is outside the V6 range: ' . $ip
            );
        }
    }



    /**
     * Get stack version
     */
    public function getVersion(): int
    {
        return $this->v6 ? 6 : 4;
    }

    /**
     * Set version
     */
    public function toVersion(
        int $version
    ): static {
        if ($version === 4) {
            return $this->toV4();
        } elseif ($version === 6) {
            return $this->toV6();
        }

        throw Exceptional::InvalidArgument(
            message: 'Unrecognised version: ' . $version
        );
    }


    /**
     * Get max integer size
     */
    public function getMax(): BigInteger
    {
        return BigInteger::of(
            $this->v6 ? self::V6Max : self::V4Max
        );
    }


    /**
     * Get padding size
     */
    public function getSize(): int
    {
        return $this->v6 ? self::V6Size : self::V4Size;
    }


    /**
     * Get bits
     */
    public function getBits(): int
    {
        return $this->v6 ? self::V6Bits : self::V4Bits;
    }



    /**
     * Is in V4 stack
     */
    public function isV4(): bool
    {
        return !$this->v6;
    }

    /**
     * Is in V4 range (either stack)
     */
    public function isV4Range(): bool
    {
        return
            !$this->v6 ||
            false !== strpos($this->__toString(), '.');
    }


    /**
     * Ensure address is in V4 stack if possible
     */
    public function toV4(): static
    {
        if (!$this->v6) {
            return $this;
        }

        if (!$this->isV4Range()) {
            throw Exceptional::OutOfBounds(
                message: 'Unable to convert IPV6 to IPV4, address is out of V4 range'
            );
        }

        $ip = $this->__toString();
        return static::parse(substr($ip, strrpos($ip, ':') + 1));
    }


    /**
     * Is in V6 stack
     */
    public function isV6(): bool
    {
        return $this->v6;
    }


    /**
     * Ensure address is in V6 stack
     */
    public function toV6(): static
    {
        if ($this->v6) {
            return $this;
        }

        return static::parse('::ffff:' . $this->__toString());
    }



    /**
     * Get numeric representation
     */
    public function toNumber(): BigInteger
    {
        return $this->ip;
    }


    /**
     * Get binary representation
     */
    public function toBinary(): string
    {
        return pack('H*', str_pad(
            $this->ip->toBase(16),
            $this->getSize(),
            '0',
            \STR_PAD_LEFT
        ));
    }



    /**
     * Bitwise and
     */
    public function and(
        Ip|string|int|BigInteger $that
    ): static {
        return static::parse(
            $this->ip->and(
                static::parse($that)->ip
            ),
            $this->v6 ? true : null
        );
    }

    /**
     * Bitwise or
     */
    public function or(
        Ip|string|int|BigInteger $that
    ): static {
        return static::parse(
            $this->ip->or(
                static::parse($that)->ip
            ),
            $this->v6 ? true : null
        );
    }

    /**
     * Bitwise xor
     */
    public function xor(
        Ip|string|int|BigInteger $that
    ): static {
        return static::parse(
            $this->ip->xor(
                static::parse($that)->ip
            ),
            $this->v6 ? true : null
        );
    }

    /**
     * Bitwise negate
     */
    public function negate(): static
    {
        return static::parse(
            $this->ip->not()->and(
                $this->getMax()
            ),
            $this->v6 ? true : null
        );
    }


    /**
     * Bitwise match comparison
     */
    public function matches(
        Ip|string|int|BigInteger $that,
        Ip|string|int|BigInteger $mask = 0
    ): bool {
        $that = static::parse($that);
        $mask = static::parse($mask);

        $value = $this->xor($that)->negate()->and($mask->negate())->or($mask);
        return $value->ip->compareTo($this->getMax()) === 0;
    }


    /**
     * Compare two IPs
     */
    public function compare(
        Ip|string|int|BigInteger $that
    ): int {
        $that = static::parse($that);
        return $this->ip->compareTo($that->ip);
    }

    public function isEqualTo(
        Ip|string|int|BigInteger $that
    ): bool {
        return $this->compare($that) === 0;
    }

    public function isLessThan(
        Ip|string|int|BigInteger $that
    ): bool {
        return $this->compare($that) < 0;
    }

    public function isLessThanOrEqualTo(
        Ip|string|int|BigInteger $that
    ): bool {
        return $this->compare($that) <= 0;
    }

    public function isGreaterThan(
        Ip|string|int|BigInteger $that
    ): bool {
        return $this->compare($that) > 0;
    }

    public function isGreaterThanOrEqualTo(
        Ip|string|int|BigInteger $that
    ): bool {
        return $this->compare($that) >= 0;
    }



    /**
     * Add value to current
     */
    public function plus(
        Ip|string|int|BigInteger $value
    ): static {
        if (is_int($value)) {
            $value = BigInteger::of($value);
        }

        if (
            $value instanceof BigInteger &&
            $value->isNegative()
        ) {
            return $this->minus($value->negated());
        }

        if ($value == 0) {
            return static::parse($this->ip);
        }

        $value = static::parse($value);

        return static::parse(
            $this->ip->plus($value->ip),
            $this->v6 ? true : null
        );
    }


    /**
     * Subtract value from current
     */
    public function minus(
        Ip|string|int|BigInteger $value
    ): static {
        if (is_int($value)) {
            $value = BigInteger::of($value);
        }

        if (
            $value instanceof BigInteger &&
            $value->isNegative()
        ) {
            return $this->plus($value->negated());
        }

        if ($value == 0) {
            return static::parse($this->ip);
        }

        $value = static::parse($value);

        return static::parse(
            $this->ip->minus($value->ip),
            $this->v6 ? true : null
        );
    }




    /**
     * Is in block range
     */
    public function isIn(
        Ip|Range|string|int|BigInteger $range
    ): bool {
        $range = Range::parse($range);
        return $range->contains($this);
    }



    /**
     * Is in private block
     */
    public function isPrivate(): bool
    {
        if ($this->private !== null) {
            return $this->private;
        }

        $this->private = false;

        foreach (
            $this->v6 ?
                V6Blocks::Private :
                V4Blocks::Private as $block
        ) {
            if ($this->isIn($block)) {
                $this->private = true;
                break;
            }
        }

        return $this->private;
    }


    /**
     * Is in reserved block
     */
    public function isReserved(): bool
    {
        if ($this->reserved !== null) {
            return $this->reserved;
        }

        $this->reserved = false;

        foreach (
            $this->v6 ?
                V6Blocks::Reserved :
                V4Blocks::Reserved as $block
        ) {
            if ($this->isIn($block)) {
                $this->reserved = true;
                break;
            }
        }

        return $this->reserved;
    }


    /**
     * Is in public range
     */
    public function isPublic(): bool
    {
        return !$this->isReserved();
    }


    /**
     * Is a link-local address
     */
    public function isLinkLocal(): bool
    {
        if ($this->linkLocal === null) {
            $this->linkLocal = $this->isIn(
                $this->v6 ?
                    V6Blocks::LinkLocal :
                    V4Blocks::LinkLocal
            );
        }

        return $this->linkLocal;
    }


    /**
     * Is a loopback address
     */
    public function isLoopback(): bool
    {
        if ($this->loopback === null) {
            $this->loopback = $this->isIn(
                $this->v6 ?
                    V6Blocks::Loopback :
                    V4Blocks::Loopback
            );
        }

        return $this->loopback;
    }



    /**
     * Convert to string
     */
    public function __toString(): string
    {
        if ($this->string !== null) {
            return $this->string;
        }

        return $this->string = (string)inet_ntop($this->toBinary());
    }



    public function toNuanceEntity(): NuanceEntity
    {
        $entity = new NuanceEntity($this);
        $entity->text = $this->__toString();

        $entity->meta['integer'] = (string)$this->ip;
        $entity->meta['v6'] = $this->v6;

        return $entity;
    }
}

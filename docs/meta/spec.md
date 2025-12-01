# Compass — Package Specification

> **Cluster:** `core`
> **Language:** `php`
> **Milestone:** `m3`
> **Repo:** `https://github.com/decodelabs/compass`
> **Role:** IP tools

This document describes the purpose, contracts, and design of **Compass** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Compass in their own applications or libraries.
- Contributors **maintaining or extending** Compass.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Compass provides accurate parsing, inspection, and testing of IPv4 and IPv6 addresses. It offers comprehensive IP address manipulation including version conversion, range checking, CIDR block operations, and special address detection (private, reserved, loopback, link-local). The package uses arbitrary-precision arithmetic (via Brick Math) to handle large IPv6 addresses accurately and provides integration with Lucid for validation and sanitization.

### 1.2 Non-Goals

Compass does **not**:

- Provide DNS resolution or hostname lookup — it only handles IP addresses
- Handle network routing or subnet calculations beyond basic CIDR operations
- Provide geolocation or IP-to-location mapping
- Handle MAC addresses or other network identifiers
- Provide network interface management
- Handle port numbers or TCP/UDP protocol details
- Provide network packet inspection or analysis

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `core` (see Chorus taxonomy)
- Compass is a core utility package that provides IP address handling capabilities for the Decode Labs ecosystem. It sits in the core cluster alongside other fundamental utilities. It depends on Exceptional, Fluidity, and Nuance, and integrates with Lucid for validation. It's used by packages like Singularity, Harvest, and Scrutiny for IP address parsing and validation.

### 2.2 Typical Usage Contexts

Typical places Compass appears:

- HTTP request IP address parsing and validation
- Access control and IP whitelisting/blacklisting
- Network configuration and routing
- Security and firewall rule evaluation
- API rate limiting based on IP ranges
- Logging and analytics (IP address normalization)
- Form validation for IP address inputs

Compass is intended to be used whenever code needs to parse, validate, compare, or test IP addresses, especially when accuracy and support for both IPv4 and IPv6 are required.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Compass\Ip`
  Main IP address class. Implements `SingleParameterFactory`, `Stringable`, and `Dumpable`. Handles parsing, conversion, comparison, and inspection of IP addresses. Supports both IPv4 and IPv6.

- `DecodeLabs\Compass\Range`
  IP address range representation. Implements `SingleParameterFactory`, `Scope`, and `Dumpable`. Represents a range of IP addresses with start and end points. Supports parsing from various formats (CIDR, dash notation, plus notation, wildcards).

- `DecodeLabs\Compass\Block`
  CIDR block representation. Implements `SingleParameterFactory`, `Scope`, and `Dumpable`. Represents a CIDR block with netmask and prefix length calculations.

- `DecodeLabs\Compass\Scope`
  Interface for IP address scopes (ranges or blocks). Defines methods for checking containment and overlap.

- `DecodeLabs\Compass\V4Blocks`
  Interface containing predefined IPv4 address blocks (Private, Reserved, LinkLocal, Loopback, Multicast, etc.).

- `DecodeLabs\Compass\V6Blocks`
  Interface containing predefined IPv6 address blocks (Private, Reserved, LinkLocal, Loopback, Multicast, etc.).

- `DecodeLabs\Lucid\Processor\Ip`
  Lucid processor for coercing values to IP addresses.

- `DecodeLabs\Lucid\Processor\IpRange`
  Lucid processor for coercing values to IP ranges.

- `DecodeLabs\Lucid\Constraint\Compass\V4`
  Lucid constraint for validating IPv4 addresses.

- `DecodeLabs\Lucid\Constraint\Compass\V6`
  Lucid constraint for validating IPv6 addresses.

- `DecodeLabs\Lucid\Constraint\Compass\Range`
  Lucid constraint for validating IP addresses within a range.

- `DecodeLabs\Lucid\Constraint\Compass\Min`
  Lucid constraint for validating minimum IP address.

- `DecodeLabs\Lucid\Constraint\Compass\Max`
  Lucid constraint for validating maximum IP address.

### 3.2 Main Entry Points

The main usage pattern is through static factory methods:

```php
use DecodeLabs\Compass\Ip;
use DecodeLabs\Compass\Range;

$ip = Ip::parse('127.0.0.1');
$range = Range::parse('127.0.0.0/8');
$block = Block::parse('192.168.0.0/24');
```

---

## 4. Dependencies

### 4.1 Decode Labs

- `decodelabs/exceptional` (required)
  Used for exception handling when IP parsing or operations fail.

- `decodelabs/fluidity` (required)
  Used for fluent interface support via `SingleParameterFactory`.

- `decodelabs/nuance` (required)
  Used for debugging and inspection via `Dumpable` interface.

### 4.2 External

- `brick/math` (required)
  Used for arbitrary-precision arithmetic to handle large IPv6 addresses accurately. Required because IPv6 addresses exceed PHP's native integer range.

### 4.3 Optional Integrations

- `decodelabs/lucid` (optional, dev dependency)
  Detected at runtime if installed, used for IP address validation and sanitization. Compass provides Lucid processors and constraints for integration.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- IP addresses are stored internally as `BigInteger` for accuracy
- IPv4 addresses are represented as 32-bit integers (0-4294967295)
- IPv6 addresses are represented as 128-bit integers (0-340282366920938463463374607431768211455)
- IP addresses are immutable — operations return new instances
- Version (v4/v6) is determined automatically or can be explicitly set
- All IP addresses can be converted to strings via `__toString()`
- Range operations validate that start IP is not greater than end IP
- CIDR blocks calculate netmask and delta automatically from prefix length

### 5.2 Input & Output Contracts

**Ip Operations:**
- `parse(Ip|string|int|BigInteger $ip, ?bool $isV6): static` — Parses IP from various formats
- `isValid(Ip|string|int|BigInteger|null $ip): bool` — Validates IP format
- `getVersion(): int` — Returns IP version (4 or 6)
- `isV4(): bool` — Checks if IPv4
- `isV6(): bool` — Checks if IPv6
- `toV4(): static` — Converts to IPv4 (if in v4 range)
- `toV6(): static` — Converts to IPv6 (hybrid format for v4)
- `toNumber(): BigInteger` — Returns numeric representation
- `toBinary(): string` — Returns binary representation
- `isIn(Ip|Range|string|int|BigInteger $range): bool` — Checks if IP is in range
- `isPrivate(): bool` — Checks if private address
- `isReserved(): bool` — Checks if reserved address
- `isPublic(): bool` — Checks if public address
- `isLinkLocal(): bool` — Checks if link-local address
- `isLoopback(): bool` — Checks if loopback address
- `compare(Ip|string|int|BigInteger $that): int` — Compares IPs (-1, 0, 1)
- `isEqualTo`, `isLessThan`, `isGreaterThan`, etc. — Comparison methods
- `and`, `or`, `xor`, `negate` — Bitwise operations
- `plus`, `minus` — Arithmetic operations
- `matches(Ip $that, Ip $mask): bool` — Checks if IP matches with mask

**Range Operations:**
- `parse(Ip|Scope|string|int|BigInteger $range): static` — Parses range from various formats
- `contains(Ip|Scope|string|int|BigInteger $scope): bool` — Checks if range contains scope
- `overlaps(Ip|Scope|string|int|BigInteger $scope): bool` — Checks if ranges overlap
- Supports parsing from:
  - CIDR notation: `'127.0.0.0/8'`
  - Dash notation: `'127.0.0.4-127.0.0.10'`
  - Plus notation: `'127.0.0.4+6'` (relative range)
  - Wildcards: `'127.0.0.*'`
  - Netmask: `'127.0.0.0/255.0.0.0'`

**Block Operations:**
- `parse(Ip|Block|string $block): static` — Parses CIDR block
- `getNetmask(): Ip` — Returns netmask IP
- `getDelta(): Ip` — Returns delta (host bits)
- `contains(Ip|Scope|string|int|BigInteger $scope): bool` — Checks if block contains scope
- `overlaps(Ip|Scope|string|int|BigInteger $scope): bool` — Checks if blocks overlap

### 5.3 Special Address Detection

Compass provides predefined blocks for common address types:
- **Private**: RFC 1918 addresses (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, etc.)
- **Reserved**: IANA reserved addresses
- **Link-Local**: Auto-configured addresses (169.254.0.0/16 for v4, fe80::/10 for v6)
- **Loopback**: Localhost addresses (127.0.0.0/8 for v4, ::1/128 for v6)
- **Multicast**: Multicast addresses

### 5.4 Range Format Support

Range parsing supports multiple formats:
- **CIDR**: `'192.168.0.0/24'` or `'fe80::/10'`
- **Netmask**: `'192.168.0.0/255.255.255.0'` (IPv4 only)
- **Dash Range**: `'192.168.0.1-192.168.0.100'`
- **Plus Range**: `'192.168.0.1+99'` (start IP + count)
- **Wildcards**: `'192.168.0.*'` or `'fe80::*'`

---

## 6. Error Handling

- Invalid IP addresses throw `Exceptional::InvalidArgument` during parsing
- Out-of-bounds IP values throw `Exceptional::InvalidArgument`
- Invalid prefix lengths throw `Exceptional::OutOfBounds`
- Invalid range formats throw `Exceptional::InvalidArgument`
- Range validation throws `Exceptional::UnexpectedValue` if start > end
- V6-to-V4 conversion throws `Exceptional::OutOfBounds` if address is out of v4 range
- `isValid()` returns `false` for invalid inputs (does not throw)
- Lucid processors throw `Exceptional::UnexpectedValue` for uncoercible values

---

## 7. Configuration & Extensibility

- Compass is not configurable — behaviour is fixed
- Predefined address blocks are defined in `V4Blocks` and `V6Blocks` interfaces
- Custom address blocks can be created by implementing `Scope` interface
- Lucid integration is provided via processors and constraints
- No extension points for custom IP formats — standard formats only

---

## 8. Interactions with Other Packages

### 8.1 Exceptional

Compass uses Exceptional for all exception handling, providing consistent error reporting across the ecosystem.

### 8.2 Fluidity

Compass implements Fluidity's `SingleParameterFactory` interface, enabling fluent object creation from various input types.

### 8.3 Nuance

Compass implements Nuance's `Dumpable` interface, allowing IP addresses to be inspected and debugged using Nuance's debugging tools.

### 8.4 Lucid

Compass provides Lucid processors (`Ip`, `IpRange`) and constraints (`V4`, `V6`, `Range`, `Min`, `Max`) for integration with Lucid's validation and sanitization pipeline. This allows IP addresses to be validated and coerced in Lucid contexts.

### 8.5 Brick Math

Compass uses Brick Math's `BigInteger` for accurate representation of IPv6 addresses, which exceed PHP's native integer range.

---

## 9. Usage Examples

### 9.1 Basic IP Parsing

```php
use DecodeLabs\Compass\Ip;

$ip = Ip::parse('127.0.0.1');
$ip = Ip::parse(2130706433); // Integer
$ip = Ip::parse('fe80::202:b3ff:fe1e:8329'); // IPv6

if ($ip->isV4()) {
    // IPv4 specific operations
}

if ($ip->isLoopback()) {
    // Localhost
}
```

### 9.2 Version Conversion

```php
use DecodeLabs\Compass\Ip;

$v4 = Ip::parse('127.0.0.1');
$v6 = $v4->toV6(); // ::ffff:127.0.0.1

$v6 = Ip::parse('fe80::ffff:127.0.0.1');
$v4 = $v6->toV4(); // 127.0.0.1 (if in v4 range)
```

### 9.3 Range Checking

```php
use DecodeLabs\Compass\Ip;
use DecodeLabs\Compass\Range;

$ip = Ip::parse('192.168.1.5');

// CIDR
if ($ip->isIn('192.168.0.0/16')) {
    // In private network
}

// Dash range
if ($ip->isIn('192.168.1.1-192.168.1.100')) {
    // In range
}

// Plus notation
if ($ip->isIn('192.168.1.1+100')) {
    // In relative range
}

// Wildcards
if ($ip->isIn('192.168.*')) {
    // Matches wildcard
}
```

### 9.4 CIDR Block Operations

```php
use DecodeLabs\Compass\Block;

$block = Block::parse('192.168.0.0/24');
$netmask = $block->getNetmask(); // 255.255.255.0
$delta = $block->getDelta(); // 0.0.0.255

if ($block->contains('192.168.0.5')) {
    // IP is in block
}
```

### 9.5 Special Address Detection

```php
use DecodeLabs\Compass\Ip;

$ip = Ip::parse($_SERVER['REMOTE_ADDR']);

if ($ip->isPrivate()) {
    // Private network
}

if ($ip->isReserved()) {
    // Reserved address
}

if ($ip->isLinkLocal()) {
    // Link-local address
}
```

### 9.6 Comparison Operations

```php
use DecodeLabs\Compass\Ip;

$ip1 = Ip::parse('192.168.1.1');
$ip2 = Ip::parse('192.168.1.2');

if ($ip1->isLessThan($ip2)) {
    // Comparison
}

$result = $ip1->compare($ip2); // -1, 0, or 1
```

### 9.7 Lucid Integration

```php
use DecodeLabs\Lucid\Processor\Ip;
use DecodeLabs\Lucid\Constraint\Compass\Range;

// Processor
$processor = new Ip();
$ip = $processor->coerce('127.0.0.1');

// Constraint
$constraint = new Range('127.0.0.0/8');
$errors = iterator_to_array($constraint->validate($ip));
```

---

## 10. Implementation Notes (for Contributors)

### 10.1 Internal Representation

IP addresses are stored as `BigInteger` internally for accuracy:
- IPv4: 32-bit integers (0 to 4,294,967,295)
- IPv6: 128-bit integers (0 to 340,282,366,920,938,463,463,374,607,431,768,211,455)

This ensures accurate handling of large IPv6 addresses that exceed PHP's native integer range.

### 10.2 String Parsing

IP string parsing uses PHP's `inet_pton()` for validation and conversion:
- Validates format using `filter_var($ip, FILTER_VALIDATE_IP)`
- Converts to binary using `inet_pton()`
- Converts binary to hex using `unpack('H*hex', $bin)`
- Converts hex to `BigInteger` using `BigInteger::fromBase($hex, 16)`

### 10.3 String Output

IP string output uses PHP's `inet_ntop()`:
- Converts `BigInteger` to hex using `toBase(16)`
- Pads hex to required length (8 for v4, 32 for v6)
- Converts hex to binary using `pack('H*', $hex)`
- Converts binary to string using `inet_ntop($bin)`

### 10.4 Version Detection

Version is determined by:
- Explicit `$isV6` parameter
- IP value exceeding IPv4 maximum (4294967295)
- Presence of `:` in string (IPv6)
- Presence of `::ffff:` prefix (IPv6-mapped IPv4)

### 10.5 Range Parsing

Range parsing supports multiple formats:
- **CIDR**: Parsed by `Block::parse()`, then converted to `Range`
- **Dash**: Split on `-`, parse both IPs
- **Plus**: Split on `+`, parse start IP, add count
- **Wildcards**: Replace `*` with min/max values (0/255 for v4, 0000/ffff for v6)

### 10.6 CIDR Block Calculation

CIDR blocks calculate:
- **Netmask**: `max << (bits - prefixLength) & max`
- **Delta**: `(1 << (bits - prefixLength)) - 1`
- **First IP**: `givenIp & netmask`
- **Last IP**: `firstIp | delta`

### 10.7 Special Address Blocks

Predefined blocks are stored in `V4Blocks` and `V6Blocks` interfaces as string arrays. These are parsed on-demand when checking address types.

### 10.8 Lucid Integration

Lucid integration is provided via:
- **Processors**: Coerce values to `Ip` or `Range` types
- **Constraints**: Validate IP addresses against rules (version, range, min/max)

---

## 11. Testing & Quality

- **Code Quality Score:** 4.5/5
- **README Quality Score:** 3/5
- **Documentation Score:** 0/5 (this spec)
- **Test Coverage Score:** 0/5

See `composer.json` for supported PHP versions.

---

## 12. Roadmap & Future Ideas

- Add support for more IP address formats
- Add network interface detection
- Consider adding IP geolocation integration
- Add support for port numbers in IP addresses
- Improve performance for large range operations
- Add test coverage
- Consider adding IP address anonymization utilities

---

## 13. References

- [Exceptional Package](https://github.com/decodelabs/exceptional) — Exception handling
- [Fluidity Package](https://github.com/decodelabs/fluidity) — Fluent interfaces
- [Nuance Package](https://github.com/decodelabs/nuance) — Debugging tools
- [Lucid Package](https://github.com/decodelabs/lucid) — Validation and sanitization
- [Brick Math Package](https://github.com/brick/math) — Arbitrary-precision arithmetic
- [RFC 1918](https://tools.ietf.org/html/rfc1918) — Private IP address ranges
- [RFC 4291](https://tools.ietf.org/html/rfc4291) — IPv6 addressing architecture
- [Chorus Package Index](../../../chorus/config/packages.json) — Ecosystem metadata


<?php

namespace Stayallive\RandomTokens;

use Tuupola\Base62;
use Valorin\Random\Random;
use Stayallive\RandomTokens\Exceptions\InvalidTokenFormatException;
use Stayallive\RandomTokens\Exceptions\InvalidTokenLengthException;
use Stayallive\RandomTokens\Exceptions\InvalidTokenPrefixException;
use Stayallive\RandomTokens\Exceptions\InvalidTokenChecksumException;

class RandomToken
{
    private const PARSE_REGEX = '/^(?<prefix>[a-zA-Z0-9]{1,6})_(?<random>[a-zA-Z0-9]{30,242})(?<checksum>[a-zA-Z0-9]{6})$/';

    public const DEFAULT_RANDOM_LENGTH = 30;
    public const MINIMUM_RANDOM_LENGTH = 30;
    public const MAXIMUM_RANDOM_LENGTH = 242;
    public const MINIMUM_PREFIX_LENGTH = 1;
    public const MAXIMUM_PREFIX_LENGTH = 6;
    public const CHECKSUM_LENGTH       = 6;

    private function __construct(
        public readonly string $prefix,
        public readonly string $random,
        public readonly string $checksum,
    ) {}

    public function __toString(): string
    {
        return "{$this->prefix}_{$this->random}{$this->checksum}";
    }

    /**
     * Generate a hash of the random part of the token for use in a cache key or to be stored in the database.
     */
    public function hash(bool $binary = false): string
    {
        return hash('sha256', $this->random, $binary);
    }

    /**
     * Get the cache key for the token.
     *
     * This cache key can be used to store information about the token in a cache without storing the plain text token.
     */
    public function cacheKey(): string
    {
        return "token:{$this->prefix}:{$this->hash()}";
    }

    /**
     * Generate a new token with a given prefix and length.
     */
    public static function generate(string $prefix, int $length = self::DEFAULT_RANDOM_LENGTH): self
    {
        self::validatePrefix($prefix);
        self::validateLength($length);

        $random = Random::token($length);

        $checksum = self::calculateChecksum($random);

        return new self($prefix, $random, $checksum);
    }

    /**
     * Construct a token from a string.
     *
     * @throws \Stayallive\RandomTokens\Exceptions\InvalidTokenFormatException
     * @throws \Stayallive\RandomTokens\Exceptions\InvalidTokenChecksumException
     */
    public static function fromString(string $token): self
    {
        $matched = preg_match(self::PARSE_REGEX, $token, $matches);

        if (!$matched) {
            throw new InvalidTokenFormatException;
        }

        $checksum = self::calculateChecksum($matches['random']);

        if ($checksum !== $matches['checksum']) {
            throw new InvalidTokenChecksumException;
        }

        return new self($matches['prefix'], $matches['random'], $matches['checksum']);
    }

    /**
     * Construct a token from a trusted random string and prefix.
     */
    public static function fromTrustedRandom(string $prefix, string $random): self
    {
        self::validatePrefix($prefix);

        return new self($prefix, $random, self::calculateChecksum($random));
    }

    /**
     * Validate the token length is within the allowed range.
     *
     * @throws \Stayallive\RandomTokens\Exceptions\InvalidTokenLengthException
     */
    private static function validateLength(int $length): void
    {
        if ($length >= self::MINIMUM_RANDOM_LENGTH && $length <= self::MAXIMUM_RANDOM_LENGTH) {
            return;
        }

        throw new InvalidTokenLengthException;
    }

    /**
     * Validate the token prefix is within the allowed range.
     *
     * @throws \Stayallive\RandomTokens\Exceptions\InvalidTokenPrefixException
     */
    private static function validatePrefix(string $prefix): void
    {
        if (strlen($prefix) >= self::MINIMUM_PREFIX_LENGTH && strlen($prefix) <= self::MAXIMUM_PREFIX_LENGTH) {
            return;
        }

        throw new InvalidTokenPrefixException;
    }

    /**
     * Calculate the checksum for a given random part of the token.
     */
    private static function calculateChecksum(string $random): string
    {
        $crc32 = hex2bin(hash('crc32b', $random));

        return str_pad((new Base62)->encode($crc32), self::CHECKSUM_LENGTH, '0', STR_PAD_LEFT);
    }
}

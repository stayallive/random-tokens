<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Stayallive\RandomTokens\RandomToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Stayallive\RandomTokens\Exceptions\InvalidTokenFormatException;
use Stayallive\RandomTokens\Exceptions\InvalidTokenLengthException;
use Stayallive\RandomTokens\Exceptions\InvalidTokenPrefixException;
use Stayallive\RandomTokens\Exceptions\InvalidTokenChecksumException;

class RandomTokenTest extends TestCase
{
    public function testCanGenerateToken(): void
    {
        $token = RandomToken::generate('test', length: 30);

        $this->assertSame('test', $token->prefix);
        $this->assertSame(30, strlen($token->random));
    }

    public function testCanGetTokenHash(): void
    {
        $token = RandomToken::fromString('prefix_ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu2ZIDR0');

        $this->assertSame('34d9fd6f95fb44264f0184f3d9bfe227c5e86f0855f4bed5fe0350d65cb1ae54', $token->hash());
        $this->assertSame('token:prefix:34d9fd6f95fb44264f0184f3d9bfe227c5e86f0855f4bed5fe0350d65cb1ae54', $token->cacheKey());
        $this->assertSame(hex2bin('34d9fd6f95fb44264f0184f3d9bfe227c5e86f0855f4bed5fe0350d65cb1ae54'), $token->hash(binary: true));
    }

    public function testCanGetTokenFromTrustedInput(): void
    {
        $plainTextToken = 'prefix_ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu2ZIDR0';

        $token = RandomToken::fromTrustedRandom('prefix', 'ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu');

        $this->assertSame($plainTextToken, (string)$token);
    }

    public function testTokenDoesNotExceed255Characters(): void
    {
        $token = RandomToken::generate(str_repeat('a', RandomToken::MAXIMUM_PREFIX_LENGTH), length: RandomToken::MAXIMUM_RANDOM_LENGTH);

        $this->assertSame(255, strlen((string)$token));
    }

    #[DataProvider('provideTokensToTest')]
    public function testCanGetTokenFromString(string $input, ?string $exceptionToExpectOrPrefix, ?string $randomPartToExpect = null, ?string $checksumToExpect = null): void
    {
        if ($randomPartToExpect === null && $checksumToExpect === null) {
            $this->expectException($exceptionToExpectOrPrefix);
        }

        $token = RandomToken::fromString($input);

        $this->assertSame($exceptionToExpectOrPrefix, $token->prefix);
        $this->assertSame($randomPartToExpect, $token->random);
        $this->assertSame($checksumToExpect, $token->checksum);
    }

    public static function provideTokensToTest(): array
    {
        return [
            ['prefix_ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu2ZIDR0', 'prefix', 'ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu', '2ZIDR0'],
            ['prefix_ieJCRA8kOyyrzm4hoM2yVbnKDFMzquABCDEF', InvalidTokenChecksumException::class],
            ['toolong_ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu2ZIDR0', InvalidTokenFormatException::class],
            ['toolong_ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu', InvalidTokenFormatException::class],
            ['prefix_notenoughrandomorchecksum', InvalidTokenFormatException::class],
        ];
    }

    #[DataProvider('provideTokenLengthsToTest')]
    public function testTokenLengthValidation(bool $expectsToFail, int $lengthToTest): void
    {
        if ($expectsToFail) {
            $this->expectException(InvalidTokenLengthException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        RandomToken::generate('prefix', $lengthToTest);
    }

    public static function provideTokenLengthsToTest(): array
    {
        return [
            [true, 1],
            [true, 29],
            [false, 30],
            [false, 242],
            [true, 243],
            [true, 500],
        ];
    }

    #[DataProvider('provideTokenPrefixesToTest')]
    public function testTokenPrefixValidation(bool $expectsToFail, string $prefixToTest): void
    {
        if ($expectsToFail) {
            $this->expectException(InvalidTokenPrefixException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        RandomToken::generate($prefixToTest);
    }

    public static function provideTokenPrefixesToTest(): array
    {
        return [
            [true, ''],
            [false, 'a'],
            [false, 'ab'],
            [false, 'abc'],
            [false, 'abcd'],
            [false, 'abcde'],
            [false, 'abcdef'],
            [true, 'toolong'],
        ];
    }
}

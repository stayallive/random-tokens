# Random Tokens

[![Latest Version](https://img.shields.io/github/release/stayallive/random-tokens.svg?style=flat-square)](https://github.com/stayallive/random-tokens/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/stayallive/random-tokens/ci.yaml?branch=main&style=flat-square)](https://github.com/stayallive/random-tokens/actions/workflows/ci.yaml)
[![Total Downloads](https://img.shields.io/packagist/dt/stayallive/random-tokens.svg?style=flat-square)](https://packagist.org/packages/stayallive/random-tokens)

This package provides a simple way to generate prefixed random tokens inspired by the [GitHub token format](https://github.blog/2021-04-05-behind-githubs-new-authentication-token-formats/).

Tokens are comprised of the following parts:

- tokens always start with a prefix of 1-6 characters
- followed by a `_`
- followed by 30-242 characters of randomness (`a-zA-Z0-9`)
- followed by a 6 character base62 CRC32 checksum

A token cannot exceed 255 characters in total length.

## Installation

```bash
composer require stayallive/random-tokens
```

## Usage

### Generating a token

```php
<?php

use Stayallive\RandomTokens\RandomToken;

// The prefix is required, but the length is optional and defaults to 30
$token = RandomToken::generate('prefix', length: 30);

echo (string)$token;   // Outputs: prefix_ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu2ZIDR0
echo $token->prefix;   // Outputs: prefix
echo $token->random;   // Outputs: ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu
echo $token->checksum; // Outputs: 2ZIDR0
```

### Validating a token

```php
<?php

use Stayallive\RandomTokens\RandomToken;

// Construct a random token from a string, this will validate the token and throws an exception if it's invalid
try {
    $token = RandomToken::fromString('prefix_ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu2ZIDR0');
} catch (\Stayallive\RandomTokens\Exceptions\InvalidTokenFormatException) {
    // Indicates the token is not in the expected format
} catch (\Stayallive\RandomTokens\Exceptions\InvalidTokenChecksumException) {
    // Indicates the token does not have a valid checksum
} catch (\Stayallive\RandomTokens\Exceptions\InvalidTokenException) {
    // Indicates the token is invalid for any of the reasons above
}

// If the token is valid you can extact the prefix, token and checksum for further validation
echo $token->prefix;   // Outputs: prefix
echo $token->random;   // Outputs: ieJCRA8kOyyrzm4hoM2yVbnKDFMzqu
echo $token->checksum; // Outputs: 2ZIDR0
```

### Storing a token

It's recommended to never store the token in plain text depending on your use case.

It's not needed to store the checksum as it can be recalculated from the random part of the token and since the prefix is usually static it's not needed to store that either.

You can store the token in a hashed format using the `RandomToken::hash(binary: false)` method.
This method will hash the random part of the token using SHA-256 and return a string with 64 characters.
Alternatively you can pass `true` as the `binary` argument to `hash()` to get the raw binary output of 32 bytes.

If you store information about the token in the cache you can also use `RandomToken::cacheKey()` to get a cache key for the token.
The cache key is constructed as `token:<prefix>:<hash>`, where `<prefix>` is the prefix of the token and `<hash>` is the SHA-256 hash of the random part of the token.

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an e-mail to Alex Bouma at `alex+security@bouma.me`. All security vulnerabilities will be swiftly
addressed.

## License

This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

<?php

declare(strict_types=1);

use Rutely\DteSigned\Crypto\Base64Url;

it('encodes and decodes base64url without padding', function (): void {
    $encoded = Base64Url::encode('Hola DTE + / =');

    expect($encoded)
        ->not->toContain('+')
        ->not->toContain('/')
        ->not->toContain('=')
        ->and(Base64Url::decode($encoded))->toBe('Hola DTE + / =');
});

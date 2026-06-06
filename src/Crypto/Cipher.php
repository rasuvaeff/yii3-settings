<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Crypto;

/**
 * @api
 */
interface Cipher
{
    /**
     * @param non-empty-string $aad Associated data that binds the ciphertext to a context (the setting key).
     */
    public function encrypt(string $plaintext, string $aad): string;

    public function decrypt(string $ciphertext, string $aad): string;
}

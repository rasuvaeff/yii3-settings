<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Crypto;

/**
 * @api
 */
interface Cipher
{
    /**
     * @param string $aad Associated data that binds the ciphertext to a context (the setting key, non-empty).
     */
    public function encrypt(string $plaintext, string $aad): string;

    /**
     * @param string $aad The same associated data used during encryption (non-empty).
     */
    public function decrypt(string $ciphertext, string $aad): string;
}

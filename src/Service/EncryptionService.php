<?php

declare(strict_types=1);

namespace App\Service;

use Random\RandomException;

class EncryptionService
{
    private readonly string $encryptionKey;

    public function __construct(string $masterPassword)
    {
        $this->encryptionKey = hash_pbkdf2('sha256', $masterPassword, 'salt_secret', 100000, 32, true);
    }

    /**
     * Encrypts the given data using AES-256-GCM.
     *
     * @param string $data The data to encrypt
     *
     * @return array An array containing the ciphertext and IV
     *
     * @throws RandomException
     */
    public function encryptData(string $data): array
    {
        $iv = random_bytes(12);
        $ciphertext = openssl_encrypt($data, 'aes-256-gcm', $this->encryptionKey, 0, $iv, $tag);

        return [
            'ciphertext' => base64_encode($ciphertext.'::'.$tag),
            'iv' => base64_encode($iv),
        ];
    }

    /**
     * Decrypts the given ciphertext using AES-256-GCM.
     *
     * @param string $ciphertext The encrypted data (base64 encoded)
     * @param string $iv         The initialization vector (base64 encoded)
     *
     * @return string The decrypted data
     */
    public function decryptData(string $ciphertext, string $iv): string
    {
        $data = explode('::', base64_decode($ciphertext, true));

        return openssl_decrypt($data[0], 'aes-256-gcm', $this->encryptionKey, 0, base64_decode($iv, true), $data[1]);
    }
}

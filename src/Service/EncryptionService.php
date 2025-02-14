<?php

namespace App\Service;

use Random\RandomException;

class EncryptionService
{
    private string $encryptionKey;

    public function __construct(string $masterPassword)
    {
        $this->encryptionKey = hash_pbkdf2("sha256", $masterPassword, "salt_secret", 100000, 32, true);
    }

    /**
     * @throws RandomException
     */
    public function encryptData(string $data): array
    {
        $iv = random_bytes(12);
        $ciphertext = openssl_encrypt($data, "aes-256-gcm", $this->encryptionKey, 0, $iv, $tag);
        return [
            'ciphertext' => base64_encode($ciphertext . "::" . $tag),
            'iv' => base64_encode($iv)
        ];
    }

    public function decryptData(string $ciphertext, string $iv): string
    {
        $data = explode("::", base64_decode($ciphertext));
        return openssl_decrypt($data[0], "aes-256-gcm", $this->encryptionKey, 0, base64_decode($iv), $data[1]);
    }
}

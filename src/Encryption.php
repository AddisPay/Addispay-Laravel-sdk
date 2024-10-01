<?php

namespace AshenafiPixel\AddisPaySDK;

use phpseclib3\Crypt\RSA;
use AshenafiPixel\AddisPaySDK\Exceptions\AddisPayException;

class Encryption
{
    /**
     * Encrypt data using the provided public key.
     *
     * @param string $data
     * @param string $publicKey
     * @return string
     * @throws AddisPayException
     */
    public static function encrypt($data, $publicKey)
    {
        try {
            $rsa = RSA::loadPublicKey($publicKey);
            $ciphertext = $rsa->encrypt($data);
            return base64_encode($ciphertext);
        } catch (\Exception $e) {
            throw new AddisPayException("Encryption failed: " . $e->getMessage());
        }
    }

    /**
     * Decrypt data using the provided private key.
     *
     * @param string $encryptedData
     * @param string $privateKey
     * @return string
     * @throws AddisPayException
     */
    public static function decrypt($encryptedData, $privateKey)
    {
        try {
            $rsa = RSA::loadPrivateKey($privateKey);
            $plaintext = $rsa->decrypt(base64_decode($encryptedData));
            return $plaintext;
        } catch (\Exception $e) {
            throw new AddisPayException("Decryption failed: " . $e->getMessage());
        }
    }
}

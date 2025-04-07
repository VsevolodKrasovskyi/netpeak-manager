<?php

/*
* Cache
* @since 1.0.2
*/

namespace Netpeak;

class CacheManager {
    private $cacheRootDir;

    public function __construct($cacheRootDir = null) {
        $this->cacheRootDir = $cacheRootDir ?: WP_CONTENT_DIR . '/cache/netpeak';
        if (!file_exists($this->cacheRootDir)) {
            wp_mkdir_p($this->cacheRootDir);
        }
    }

    private function encryptLocal($plainData, $licenseKey) {

        $keyBinary = hash('sha256', $licenseKey, true);

        $iv = random_bytes(16);

        $encrypted = openssl_encrypt($plainData, 'AES-256-CBC', $keyBinary, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            error_log("CacheManager Error: Encryption failed");
            return false;
        }

        return base64_encode($iv . $encrypted);
    }

    private function decryptLocal($encryptedBase64, $licenseKey) {
        $keyBinary = hash('sha256', $licenseKey, true);

        $decoded = base64_decode($encryptedBase64);
        if (!$decoded || strlen($decoded) < 16) {
            error_log("CacheManager Error: Invalid encrypted data");
            return false;
        }

        $iv = substr($decoded, 0, 16);
        $cipherText = substr($decoded, 16);

        $plain = openssl_decrypt($cipherText, 'AES-256-CBC', $keyBinary, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            error_log("CacheManager Error: Decryption failed");
            return false;
        }

        return $plain;
    }

    private function getCacheFilePath($key, $subDir = '') {
        $cacheDir = $this->cacheRootDir . ($subDir ? '/' . $subDir : '');
        if (!file_exists($cacheDir)) {
            wp_mkdir_p($cacheDir);
        }
        return $cacheDir . '/' . hash_hmac('sha256', $key, 'salt') . '.cache';
    }

    public function set($key, $plainData, $expiration = HOUR_IN_SECONDS, $subDir = '') {
        $cacheFile = $this->getCacheFilePath($key, $subDir);

        $licenseKey = get_option('netpeak_seo_license_key');
        if (!$licenseKey) {
            error_log("CacheManager Error: License key missing for encryption");
            return;
        }

        $encryptedData = $this->encryptLocal($plainData, $licenseKey);
        if (!$encryptedData) {
            return;
        }

        $cacheData = [
            'expires' => time() + $expiration,
            'data'    => $encryptedData,
        ];

        file_put_contents($cacheFile, json_encode($cacheData, JSON_THROW_ON_ERROR), LOCK_EX);
    }


    /**
     * Reads a value from the cache. If the value does not exist or has expired, it is removed from the cache and null is returned.
     *
     * @param string $key
     * @param string $subDir
     * @return mixed|null
     */
    public function get($key, $subDir = '') {
        $cacheFile = $this->getCacheFilePath($key, $subDir);

        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true, 512, JSON_THROW_ON_ERROR);
            if ($cacheData && isset($cacheData['expires'], $cacheData['data'])) {
                if ($cacheData['expires'] >= time()) {
                    $encryptedData = $cacheData['data'];

                    $licenseKey = get_option('netpeak_seo_license_key');
                    if (!$licenseKey) {
                        error_log("CacheManager Error: License key missing for decryption");
                        return null;
                    }

                    $plainData = $this->decryptLocal($encryptedData, $licenseKey);
                    if ($plainData === false) {
                        return null; 
                    }

                    return $plainData;
                } else {
                    unlink($cacheFile);
                }
            }
        }
        return null; 
    }

    public function delete($key, $subDir = '') {
        $cacheFile = $this->getCacheFilePath($key, $subDir);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function clear($subDir = '') {
        $cacheDir = $this->cacheRootDir . ($subDir ? '/' . $subDir : '');
        $files = glob($cacheDir . '/*.cache');
        if ($files) {
            array_map('unlink', $files);
        }
    }
}

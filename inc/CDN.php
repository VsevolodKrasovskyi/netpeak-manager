<?php

namespace Netpeak;

class CDN {
    private static $instance;
    private $cacheManager;
    private $licenseKey;
    private $currentDomain;
    private $base_Api;

    private function __construct() {
        $this->cacheManager   = new CacheManager(WP_CONTENT_DIR . '/cache/netpeak');
        $this->licenseKey     = get_option('netpeak_seo_license_key');
        $this->currentDomain  = parse_url(home_url(), PHP_URL_HOST);
        $this->base_Api       = 'https://cdn.netpeak.dev/api/v1/';
    }
    //Singleton
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    //Get base API
    public function getBaseApi()
    {
        return $this->base_Api;
    }

    private function getCdnToken() {
        $email = get_option('netpeak_seo_license_email'); 
        $password = get_option('netpeak_seo_license_password'); 

        // Sending a request
        $response = wp_remote_post( $this->base_Api . 'login', [
            'body' => [
                'email'    => 'masik@vanilla',
                'password' => $password,
            ],
        ]);
    
        if (is_wp_error($response)) {
            error_log("CDN Error: " . $response->get_error_message());
            return false;
        }
    
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['token']) && isset($data['expires_at'])) {
            update_option('netpeak_seo_license_auth_token', sanitize_text_field($data['token']));
        }
    
        return $data['success'] ? $data['token'] : false;
    }
    
    public function load_cdn_script($scriptName) {
        if (!$this->licenseKey) {
            error_log("CDN Error: License key missing");
            return;
        }

        $cacheKey = 'netpeak_seo_cdn_script_' . hash_hmac('sha256', $scriptName, 'super_secret_salt');
        
        $cachedScript = $this->cacheManager->get($cacheKey);
        if ($cachedScript) {
            $this->include_script($cachedScript, $scriptName);
            return;
        }
        $token = get_option('netpeak_seo_license_auth_token');
        if (!$token) {
            error_log("CDN Error: Token not found in DB => Getting new token API");
            $token = $this->getCdnToken();
            return;
        }

        $response = wp_remote_post( $this->base_Api . "load-script/{$scriptName}", [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'body' => [
                'license_key' => $this->licenseKey,
                'domain'      => $this->currentDomain
            ],
        ]);

        if (is_wp_error($response)) {
            error_log("CDN Error: Request failed - " . $response->get_error_message());
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['success'])) {
            error_log("CDN Error: Failed to load script $scriptName");
            return;
        }

        $encryptedScriptBase64 = $data['script'];
        $serverHash            = $data['hash'];

        $key = hash('sha256', $this->licenseKey, true);

        $computedHash = hash_hmac('sha256', $encryptedScriptBase64, $key);
        if ($computedHash !== $serverHash) {
            error_log("CDN Error: Hash mismatch for script $scriptName");
            return;
        }

        $decoded = base64_decode($encryptedScriptBase64);
        if (!$decoded || strlen($decoded) < 16) {
            error_log("CDN Error: Invalid encrypted data");
            return;
        }

        $iv        = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);

        $decryptedScript = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($decryptedScript === false) {
            error_log("CDN Error: Decryption failed for script $scriptName");
            return;
        }

        $this->cacheManager->set($cacheKey, $decryptedScript, HOUR_IN_SECONDS);

        $this->include_script($decryptedScript, $scriptName);
    }

    private function include_script($scriptContent, $scriptName) {
        $tempFile = tempnam(sys_get_temp_dir(), "cdn_{$scriptName}_") . '.php';
        if (file_put_contents($tempFile, $scriptContent, LOCK_EX) === false) {
            error_log("CDN Error: Failed to write temporary file for $scriptName");
            return;
        }

        include_once $tempFile;

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}

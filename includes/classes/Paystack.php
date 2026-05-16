<?php
/**
 * =============================================
 * RemoWorkers - Paystack Service
 * =============================================
 */

class Paystack {
    private $secretKey;
    private $publicKey;
    private $baseUrl = "https://api.paystack.co";

    public function __construct() {
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
        $this->publicKey = env('PAYSTACK_PUBLIC_KEY');
    }

    /**
     * Initialize a transaction
     */
    public function initialize($email, $amount, $callbackUrl, $metadata = []) {
        $url = $this->baseUrl . "/transaction/initialize";
        
        $fields = [
            'email' => $email,
            'amount' => $amount * 100, // Paystack uses kobo/cents
            'callback_url' => $callbackUrl,
            'metadata' => json_encode($metadata)
        ];

        return $this->request($url, 'POST', $fields);
    }

    /**
     * Verify a transaction
     */
    public function verify($reference) {
        $url = $this->baseUrl . "/transaction/verify/" . rawurlencode($reference);
        return $this->request($url, 'GET');
    }

    /**
     * Handle API requests
     */
    private function request($url, $method = 'GET', $fields = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = [
            "Authorization: Bearer " . $this->secretKey,
            "Cache-Control: no-cache",
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $headers[] = "Content-Type: application/json";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return [
                'status' => false,
                'message' => "Curl Error: " . $err
            ];
        }

        return json_decode($response, true);
    }
}

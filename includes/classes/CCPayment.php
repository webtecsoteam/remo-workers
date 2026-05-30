<?php
/**
 * CCPayment API v2 client (signed requests).
 */
class CCPayment
{
    private string $appId;
    private string $appSecret;
    private string $depositAddressUrl;
    private string $depositRecordUrl;
    private string $withdrawUrl;
    private string $withdrawRecordUrl;

    public function __construct()
    {
        $this->appId = (string) env('CCPAYMENT_API_KEY', '');
        $this->appSecret = (string) env('CCPAYMENT_SECRET', '');
        $this->depositAddressUrl = (string) env(
            'CCPAYMENT_DEPOSIT_ADDRESS_URL',
            'https://ccpayment.com/ccpayment/v2/getOrCreateAppDepositAddress'
        );
        $this->depositRecordUrl = (string) env(
            'CCPAYMENT_DEPOSIT_RECORD_URL',
            'https://ccpayment.com/ccpayment/v2/getAppDepositRecord'
        );
        $this->withdrawUrl = (string) env(
            'CCPAYMENT_WITHDRAW_URL',
            'https://ccpayment.com/ccpayment/v2/applyAppWithdrawToNetwork'
        );
        $this->withdrawRecordUrl = (string) env(
            'CCPAYMENT_WITHDRAW_RECORD_URL',
            'https://ccpayment.com/ccpayment/v2/getAppWithdrawRecord'
        );
    }

    public function isConfigured(): bool
    {
        return $this->appId !== '' && $this->appSecret !== '';
    }

    /**
     * Get or create a permanent USDT deposit address on the given chain.
     *
     * @return array{success: bool, address?: string, memo?: string, message?: string, raw?: array}
     */
    public function getOrCreateDepositAddress(string $referenceId, string $chain = 'TRX'): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'CCPayment is not configured.'];
        }

        if (function_exists('ccpayment_normalize_chain')) {
            $chain = ccpayment_normalize_chain($chain);
        }

        $content = [
            'referenceId' => $referenceId,
            'chain' => $chain,
        ];

        $response = $this->post($this->depositAddressUrl, $content);
        if (!$response['success']) {
            return $response;
        }

        $raw = $response['raw'] ?? [];
        $code = (int) ($raw['code'] ?? 0);
        if ($code !== 10000) {
            return [
                'success' => false,
                'message' => (string) ($raw['msg'] ?? 'CCPayment request failed'),
                'raw' => $raw,
            ];
        }

        $data = $raw['data'] ?? [];
        if (!is_array($data) || empty($data['address'])) {
            return [
                'success' => false,
                'message' => 'CCPayment did not return a deposit address.',
                'raw' => $raw,
            ];
        }

        return [
            'success' => true,
            'address' => (string) $data['address'],
            'memo' => (string) ($data['memo'] ?? ''),
            'raw' => $raw,
        ];
    }

    /**
     * Fetch deposit details (amount, coin, txId) — required because DirectDeposit webhooks omit amount.
     *
     * @return array{success: bool, record?: array<string, mixed>, message?: string, raw?: array}
     */
    public function getDepositRecord(string $recordId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'CCPayment is not configured.'];
        }

        if ($recordId === '') {
            return ['success' => false, 'message' => 'Missing recordId.'];
        }

        $response = $this->post($this->depositRecordUrl, ['recordId' => $recordId]);
        if (!$response['success']) {
            return $response;
        }

        $raw = $response['raw'] ?? [];
        $code = (int) ($raw['code'] ?? 0);
        if ($code !== 10000) {
            return [
                'success' => false,
                'message' => (string) ($raw['msg'] ?? 'CCPayment deposit lookup failed'),
                'raw' => $raw,
            ];
        }

        $record = $raw['data']['record'] ?? null;
        if (!is_array($record)) {
            return [
                'success' => false,
                'message' => 'CCPayment did not return deposit record details.',
                'raw' => $raw,
            ];
        }

        return [
            'success' => true,
            'record' => $record,
            'raw' => $raw,
        ];
    }

    /**
     * Create a network (on-chain) withdrawal to an external wallet address.
     *
     * @param array{coinId: int, chain: string, address: string, orderId: string, amount: string, memo?: string, merchantPayNetworkFee?: bool} $params
     * @return array{success: bool, recordId?: string, message?: string, raw?: array}
     */
    public function createNetworkWithdrawal(array $params): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'CCPayment is not configured.'];
        }

        $content = [
            'coinId' => (int) ($params['coinId'] ?? 0),
            'chain' => (string) ($params['chain'] ?? ''),
            'address' => (string) ($params['address'] ?? ''),
            'orderId' => (string) ($params['orderId'] ?? ''),
            'amount' => (string) ($params['amount'] ?? ''),
        ];

        if ($content['coinId'] <= 0 || $content['chain'] === '' || $content['address'] === ''
            || $content['orderId'] === '' || $content['amount'] === '') {
            return ['success' => false, 'message' => 'Missing required withdrawal parameters.'];
        }

        if (isset($params['memo']) && (string) $params['memo'] !== '') {
            $content['memo'] = (string) $params['memo'];
        }
        $content['merchantPayNetworkFee'] = array_key_exists('merchantPayNetworkFee', $params)
            ? (bool) $params['merchantPayNetworkFee']
            : false;

        if (function_exists('ccpayment_normalize_chain')) {
            $content['chain'] = ccpayment_normalize_chain($content['chain']);
        }

        $response = $this->postWithRetry($this->withdrawUrl, $content, 3, 15);
        if (!$response['success']) {
            return $response;
        }

        $raw = $response['raw'] ?? [];
        $code = (int) ($raw['code'] ?? 0);
        if ($code !== 10000) {
            return [
                'success' => false,
                'message' => (string) ($raw['msg'] ?? 'CCPayment withdrawal request failed'),
                'raw' => $raw,
            ];
        }

        $recordId = (string) ($raw['data']['recordId'] ?? '');
        if ($recordId === '') {
            $lookup = $this->getWithdrawRecord('', $content['orderId']);
            if ($lookup['success'] && !empty($lookup['recordId'])) {
                $recordId = (string) $lookup['recordId'];
            }
        }

        if ($recordId === '') {
            return [
                'success' => false,
                'message' => 'CCPayment did not return a withdrawal record ID.',
                'raw' => $raw,
            ];
        }

        return [
            'success' => true,
            'recordId' => $recordId,
            'raw' => $raw,
        ];
    }

    /**
     * @return array{success: bool, recordId?: string, status?: string, record?: array, message?: string, raw?: array}
     */
    public function getWithdrawRecord(string $recordId = '', string $orderId = ''): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'CCPayment is not configured.'];
        }

        if ($recordId === '' && $orderId === '') {
            return ['success' => false, 'message' => 'Missing recordId or orderId.'];
        }

        $content = [];
        if ($recordId !== '') {
            $content['recordId'] = $recordId;
        }
        if ($orderId !== '') {
            $content['orderId'] = $orderId;
        }

        $response = $this->post($this->withdrawRecordUrl, $content);
        if (!$response['success']) {
            return $response;
        }

        $raw = $response['raw'] ?? [];
        $code = (int) ($raw['code'] ?? 0);
        if ($code !== 10000) {
            return [
                'success' => false,
                'message' => (string) ($raw['msg'] ?? 'CCPayment withdrawal lookup failed'),
                'raw' => $raw,
            ];
        }

        $record = $raw['data']['record'] ?? null;
        if (!is_array($record)) {
            return [
                'success' => false,
                'message' => 'CCPayment did not return withdrawal record details.',
                'raw' => $raw,
            ];
        }

        return [
            'success' => true,
            'recordId' => (string) ($record['recordId'] ?? $recordId),
            'status' => (string) ($record['status'] ?? ''),
            'record' => $record,
            'raw' => $raw,
        ];
    }

    /**
     * @param array<string, mixed> $content
     * @return array{success: bool, message?: string, raw?: array, timed_out?: bool}
     */
    private function postWithRetry(string $url, array $content, int $maxAttempts = 3, int $timeoutSeconds = 15): array
    {
        $last = ['success' => false, 'message' => 'CCPayment request failed.'];
        $orderId = (string) ($content['orderId'] ?? '');

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $last = $this->postTimed($url, $content, $timeoutSeconds);
            if ($last['success'] || empty($last['timed_out'])) {
                return $last;
            }
            usleep(200000);
        }

        if ($orderId !== '') {
            $lookup = $this->getWithdrawRecord('', $orderId);
            if ($lookup['success']) {
                $status = strtolower((string) ($lookup['status'] ?? ''));
                if (in_array($status, ['success', 'processing', 'waitingapproval'], true)) {
                    return [
                        'success' => true,
                        'raw' => [
                            'code' => 10000,
                            'msg' => 'success',
                            'data' => ['recordId' => $lookup['recordId'] ?? ''],
                        ],
                    ];
                }
            }
        }

        return $last;
    }

    /**
     * @param array<string, mixed> $content
     * @return array{success: bool, message?: string, raw?: array, timed_out?: bool}
     */
    private function postTimed(string $url, array $content, int $timeoutSeconds = 15): array
    {
        $timestamp = time();
        $body = json_encode($content, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return ['success' => false, 'message' => 'Failed to encode request body.'];
        }

        $signText = $this->appId . $timestamp;
        if (strlen($body) !== 2) {
            $signText .= $body;
        } else {
            $body = '';
        }

        $sign = hash_hmac('sha256', $signText, $this->appSecret);
        $headers = [
            'Content-Type: application/json;charset=utf-8',
            'Appid: ' . $this->appId,
            'Sign: ' . $sign,
            'Timestamp: ' . $timestamp,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_USERAGENT => 'RemoWorkers/1.0',
        ]);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
            return ['success' => false, 'message' => 'CCPayment request timed out.', 'timed_out' => true];
        }

        if ($response === false) {
            return ['success' => false, 'message' => 'CCPayment request failed: ' . $curlError];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Invalid response from CCPayment (HTTP ' . $httpCode . ').',
            ];
        }

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'message' => (string) ($decoded['msg'] ?? 'CCPayment HTTP error ' . $httpCode),
                'raw' => $decoded,
            ];
        }

        return ['success' => true, 'raw' => $decoded];
    }

    /**
     * @param array<string, mixed> $content
     * @return array{success: bool, message?: string, raw?: array}
     */
    public function post(string $url, array $content): array
    {
        $timestamp = time();
        $body = json_encode($content, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return ['success' => false, 'message' => 'Failed to encode request body.'];
        }

        $signText = $this->appId . $timestamp;
        if (strlen($body) !== 2) {
            $signText .= $body;
        } else {
            $body = '';
        }

        $sign = hash_hmac('sha256', $signText, $this->appSecret);

        $headers = [
            'Content-Type: application/json;charset=utf-8',
            'Appid: ' . $this->appId,
            'Sign: ' . $sign,
            'Timestamp: ' . $timestamp,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'RemoWorkers/1.0',
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'message' => 'CCPayment request failed: ' . $curlError];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Invalid response from CCPayment (HTTP ' . $httpCode . ').',
            ];
        }

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'message' => (string) ($decoded['msg'] ?? 'CCPayment HTTP error ' . $httpCode),
                'raw' => $decoded,
            ];
        }

        return ['success' => true, 'raw' => $decoded];
    }
}

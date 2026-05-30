<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/classes/CCPayment.php';
require_once __DIR__ . '/../../includes/ccpayment_transactions.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;
$method_id = isset($data['method_id']) ? (int) $data['method_id'] : 0;
$cryptoAddress = isset($data['crypto_address']) ? trim((string) $data['crypto_address']) : '';

if ($amount <= 0 || empty($method_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid withdrawal request.']);
    exit;
}

$db = getDB();

try {
    $methStmt = $db->prepare('SELECT * FROM user_withdrawal_methods WHERE id = ? AND user_id = ?');
    $methStmt->execute([$method_id, $user['id']]);
    $savedMethod = $methStmt->fetch(PDO::FETCH_ASSOC);
    if (!$savedMethod) {
        throw new Exception('Selected withdrawal method not found.');
    }

    $method = $savedMethod['method_type'];
    $details = json_decode($savedMethod['details'], true);
    if (!is_array($details)) {
        $details = [];
    }

    if ($method === 'Crypto') {
        echo json_encode(processCryptoWithdrawal($db, $user, $amount, $cryptoAddress, $details));
        exit;
    }

    $db->beginTransaction();

    $stmt = $db->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$user['id']]);
    $currentBalance = (float) $stmt->fetchColumn();

    if ($currentBalance < $amount) {
        throw new Exception('Insufficient balance for withdrawal.');
    }

    $upd = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
    $upd->execute([$amount, $user['id']]);

    if (isset($details['email'])) {
        $detailsStr = 'Withdrawal to ' . $method . ' (' . $details['email'] . ')';
    } else {
        $detailsStr = 'Withdrawal to ' . $method . ' (' . ($details['bankName'] ?? 'Bank') . ' - *' . substr($details['accNum'] ?? '', -4) . ')';
    }

    $tx = $db->prepare("
        INSERT INTO payments (transaction_id, payer_id, payee_id, amount, payment_method, description, status)
        VALUES (?, NULL, ?, ?, ?, ?, 'pending')
    ");
    $txId = 'WTH-' . strtoupper(uniqid());
    $tx->execute([$txId, $user['id'], $amount, $method, $detailsStr]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * @param array<string, mixed> $user
 * @param array<string, mixed> $details
 * @return array{success: bool, message?: string}
 */
function processCryptoWithdrawal(PDO $db, array $user, float $amount, string $cryptoAddress, array $details): array
{
    if ($cryptoAddress === '') {
        return ['success' => false, 'message' => 'Please enter a valid crypto wallet address.'];
    }

    if (strlen($cryptoAddress) < 10 || strlen($cryptoAddress) > 128) {
        return ['success' => false, 'message' => 'Wallet address length is invalid.'];
    }

    $client = new CCPayment();
    if (!$client->isConfigured()) {
        return ['success' => false, 'message' => 'Crypto withdrawals are not configured. Please contact support.'];
    }

    $chain = (string) ($details['chain'] ?? env('CCPAYMENT_WITHDRAW_CHAIN', env('CCPAYMENT_CHAIN', 'POLYGON')));
    if (function_exists('ccpayment_normalize_chain')) {
        $chain = ccpayment_normalize_chain($chain);
    }

    $coinId = (int) env('CCPAYMENT_WITHDRAW_COIN_ID', 1280);
    if ($coinId <= 0) {
        return ['success' => false, 'message' => 'Crypto withdrawal coin is not configured.'];
    }

    $db->beginTransaction();

    try {
        $stmt = $db->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$user['id']]);
        $currentBalance = (float) $stmt->fetchColumn();

        if ($currentBalance < $amount) {
            throw new Exception('Insufficient balance for withdrawal.');
        }

        $orderId = 'WTH-' . strtoupper(bin2hex(random_bytes(8)));
        $cryptoAmount = number_format($amount, 6, '.', '');
        $cryptoAmount = rtrim(rtrim($cryptoAmount, '0'), '.');
        if ($cryptoAmount === '' || (float) $cryptoAmount <= 0) {
            throw new Exception('Withdrawal amount is too small.');
        }

        $withdrawResult = $client->createNetworkWithdrawal([
            'coinId' => $coinId,
            'chain' => $chain,
            'address' => $cryptoAddress,
            'orderId' => $orderId,
            'amount' => $cryptoAmount,
            'merchantPayNetworkFee' => false,
        ]);

        if (!$withdrawResult['success']) {
            throw new Exception($withdrawResult['message'] ?? 'Crypto withdrawal could not be initiated.');
        }

        $recordId = (string) ($withdrawResult['recordId'] ?? '');

        $upd = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
        $upd->execute([$amount, $user['id']]);

        $masked = strlen($cryptoAddress) > 12
            ? substr($cryptoAddress, 0, 6) . '…' . substr($cryptoAddress, -4)
            : $cryptoAddress;
        $chainLabel = function_exists('ccpayment_chain_label') ? ccpayment_chain_label($chain) : $chain;
        $detailsStr = 'Withdrawal to Crypto USDT (' . $chainLabel . ' · ' . $masked . ')';

        $tx = $db->prepare("
            INSERT INTO payments (transaction_id, payer_id, payee_id, amount, payment_method, description, status)
            VALUES (?, NULL, ?, ?, 'ccpayment_crypto', ?, 'pending')
        ");
        $tx->execute([$orderId, $user['id'], $amount, $detailsStr]);
        $paymentId = (int) $db->lastInsertId();

        ccpayment_ensure_transactions_table();
        $ccLog = $db->prepare("
            INSERT INTO ccpayment_transactions
                (reference_id, user_id, payment_id, purpose, amount_usd, chain, coin_symbol, status, ccpayment_record_id, api_request, api_response)
            VALUES (?, ?, ?, 'withdraw', ?, ?, 'USDT', 'pending', ?, ?, ?)
        ");
        $apiRequest = json_encode([
            'coinId' => $coinId,
            'chain' => $chain,
            'address' => $cryptoAddress,
            'orderId' => $orderId,
            'amount' => $cryptoAmount,
        ]);
        $apiResponse = json_encode($withdrawResult['raw'] ?? []);
        $ccLog->execute([
            $orderId,
            $user['id'],
            $paymentId > 0 ? $paymentId : null,
            $amount,
            $chain,
            $recordId !== '' ? $recordId : null,
            $apiRequest,
            $apiResponse,
        ]);

        $db->commit();

        return [
            'success' => true,
            'message' => 'Crypto withdrawal submitted. Funds will arrive after blockchain confirmation.',
            'order_id' => $orderId,
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

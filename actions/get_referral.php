<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/referral.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $referrerId = (int) $user['id'];
    $referralCode = ensureUserReferralCode($referrerId);
    $rewardResult = processReferralRewards($referrerId);
    $summary = getReferrerReferralSummary($referrerId);

    $response = [
        'success' => true,
        'code' => $referralCode,
        'link' => referralShareUrl($referralCode),
        'stats' => [
            'total' => $summary['total'],
            'qualified_count' => $summary['qualified_count'],
            'threshold' => $summary['threshold'],
            'reward_amount' => $summary['reward_amount'],
            'rewards_paid' => $summary['rewards_paid'],
            'total_earned' => $summary['total_earned'],
            'progress_to_next' => $summary['progress_to_next'],
        ],
        'referrals' => $summary['referrals'],
    ];

    if (($rewardResult['paid_milestones'] ?? 0) > 0) {
        $response['reward_credited'] = [
            'amount' => $rewardResult['amount_credited'],
            'milestones' => $rewardResult['paid_milestones'],
            'new_balance' => $rewardResult['new_balance'],
        ];
    }

    echo json_encode($response);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to load referral code.']);
}

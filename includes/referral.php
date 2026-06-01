<?php

/**
 * Ensure the users.referral_code column exists (runtime safety net).
 */
function ensureReferralCodeColumn(?PDO $db = null): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db = $db ?? getDB();
    try {
        $cols = $db->query('DESCRIBE users')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('referral_code', $cols, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN referral_code VARCHAR(16) NULL');
            $db->exec('CREATE UNIQUE INDEX idx_users_referral_code ON users (referral_code)');
        }
    } catch (PDOException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Referral schema check failed: ' . $e->getMessage());
        }
    }

    $done = true;
}

/**
 * Generate a unique referral code (8 chars, no ambiguous characters).
 */
function generateReferralCode(?PDO $db = null): string
{
    $db = $db ?? getDB();
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    for ($attempt = 0; $attempt < 25; $attempt++) {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE referral_code = ? LIMIT 1');
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }

    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

/**
 * Return the user's referral code, creating and saving one if missing.
 */
function ensureUserReferralCode(int $userId, ?PDO $db = null): string
{
    $db = $db ?? getDB();
    ensureReferralCodeColumn($db);

    $stmt = $db->prepare('SELECT referral_code FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $existing = $stmt->fetchColumn();

    if (is_string($existing) && $existing !== '') {
        return $existing;
    }

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = generateReferralCode($db);
        try {
            $upd = $db->prepare('UPDATE users SET referral_code = ? WHERE id = ? AND (referral_code IS NULL OR referral_code = \'\')');
            $upd->execute([$code, $userId]);
            if ($upd->rowCount() > 0) {
                return $code;
            }

            $stmt->execute([$userId]);
            $existing = $stmt->fetchColumn();
            if (is_string($existing) && $existing !== '') {
                return $existing;
            }
        } catch (PDOException $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('Referral code save failed: ' . $e->getMessage());
            }
        }
    }

    throw new RuntimeException('Unable to generate referral code.');
}

/**
 * Public signup link that includes the referral code.
 */
function referralShareUrl(string $referralCode): string
{
    return baseUrl('?ref=' . urlencode($referralCode));
}

/**
 * Normalize user-entered referral code input.
 */
function normalizeReferralCodeInput(string $code): string
{
    return strtoupper(preg_replace('/\s+/', '', trim($code)));
}

/**
 * Look up the referrer by their public referral code.
 *
 * @return array{id:int,name:string,referral_code:string}|null
 */
function lookupReferrerByCode(string $code, ?PDO $db = null): ?array
{
    $code = normalizeReferralCodeInput($code);
    if ($code === '' || strlen($code) > 16) {
        return null;
    }

    $db = $db ?? getDB();
    ensureReferralCodeColumn($db);

    $stmt = $db->prepare('
        SELECT id, name, referral_code
        FROM users
        WHERE UPPER(referral_code) = ?
          AND status = ?
        LIMIT 1
    ');
    $stmt->execute([$code, 'active']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['referral_code'])) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'referral_code' => (string) $row['referral_code'],
    ];
}

/** Whether the referral program is enabled (admin platform setting). */
function referralProgramEnabled(): bool
{
    if (!function_exists('getPlatformSettingString')) {
        return true;
    }
    return getPlatformSettingString('referral_enabled', '1') === '1';
}

/** Qualified referrals required before each wallet reward. */
function referralRewardThreshold(): int
{
    $value = (int) getPlatformSetting('referral_reward_threshold', 10);
    return $value > 0 ? $value : 10;
}

/** USD amount credited to referrer wallet per milestone. */
function referralRewardAmount(): float
{
    $value = (float) getPlatformSetting('referral_reward_amount', 1.0);
    return $value > 0 ? round($value, 2) : 1.0;
}

/**
 * Ensure referral tracking tables exist (runtime safety net).
 */
function ensureReferralTables(?PDO $db = null): void
{
    static $done = false;
    if ($done) {
        return;
    }

    ensureReferralCodeColumn($db);
    $db = $db ?? getDB();

    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_referrals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                referrer_id INT NOT NULL,
                referred_user_id INT NOT NULL,
                referral_code_used VARCHAR(16) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_referred_user (referred_user_id),
                KEY idx_referrer (referrer_id),
                FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS referral_rewards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                referrer_id INT NOT NULL,
                milestone INT NOT NULL,
                amount DECIMAL(12, 2) NOT NULL DEFAULT 1.00,
                qualified_count INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_referrer_milestone (referrer_id, milestone),
                FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Referral tables check failed: ' . $e->getMessage());
        }
    }

    $done = true;
}

/**
 * Whether a referred user has uploaded a profile photo.
 */
function userHasProfilePhoto(array $userRow): bool
{
    $avatar = trim((string) ($userRow['avatar_url'] ?? ''));
    return $avatar !== '';
}

/**
 * Whether a referred user has verified their email.
 */
function userHasVerifiedEmail(array $userRow): bool
{
    return !empty($userRow['email_verified_at']);
}

/**
 * Whether a referred user has completed identity / account verification (admin-approved).
 */
function userHasVerifiedAccount(array $userRow): bool
{
    return !empty($userRow['is_verified']);
}

/**
 * A referral counts toward rewards when email, account, and profile photo are complete.
 */
function isReferralUserQualified(array $userRow): bool
{
    return userHasVerifiedEmail($userRow)
        && userHasVerifiedAccount($userRow)
        && userHasProfilePhoto($userRow);
}

/**
 * Link a newly registered user to their referrer. Silent no-op on invalid input.
 */
function recordReferralOnSignup(int $referredUserId, string $referralCode, ?PDO $db = null): bool
{
    if (!referralProgramEnabled()) {
        return false;
    }

    if ($referredUserId <= 0) {
        return false;
    }

    $code = normalizeReferralCodeInput($referralCode);
    if ($code === '') {
        return false;
    }

    $db = $db ?? getDB();
    ensureReferralTables($db);

    $referrer = lookupReferrerByCode($code, $db);
    if (!$referrer || (int) $referrer['id'] === $referredUserId) {
        return false;
    }

    try {
        $stmt = $db->prepare('
            INSERT INTO user_referrals (referrer_id, referred_user_id, referral_code_used)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([(int) $referrer['id'], $referredUserId, $referrer['referral_code']]);
        return true;
    } catch (PDOException $e) {
        // Duplicate referred_user_id or other constraint — do not block signup.
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('recordReferralOnSignup failed: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * When a referred user completes a qualification step, attempt to pay any due rewards.
 */
function referralOnReferredUserUpdated(int $referredUserId, ?PDO $db = null): void
{
    if (!referralProgramEnabled() || $referredUserId <= 0) {
        return;
    }

    $db = $db ?? getDB();
    ensureReferralTables($db);

    $stmt = $db->prepare('SELECT referrer_id FROM user_referrals WHERE referred_user_id = ? LIMIT 1');
    $stmt->execute([$referredUserId]);
    $referrerId = (int) $stmt->fetchColumn();
    if ($referrerId <= 0) {
        return;
    }

    processReferralRewards($referrerId, $db);
}

/**
 * List referrals for a referrer with verification / photo status.
 *
 * @return array{
 *   referrals: list<array{
 *     id:int,
 *     name:string,
 *     joined_at:string,
 *     email_verified:bool,
 *     account_verified:bool,
 *     profile_photo:bool,
 *     qualified:bool
 *   }>,
 *   total:int,
 *   qualified_count:int,
 *   threshold:int,
 *   reward_amount:float,
 *   rewards_paid:int,
 *   total_earned:float,
 *   progress_to_next:int,
 *   milestones_available:int
 * }
 */
function getReferrerReferralSummary(int $referrerId, ?PDO $db = null): array
{
    $db = $db ?? getDB();
    ensureReferralTables($db);

    $threshold = referralRewardThreshold();
    $rewardAmount = referralRewardAmount();

    $stmt = $db->prepare('
        SELECT
            u.id,
            u.name,
            u.email_verified_at,
            u.is_verified,
            u.avatar_url,
            ur.created_at AS joined_at
        FROM user_referrals ur
        INNER JOIN users u ON u.id = ur.referred_user_id
        WHERE ur.referrer_id = ?
        ORDER BY ur.created_at DESC
    ');
    $stmt->execute([$referrerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $referrals = [];
    $qualifiedCount = 0;

    foreach ($rows as $row) {
        $emailVerified = userHasVerifiedEmail($row);
        $accountVerified = userHasVerifiedAccount($row);
        $profilePhoto = userHasProfilePhoto($row);
        $qualified = isReferralUserQualified($row);
        if ($qualified) {
            $qualifiedCount++;
        }

        $referrals[] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'joined_at' => (string) ($row['joined_at'] ?? ''),
            'email_verified' => $emailVerified,
            'account_verified' => $accountVerified,
            'profile_photo' => $profilePhoto,
            'qualified' => $qualified,
        ];
    }

    $paidStmt = $db->prepare('
        SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total
        FROM referral_rewards
        WHERE referrer_id = ?
    ');
    $paidStmt->execute([$referrerId]);
    $paidRow = $paidStmt->fetch(PDO::FETCH_ASSOC) ?: ['cnt' => 0, 'total' => 0];

    $rewardsPaid = (int) ($paidRow['cnt'] ?? 0);
    $totalEarned = round((float) ($paidRow['total'] ?? 0), 2);
    $milestonesAvailable = (int) floor($qualifiedCount / $threshold);
    $progressToNext = $qualifiedCount % $threshold;

    return [
        'referrals' => $referrals,
        'total' => count($referrals),
        'qualified_count' => $qualifiedCount,
        'threshold' => $threshold,
        'reward_amount' => $rewardAmount,
        'rewards_paid' => $rewardsPaid,
        'total_earned' => $totalEarned,
        'progress_to_next' => $progressToNext,
        'milestones_available' => $milestonesAvailable,
    ];
}

/**
 * Stable payment reference for a referral reward milestone (prevents duplicate history rows).
 */
function referralPaymentTransactionId(int $referrerId, int $milestone): string
{
    return sprintf('REF-%d-M%d', $referrerId, $milestone);
}

/**
 * Human-readable label for transaction history.
 */
function referralRewardDescription(int $milestone, int $threshold): string
{
    $qualifiedTarget = $milestone * $threshold;
    return sprintf(
        'Referral Reward — %d qualified referral%s (milestone %d)',
        $qualifiedTarget,
        $qualifiedTarget === 1 ? '' : 's',
        $milestone
    );
}

/**
 * Log referral reward in payments table for Transaction History (idempotent).
 */
function referralEnsurePaymentRecord(
    PDO $db,
    int $referrerId,
    int $milestone,
    float $amount,
    int $threshold
): void {
    $transactionId = referralPaymentTransactionId($referrerId, $milestone);

    $check = $db->prepare('SELECT id FROM payments WHERE transaction_id = ? LIMIT 1');
    $check->execute([$transactionId]);
    if ($check->fetch()) {
        return;
    }

    $description = referralRewardDescription($milestone, $threshold);
    $systemUserId = 1;

    $insert = $db->prepare("
        INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method, description, platform_fee)
        VALUES (?, ?, ?, ?, 'completed', 'Referral Reward', ?, 0.0)
    ");
    $insert->execute([
        $transactionId,
        $systemUserId,
        $referrerId,
        round($amount, 2),
        $description,
    ]);
}

/**
 * Backfill payment history rows for referral rewards already paid before logging existed.
 */
function referralSyncMissingPaymentRecords(int $referrerId, ?PDO $db = null): void
{
    if ($referrerId <= 0) {
        return;
    }

    $db = $db ?? getDB();
    ensureReferralTables($db);
    $threshold = referralRewardThreshold();

    $stmt = $db->prepare('
        SELECT milestone, amount, qualified_count
        FROM referral_rewards
        WHERE referrer_id = ?
        ORDER BY milestone ASC
    ');
    $stmt->execute([$referrerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $row) {
        referralEnsurePaymentRecord(
            $db,
            $referrerId,
            (int) $row['milestone'],
            (float) $row['amount'],
            $threshold
        );
    }
}

/**
 * Credit referrer wallet for each unpaid 10-qualified-referral milestone.
 *
 * @return array{paid_milestones:int,amount_credited:float,new_balance:float|null}
 */
function processReferralRewards(int $referrerId, ?PDO $db = null): array
{
    if (!referralProgramEnabled() || $referrerId <= 0) {
        return ['paid_milestones' => 0, 'amount_credited' => 0.0, 'new_balance' => null];
    }

    $db = $db ?? getDB();
    ensureReferralTables($db);

    $threshold = referralRewardThreshold();
    $rewardAmount = referralRewardAmount();

    try {
        $db->beginTransaction();

        $lock = $db->prepare('SELECT id, balance FROM users WHERE id = ? FOR UPDATE');
        $lock->execute([$referrerId]);
        $referrer = $lock->fetch(PDO::FETCH_ASSOC);
        if (!$referrer) {
            $db->rollBack();
            return ['paid_milestones' => 0, 'amount_credited' => 0.0, 'new_balance' => null];
        }

        referralSyncMissingPaymentRecords($referrerId, $db);

        $countStmt = $db->prepare('
            SELECT COUNT(*) AS cnt
            FROM user_referrals ur
            INNER JOIN users u ON u.id = ur.referred_user_id
            WHERE ur.referrer_id = ?
              AND u.email_verified_at IS NOT NULL
              AND u.is_verified = 1
              AND u.avatar_url IS NOT NULL
              AND TRIM(u.avatar_url) <> \'\'
        ');
        $countStmt->execute([$referrerId]);
        $qualifiedCount = (int) $countStmt->fetchColumn();

        $milestonesEarned = (int) floor($qualifiedCount / $threshold);
        if ($milestonesEarned <= 0) {
            $db->commit();
            return [
                'paid_milestones' => 0,
                'amount_credited' => 0.0,
                'new_balance' => round((float) ($referrer['balance'] ?? 0), 2),
            ];
        }

        $paidStmt = $db->prepare('SELECT milestone FROM referral_rewards WHERE referrer_id = ?');
        $paidStmt->execute([$referrerId]);
        $paidMilestones = array_map('intval', $paidStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

        $paidCount = 0;
        $amountCredited = 0.0;
        $balance = (float) ($referrer['balance'] ?? 0);

        for ($milestone = 1; $milestone <= $milestonesEarned; $milestone++) {
            if (in_array($milestone, $paidMilestones, true)) {
                continue;
            }

            $insert = $db->prepare('
                INSERT INTO referral_rewards (referrer_id, milestone, amount, qualified_count)
                VALUES (?, ?, ?, ?)
            ');
            $insert->execute([$referrerId, $milestone, $rewardAmount, $qualifiedCount]);

            referralEnsurePaymentRecord(
                $db,
                $referrerId,
                $milestone,
                $rewardAmount,
                $threshold
            );

            $balance += $rewardAmount;
            $amountCredited += $rewardAmount;
            $paidCount++;
        }

        if ($paidCount > 0) {
            $upd = $db->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $upd->execute([round($balance, 2), $referrerId]);
        }

        $db->commit();

        return [
            'paid_milestones' => $paidCount,
            'amount_credited' => round($amountCredited, 2),
            'new_balance' => round($balance, 2),
        ];
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('processReferralRewards failed: ' . $e->getMessage());
        }
        return ['paid_milestones' => 0, 'amount_credited' => 0.0, 'new_balance' => null];
    }
}

<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$clientId = 4;
$freelancerIds = [2, 6, 7];

// 1. Create Jobs
$jobs = [
    [
        'title' => 'Senior React Developer — Analytics Dashboard',
        'description' => 'Build interactive data visualizations, real-time WebSocket charts, and a filterable data table.',
        'category' => 'Web Development',
        'budget' => 5000.00,
        'budget_type' => 'fixed',
        'status' => 'open'
    ],
    [
        'title' => 'Backend API Development',
        'description' => 'Need a robust REST API using PHP/MySQL for a fintech platform.',
        'category' => 'Software Engineering',
        'budget' => 3000.00,
        'budget_type' => 'fixed',
        'status' => 'in_progress'
    ],
    [
        'title' => 'UI/UX Redesign — Dashboard',
        'description' => 'Redesign our main dashboard for better usability and modern aesthetics.',
        'category' => 'Design',
        'budget' => 50.00,
        'budget_type' => 'hourly',
        'status' => 'in_progress'
    ]
];

foreach ($jobs as $job) {
    $stmt = $db->prepare("INSERT INTO jobs (client_id, title, description, category, budget, budget_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$clientId, $job['title'], $job['description'], $job['category'], $job['budget'], $job['budget_type'], $job['status']]);
    $jobId = $db->lastInsertId();

    // 2. Create Proposals for each job
    foreach ($freelancerIds as $fId) {
        $bid = ($job['budget_type'] == 'fixed') ? $job['budget'] * 0.9 : 45.00;
        $pStmt = $db->prepare("INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status) VALUES (?, ?, ?, ?, ?)");
        $pStmt->execute([$jobId, $fId, $bid, "I am highly interested in this project...", 'pending']);
        $proposalId = $db->lastInsertId();

        // 3. Create a Contract for the 2nd and 3rd job
        if (($job['title'] == 'Backend API Development' || $job['title'] == 'UI/UX Redesign — Dashboard') && $fId == 2) {
            $cStmt = $db->prepare("INSERT INTO contracts (job_id, client_id, freelancer_id, proposal_id, amount, contract_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $cStmt->execute([$jobId, $clientId, $fId, $proposalId, $bid, $job['budget_type'], 'active']);
            
            // Mark proposal as accepted
            $db->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?")->execute([$proposalId]);
        }
    }
}

echo "Seeded jobs, proposals, and contracts successfully.\n";

<?php
require_once __DIR__ . '/../../../../../Sites/upwork/upwork project/includes/config.php';

try {
    $db = getDB();
    
    echo "--- USERS ---\n";
    $users = $db->query("SELECT id, name, email, avatar_url, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "ID: {$u['id']} | Name: {$u['name']} | Email: {$u['email']} | Avatar: {$u['avatar_url']} | Role: {$u['role']}\n";
    }
    
    echo "\n--- MESSAGES ---\n";
    $messages = $db->query("SELECT m.*, s.name as sender_name, r.name as receiver_name FROM messages m LEFT JOIN users s ON m.sender_id = s.id LEFT JOIN users r ON m.receiver_id = r.id ORDER BY m.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($messages as $m) {
        echo "ID: {$m['id']} | Sender: {$m['sender_name']} ({$m['sender_id']}) | Receiver: {$m['receiver_name']} ({$m['receiver_id']}) | Message: " . substr($m['message'], 0, 50) . "...\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
session_start();
require 'auth.php';
require 'db.php';

if (!isLoggedIn()) {
    exit;
}

$receiver_id = $_GET['receiver_id'] ?? null;
if (!$receiver_id) exit;

$current_user = $_SESSION['user_id'];

// Fetch messages excluding those the current user has deleted from their side
$stmt = $pdo->prepare("
    SELECT m.*, u.username as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE 
        (
            (m.sender_id = :user1 AND m.receiver_id = :user2)
            OR 
            (m.sender_id = :user2 AND m.receiver_id = :user1)
        )
        AND NOT (m.deleted_by_sender = 1 AND m.sender_id = :current_user)
    ORDER BY m.timestamp ASC
");
$stmt->execute([
    ':user1' => $current_user,
    ':user2' => $receiver_id,
    ':current_user' => $current_user
]);
$messages = $stmt->fetchAll();

// Mark messages as read
$pdo->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE")
    ->execute([$current_user, $receiver_id]);

foreach ($messages as $message):
    $isSender = $message['sender_id'] == $current_user;
?>
    <div class="message <?= $isSender ? 'sent' : 'received' ?>" data-id="<?= $message['id'] ?>">
        <div class="message-content">
            <?= htmlspecialchars($message['message']) ?>
            <?php if ($isSender): ?>
                <span class="delete-menu">
                    <button onclick="deleteMessage(<?= $message['id'] ?>, 'me')">🗑️ Me</button>
                    <button onclick="deleteMessage(<?= $message['id'] ?>, 'all')">🗑️ All</button>
                </span>
            <?php endif; ?>
        </div>
        <div class="message-time">
            <?= date('h:i A', strtotime($message['timestamp'])) ?>
            <?php if ($isSender): ?>
                <?= $message['is_read'] ? '<span class="read-status">✓✓</span>' : '<span class="read-status">✓</span>' ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
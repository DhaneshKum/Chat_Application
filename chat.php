<?php
session_start();
require 'auth.php';
require 'db.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Get all users except current user, ordered by most recent activity
$stmt = $pdo->prepare("
    SELECT u.*, MAX(COALESCE(m.timestamp, u.created_at)) as last_activity
    FROM users u
    LEFT JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE u.id != ?
    GROUP BY u.id
    ORDER BY last_activity DESC
");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();

// Get receiver ID from URL or default to most recent user
$receiver_id = $_GET['receiver_id'] ?? ($users[0]['id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="chat-container">
    <div class="sidebar">
        <div class="current-user">
            <h3><?= htmlspecialchars($_SESSION['username']) ?></h3>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <h2>Recent Chats</h2>
        <ul class="user-list">
            <?php foreach ($users as $user): ?>
                <li class="<?= $user['id'] == $receiver_id ? 'active' : '' ?>">
                    <a href="chat.php?receiver_id=<?= $user['id'] ?>">
                        <?= htmlspecialchars($user['username']) ?>
                        <?php
                        $unread = $pdo->prepare("
                            SELECT COUNT(*) as unread 
                            FROM messages 
                            WHERE sender_id = ? 
                            AND receiver_id = ? 
                            AND is_read = FALSE
                        ");
                        $unread->execute([$user['id'], $_SESSION['user_id']]);
                        $unread_count = $unread->fetch()['unread'];
                        if ($unread_count > 0): ?>
                            <span class="unread-count"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="chat-area">
        <?php if ($receiver_id): ?>
            <div class="chat-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">
                    Chat with 
                    <?= htmlspecialchars(
                        array_values(array_filter($users, fn($u) => $u['id'] == $receiver_id))[0]['username'] ?? 'Unknown'
                    ) ?>
                </h2>

                <form onsubmit="event.preventDefault(); clearChat();" style="margin: 0;">
                    <input type="hidden" id="sender_id" value="<?= $_SESSION['user_id'] ?>">
                    <input type="hidden" id="receiver_id_hidden" value="<?= $receiver_id ?>">
                    <button type="submit" style="
                        background-color: #e74c3c;
                        color: white;
                        border: none;
                        padding: 6px 12px;
                        border-radius: 4px;
                        cursor: pointer;
                    ">
                        🧹 Clear Chat
                    </button>
                </form>
            </div>

            <div class="messages" id="messages">
                <!-- Messages will be loaded via AJAX -->
                <div class="loading">Loading chat...</div>
            </div>

            <form class="message-form" id="messageForm">
                <input type="hidden" id="receiver_id" value="<?= $receiver_id ?>">
                <input type="text" id="messageInput" placeholder="Type your message..." autocomplete="off" required>
                <button type="submit">Send</button>
            </form>
        <?php else: ?>
            <div class="no-chat-selected">
                <p>Select a user to start chatting</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const messagesDiv = document.getElementById('messages');
const receiverId = document.getElementById('receiver_id')?.value;
const messageInput = document.getElementById('messageInput');

function scrollToBottom() {
    if (messagesDiv) {
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
}

function fetchMessages() {
    if (!receiverId) return;

    fetch(`getMessage.php?receiver_id=${receiverId}`)
        .then(response => response.text())
        .then(data => {
            if (messagesDiv.innerHTML !== data) {
                messagesDiv.innerHTML = data;
                scrollToBottom();
                attachDeleteListeners();
            }
        });
}

function attachDeleteListeners() {
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            const type = btn.dataset.type;
            const sender = document.getElementById('sender_id').value;
            if (confirm("Are you sure you want to delete this message?")) {
                fetch('deleteMessage.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}&type=${type}&sender=${sender}`
                }).then(() => fetchMessages());
            }
        };
    });
}

setInterval(fetchMessages, 2000);
fetchMessages();

document.getElementById('messageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const message = messageInput.value.trim();

    if (message) {
        fetch('process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `receiver_id=${receiverId}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.text())
        .then(() => {
            messageInput.value = '';
            fetchMessages();
        });
    }
});

function clearChat() {
    const sender = document.getElementById('sender_id').value;
    const receiver = document.getElementById('receiver_id_hidden').value;

    if (confirm("Are you sure you want to clear the entire chat?")) {
        fetch('clearChat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sender=${sender}&receiver=${receiver}`
        })
        .then(res => res.text())
        .then(() => {
            fetchMessages();
        });
    }
}
</script>
</body>
</html>

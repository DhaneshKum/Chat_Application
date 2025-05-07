<?php
include 'db.php';

$sender = $_POST['sender'];
$receiver = $_POST['receiver'];

if ($sender && $receiver) {
    $stmt = $conn->prepare("DELETE FROM messages WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?)");
    $stmt->bind_param("ssss", $sender, $receiver, $receiver, $sender);
    $stmt->execute();
    echo "chat_cleared";
} else {
    http_response_code(400);
    echo "Missing parameters";
}
?>
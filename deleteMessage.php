<?php
include 'db.php';

$id = $_POST['id'];
$type = $_POST['type'];
$sender = $_POST['sender'];

if ($type === 'me') {
    $stmt = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ? AND sender = ?");
    $stmt->bind_param("is", $id, $sender);
    $stmt->execute();
    echo "deleted_from_me";
} elseif ($type === 'all') {
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "deleted_from_all";
} else {
    http_response_code(400);
    echo "Invalid delete type";
}
?>


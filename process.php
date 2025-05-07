<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

// Insert message into database
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);

echo "Message sent";
?>
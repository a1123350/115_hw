<?php
session_start();
include('db.php');

$user_id = $_SESSION['user_id'] ?? 1;

// 將該使用者的所有未讀通知全部洗成已讀 (1)
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo "success";
?>
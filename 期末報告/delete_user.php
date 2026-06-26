<?php
session_start();
include('db.php');

// 🔒 頂級權限防火牆
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("⚠️ 權限不足！");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    // 防呆機制：不要刪除目前登入的自己
    if ($_SESSION['user_id'] == $user_id) {
        die("⚠️ 錯誤：您不能在登入狀態下刪除自己的系統管理員帳號！");
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// 💡 修正導向：精準回到整合後台的使用者分頁
header("Location: admin.php?tab=users"); 
exit;
?>
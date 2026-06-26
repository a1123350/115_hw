<?php
session_start();
include('db.php');

$success = '';
$error = '';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    // 安全起見：如果有多用戶，這裡未來可以加上 WHERE user_id = $_SESSION['user_id']
    $stmt = $conn->prepare("UPDATE appointments SET status = '已取消' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "您的預約已成功取消，期待下次有機會再與毛孩相見！";
    } else {
        $error = "❌ 系統取消失敗，請稍後再試或致電園區協助。";
    }
} else {
    $error = "❌ 預約參數不正確，無法執行取消。";
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>取消預約結果 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Noto Sans TC', sans-serif; background-color: #fffde7; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
    .container { background-color: rgba(255, 255, 255, 0.95); padding: 45px; border-radius: 24px; box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08); text-align: center; max-width: 480px; width: 100%; border-top: 6px solid #fbc02d; }
    h1 { color: #5d4037; margin-bottom: 20px; font-size: 24px; }
    .top-right-btn { position: absolute; top: 20px; right: 20px; padding: 10px 20px; background-color: #fbc02d; color: white; font-size: 14px; text-decoration: none; font-weight: bold; border-radius: 25px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: all 0.2s; }
    .top-right-btn:hover { background-color: #f9a825; transform: translateY(-2px); }
    .message { font-size: 16px; padding: 18px; border-radius: 12px; margin-top: 25px; line-height: 1.5; font-weight: 500; }
    .success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    .footer-link a { text-decoration: none; color: #fbc02d; font-weight: bold; font-size: 15px; transition: color 0.2s; }
    .footer-link a:hover { color: #f9a825; text-decoration: underline; }
  </style>
</head>
<body>

<a href="index.php" class="top-right-btn">🏠 回到首頁</a>

<div class="container">
  <h1>⚙️ 預約狀態變更</h1>

  <?php if ($success): ?>
    <div class="message success">🎉 <?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <p class="footer-link" style="margin-top: 35px;"><a href="visit_form.php">↩ 返回預約登記頁面</a></p>
</div>

</body>
</html>
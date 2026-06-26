<?php
session_start();
include('db.php');

// 🔒 權限防火牆：只有管理員可以執行此操作
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("⚠️ 權限不足，無法執行此操作。");
}

$success = '';
$error = '';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    // 🌟 1. 先查出預約的詳細資訊，用來填寫通知內容
    $info_stmt = $conn->prepare("SELECT user_id, visit_date, purpose FROM appointments WHERE id = ?");
    $info_stmt->bind_param("i", $id);
    $info_stmt->execute();
    $app_info = $info_stmt->get_result()->fetch_assoc();
    $info_stmt->close();

    // 2. 執行狀態更新
    $stmt = $conn->prepare("UPDATE appointments SET status = '已取消' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "該筆民眾預約/志工活動已成功駁回或取消。";
        
        // 🌟 3. 補上通知：預約被取消/駁回
        if ($app_info) {
            $user_id = $app_info['user_id'];
            $visit_date = $app_info['visit_date'];
            $purpose = $app_info['purpose'] ?? '參訪/志工活動';
            
            $noti_title = "🛑 預約申請取消/駁回通知";
            $noti_content = "您好，您先前預約於 " . htmlspecialchars($visit_date) . " 的【" . htmlspecialchars($purpose) . "】活動申請已由管理員「取消或駁回」。造成您的不便敬請見諒，如有疑問歡迎隨時聯繫園區。";
            
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $user_id, $noti_title, $noti_content);
            $noti_stmt->execute();
            $noti_stmt->close();
        }
    } else {
        $error = "❌ 資料庫更新失敗，請稍後再試。";
    }
    $stmt->close();
} else {
    $error = "❌ 預約 ID 無效。";
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>後台取消預約結果 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <meta http-equiv="refresh" content="3;url=admin.php?tab=visit"> 
  <style>
    body { font-family: 'Noto Sans TC', sans-serif; background-color: #fffde7; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
    .container { background-color: rgba(255, 255, 255, 0.95); padding: 45px; border-radius: 24px; box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08); text-align: center; max-width: 480px; width: 100%; border-top: 6px solid #e53935; }
    h1 { color: #5d4037; margin-bottom: 20px; font-size: 24px; font-weight: 700; }
    .top-right-btn { position: absolute; top: 20px; right: 20px; padding: 10px 20px; background-color: #fbc02d; color: white; font-size: 14px; text-decoration: none; font-weight: bold; border-radius: 25px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: all 0.2s; }
    .top-right-btn:hover { background-color: #f9a825; transform: translateY(-2px); }
    .message { font-size: 16px; padding: 18px; border-radius: 12px; margin-top: 25px; line-height: 1.5; font-weight: 500; }
    .success { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    .error { background-color: #f5f5f5; color: #616161; border: 1px solid #e0e0e0; }
    .footer-link a { display: inline-block; padding: 12px 30px; background-color: #fbc02d; color: white; font-weight: bold; text-decoration: none; border-radius: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: all 0.2s; }
    .footer-link a:hover { background-color: #f9a825; transform: translateY(-2px); }
    .loading-tip { font-size: 13px; color: #9e9e9e; margin-top: 20px; }
  </style>
</head>
<body>

<a href="index.php" class="top-right-btn">🏠 回到首頁</a>

<div class="container">
  <h1>🛑 預約取消審核</h1>

  <?php if ($success): ?>
    <div class="message success">⚙️ <?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <p class="footer-link" style="margin-top: 35px;">
    <a href="admin.php?tab=visit">立即返回管理頁面</a>
  </p>
  <div class="loading-tip">🔄 網頁將在 3 秒內 automatic 跳轉，請稍候...</div>
</div>

</body>
</html>
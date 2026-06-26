<?php
session_start();
include('db.php');

// 權限防火牆
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("權限不足");
}

$id = $_GET['id'] ?? 0;
$updated = false;

if ($id > 0) {
    // 🌟 1. 先查出這筆預約是哪個會員的，以及預約日期、項目，用來寫通知內容
    $info_stmt = $conn->prepare("SELECT user_id, visit_date, purpose FROM appointments WHERE id = ?");
    $info_stmt->bind_param("i", $id);
    $info_stmt->execute();
    $app_info = $info_stmt->get_result()->fetch_assoc();
    $info_stmt->close();

    // 2. 執行更新狀態
    $stmt = $conn->prepare("UPDATE appointments SET status = '已確認' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $updated = true;
        
        // 🌟 3. 補上通知：預約核准成功，發送小鈴鐺訊息
        if ($app_info) {
            $user_id = $app_info['user_id'];
            $visit_date = $app_info['visit_date'];
            $purpose = $app_info['purpose'] ?? '參訪/志工活動';
            
            $noti_title = "📅 預約申請核准通知";
            $noti_content = "您好！您預約於 " . htmlspecialchars($visit_date) . " 的【" . htmlspecialchars($purpose) . "】活動已通過管理員審核，狀態已更新為「已確認」。期待您的到來！";
            
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $user_id, $noti_title, $noti_content);
            $noti_stmt->execute();
            $noti_stmt->close();
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>預約確認結果</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <meta http-equiv="refresh" content="3;url=admin.php?tab=visit"> 
  <style>
    body { font-family: 'Noto Sans TC', sans-serif; background-color: #fffde7; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
    .container { background-color: #fff; padding: 45px 50px; border-radius: 24px; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06); text-align: center; max-width: 480px; width: 100%; border-top: 6px solid #43a047; }
    h1 { color: #2e7d32; font-size: 24px; margin-bottom: 15px; font-weight: 700; }
    h1.err { color: #e53935; }
    p { color: #616161; font-size: 16px; margin-bottom: 30px; }
    .btn { display: inline-block; padding: 12px 30px; background-color: #fbc02d; color: white; font-weight: bold; text-decoration: none; border-radius: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: all 0.2s; }
    .btn:hover { background-color: #f9a825; transform: translateY(-2px); }
    .top-right-btn { position: absolute; top: 20px; right: 20px; padding: 10px 18px; background-color: #fbc02d; color: white; font-size: 14px; text-decoration: none; font-weight: bold; border-radius: 25px; transition: all 0.2s; }
    .top-right-btn:hover { background-color: #f9a825; }
    .loading-tip { font-size: 13px; color: #9e9e9e; margin-top: 20px; }
  </style>
</head>
<body>

  <a href="index.php" class="top-right-btn">🏠 回首頁</a>

  <div class="container" style="border-top-color: <?= $updated ? '#43a047' : '#e53935' ?>;">
    <?php if ($updated): ?>
      <h1>✅ 預約核准成功</h1>
      <p>該筆民眾預約/志工排班已成功核准，系統已同步將狀態更動為「已確認」。</p>
    <?php else: ?>
      <h1 class="err">❌ 審核操作失敗</h1>
      <p>找不到對應的預約序號，或資料庫連線超時，請重新操作。</p>
    <?php endif; ?>

    <a href="admin.php?tab=visit" class="btn">立即返回管理後台</a>
    <div class="loading-tip">🔄 網頁將在 3 秒內自動跳轉，請稍候...</div>
  </div>

</body>
</html>
<?php
date_default_timezone_set('Asia/Taipei');
$conn = new mysqli("fdb1028.awardspace.net", "4646539_wpress07fe855f", "asdfghjkl456", "4646539_wpress07fe855f");

// 檢查連線是否成功
if ($conn->connect_error) {
    // 錯誤記錄寫入 log（伺服器管理時好用）
    error_log("連線失敗: " . $conn->connect_error);

    // 顯示自訂錯誤頁面
    echo '
    <!DOCTYPE html>
    <html lang="zh-Hant">
    <head>
      <meta charset="UTF-8">
      <title>連線錯誤</title>
      <style>
        body {
          font-family: "Noto Sans TC", sans-serif;
          background: linear-gradient(135deg, #fff8e1, #ffebee);
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
        }
        .error-box {
          background-color: #ffffff;
          border-left: 6px solid #e53935;
          padding: 40px;
          border-radius: 12px;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          text-align: center;
          max-width: 400px;
        }
        h1 {
          color: #e53935;
          font-size: 24px;
          margin-bottom: 20px;
        }
        p {
          color: #666;
          font-size: 16px;
        }
      </style>
    </head>
    <body>
      <div class="error-box">
        <h1>❌ 資料庫連線失敗</h1>
        <p>請稍後再試，或聯絡管理員。</p>
      </div>
    </body>
    </html>
    ';
    exit;
}

// 🌟 🆕 核心關鍵：連線成功後，立刻強制將這次的 MySQL 連線時區改成台灣時間 (+08:00)
$conn->query("SET time_zone = '+08:00';"); 
?>

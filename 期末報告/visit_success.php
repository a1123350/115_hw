<?php
session_start();
include('db.php');

// 從 session 取得剛建立的預約 ID
$appointment_id = $_SESSION['appointment_id'] ?? null;

if (!$appointment_id) {
    echo "⚠️ 無法取得預約資料，請重新預約。";
    exit;
}

// 從資料庫撈出該預約資訊
$stmt = $conn->prepare("SELECT a.*, u.username FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

if (!$appointment) {
    echo "⚠️ 找不到預約資料。";
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>報名成功 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background-color: #fffde7;
      margin: 0; padding: 0;
      display: flex; justify-content: center; align-items: center;
      height: 100vh;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }

    .container {
      background-color: rgba(255, 253, 231, 0.95);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      text-align: center;
      max-width: 600px;
      width: 90%;
    }

    h1 { color: #f9a825; margin-bottom: 20px; }
    .info { font-size: 18px; margin-bottom: 10px; color: #555; text-align: left; max-width: 400px; margin-left: auto; margin-right: auto; }
    .tips { margin-top: 20px; background-color: #fffde7; padding: 15px; border-radius: 10px; border-left: 6px solid #fbc02d; text-align: left; }
    .btn-group { margin-top: 30px; }
    .btn { background-color: #fbc02d; color: white; padding: 10px 20px; border: none; border-radius: 8px; margin: 10px; text-decoration: none; font-weight: bold; font-size: 16px; transition: background-color 0.3s ease; display: inline-block; }
    .btn:hover { background-color: #f9a825; }
    .cancel-btn { background-color: #e53935; }
    .cancel-btn:hover { background-color: #c62828; }
    .top-right-btn { position: absolute; top: 20px; right: 20px; padding: 10px 18px; background-color: #fbc02d; color: white; font-size: 14px; text-decoration: none; font-weight: bold; border-radius: 25px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); transition: background-color 0.3s ease, transform 0.2s; }
    .top-right-btn:hover { background-color: #f9a825; transform: scale(1.05); }
  </style>
</head>
<body>

  <a href="index.php" class="top-right-btn">回首頁</a>

  <div class="container">
    <h1>🎉 報名表單送出成功！</h1>

    <div class="info">👤 預約人：<?= htmlspecialchars($appointment['username']) ?></div>
    <div class="info">🎯 報名項目：<strong style="color:#e65100;"><?= htmlspecialchars($appointment['purpose']) ?></strong></div>
    <div class="info">📅 活動日期：<?= htmlspecialchars($appointment['visit_date']) ?></div>
    <div class="info">👥 報名人數：<?= htmlspecialchars($appointment['group_size']) ?> 人</div>
    <div class="info">📞 聯絡電話：<?= htmlspecialchars($appointment['phone']) ?></div>

    <div class="tips">
      <strong>💡 園區貼心提醒：</strong>
      <ul>
        <li>請記住您的報名日期，並準時到達園區。</li>
        
        <?php if(strpos($appointment['purpose'], '參訪') !== false): ?>
          <li>參訪時請勿自行攜帶外食餵食動物，並遵守現場動線指引。</li>
        <?php else: ?>
          <li>志工活動需要適度勞動，<b>請穿著輕便、不怕髒的長褲與全包運動鞋</b>（請勿穿拖鞋、涼鞋）。</li>
          <li>園區會準備洗狗與防護設備，建議可以多帶一套更換衣物。</li>
        <?php endif; ?>
        
        <li>若臨時有事需要取消，請登入系統於使用者主頁提早取消釋出名額。</li>
      </ul>
    </div>

    <div class="btn-group">
      <a href="visit_form.php" class="btn">回上頁</a>
      <a href="user_cancel_appointment.php?id=<?= $appointment['id'] ?>" class="btn cancel-btn" onclick="return confirm('確定要取消這次的活動/參訪報名嗎？')">❌ 取消預約</a>
    </div>
  </div>

  <script>
    const images = ['image/home page.jpeg', 'image/login page.jpg', 'image/user.webp'];
    let index = 0;
    function changeBackground() {
      document.body.style.backgroundImage = `url('${images[index]}')`;
      index = (index + 1) % images.length;
    }
    setInterval(changeBackground, 4000);
    changeBackground();
  </script>
</body>
</html>
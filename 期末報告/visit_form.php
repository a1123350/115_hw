<?php
session_start();
// 🔐 安全檢查：未登入者直接踢走
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=visit_form.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>活動與志工報名 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background-color: #fffde7;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      transition: background-image 1s ease-in-out;
      padding: 20px;
      box-sizing: border-box;
    }

    .container {
      background-color: rgba(255, 255, 255, 0.93);
      padding: 35px 40px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      text-align: center;
      max-width: 480px;
      width: 100%;
      z-index: 1;
    }

    h1 { color: #f9a825; font-size: 24px; margin-bottom: 20px; }

    form label {
      display: block;
      text-align: left;
      margin-top: 15px;
      font-weight: bold;
      color: #5d4037;
    }

    form input, form select {
      width: 100%;
      padding: 11px;
      margin: 6px 0 12px 0;
      border-radius: 10px;
      border: 1px solid #ccc;
      font-size: 15px;
      box-sizing: border-box;
      outline: none;
    }
    
    form input:focus, form select:focus {
      border-color: #fbc02d;
    }

    .submit-btn {
      background-color: #f9a825;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
      font-weight: bold;
      transition: 0.3s;
      margin-top: 15px;
    }

    .submit-btn:hover { background-color: #fbc02d; }

    .top-right-btn, .top-left-btn {
      position: absolute;
      top: 20px;
      padding: 10px 18px;
      background-color: #fbc02d;
      color: white;
      font-size: 14px;
      text-decoration: none;
      font-weight: bold;
      border-radius: 25px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: background-color 0.3s ease, transform 0.2s;
      z-index: 10;
    }
    .top-right-btn { right: 20px; }
    .top-left-btn { left: 20px; }
    .top-right-btn:hover, .top-left-btn:hover { background-color: #f9a825; transform: scale(1.05); } 
    
    .notice-box {
      background: #fffde7; border-left: 4px solid #fbc02d; padding: 10px;
      text-align: left; font-size: 13px; color: #6d4c41; border-radius: 4px; margin-bottom: 15px; line-height: 1.4;
    }
  </style>
</head>
<body>
  <a href="index.php" class="top-right-btn">回首頁</a>
  <a href="user.php" class="top-left-btn">回使用者頁面</a>
  
  <div class="container">
    <h1>📅 預約參訪 / 志工活動報名</h1>
    
    <div class="notice-box">
      📌 <b>人數上限說明：</b><br>
      為維護活動品質，每日全園上限 5 人。若選擇日期時系統跳出滿額提示，請調整人數或改選其他日期。
    </div>

    <form method="POST" action="visit_submit.php" id="bookingForm">
      
      <label for="purpose">🎯 選擇報名項目：</label>
      <select id="purpose" name="purpose" required>
        <option value="單純參訪(領養諮詢)">🏠 單純參訪 / 領養諮詢 (適合家庭)</option>
        <option value="愛心遛狗活動">🐕 週末假日 - 愛心遛狗揪團 (需滿18歲)</option>
        <option value="洗澡與環境志工">🧼 週末假日 - 毛孩洗澡與環境整理志工</option>
      </select>

      <label for="visit_date">選擇日期：</label>
      <input type="date" id="visit_date" name="visit_date" required>
      
      <label for="group_size">👥 報名人數 (含自己)：</label>
      <input type="number" id="group_size" name="group_size" min="1" max="5" value="1" required>

      <label for="phone">聯絡電話：</label>
      <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" placeholder="例如：0912345678" required>
      
      <button type="submit" class="submit-btn">送出預約報名</button>
    </form>
  </div>

  <script>
    // 1. 背景圖片輪換
    const images = ['image/home page.jpeg', 'image/login page.jpg', 'image/user.webp'];
    let imgIndex = 0;
    function changeBackground() {
      document.body.style.backgroundImage = `url('${images[imgIndex]}')`;
      imgIndex = (imgIndex + 1) % images.length;
    }
    setInterval(changeBackground, 4000);
    changeBackground();

    // 2. 設定日曆最少天數限制（不能選今天與過去）
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const yyyy = tomorrow.getFullYear();
    const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const dd = String(tomorrow.getDate()).padStart(2, '0');
    document.getElementById('visit_date').min = `${yyyy}-${mm}-${dd}`;

    // 3. 【進階體驗精髓】AJAX 抓取滿額日期，並實施點選校驗
    let fullDates = [];
    
    // 頁面加載時先向 API 索取完全滿員(>=10人)的日期
    fetch('api_get_full_dates.php')
      .then(response => response.json())
      .then(data => {
          fullDates = data;
      })
      .catch(err => console.error("無法取得額滿數據", err));

    // 當使用者修改「日期」或「人數」時，進行動態攔截
    const dateInput = document.getElementById('visit_date');
    const form = document.getElementById('bookingForm');

    dateInput.addEventListener('change', function() {
        if (fullDates.includes(this.value)) {
            alert('❌ 不好意思！此日期的總報名人數已達 5 人上限，請選擇其他日期！');
            this.value = ''; // 清空選擇
        }
    });

    // 表單送出前的終極把關
    form.addEventListener('submit', function(e) {
        if (fullDates.includes(dateInput.value)) {
            alert('❌ 該日期已額滿，請重新選擇日期。');
            e.preventDefault(); // 攔截不讓表單送出
        }
    });
  </script>
</body>
</html>
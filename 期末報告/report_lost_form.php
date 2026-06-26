<?php
session_start();
// 🔐 安全檢查：強制必須登入會員才能開啟此頁面
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=report_lost_form.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>發布走失協尋 - 寵物之家</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-house/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Noto Sans TC', sans-serif; background: linear-gradient(to bottom right, #ffebee, #ffcdd2); min-height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center; padding: 60px 20px 40px 20px; box-sizing: border-box; position: relative; }
    
    /* 🛠️ 專屬紅色系頂部導覽按鈕區 */
    .top-nav {
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 100;
    }

    .nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px; /* 文字與圖示的間距 */
      padding: 10px 20px;
      background-color: #e53935; /* 改為配合走失主題的質感紅 */
      color: white;
      font-size: 15px;
      text-decoration: none;
      font-weight: bold;
      border-radius: 25px;
      box-shadow: 0 4px 12px rgba(211, 47, 47, 0.2);
      transition: background-color 0.3s ease, transform 0.2s, box-shadow 0.2s;
    }

    .nav-btn:hover {
      background-color: #b71c1c;
      transform: translateY(-2px); /* 向上微浮，更有質感 */
      box-shadow: 0 6px 15px rgba(211, 47, 47, 0.3);
    }
    
    /* 原有表單樣式 */
    .form-container { background: white; padding: 30px 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; width: 100%; box-sizing: border-box; margin-top: 20px; }
    h1 { color: #d32f2f; text-align: center; margin-bottom: 20px; font-size: 24px; }
    label { display: block; margin-top: 15px; font-weight: bold; color: #555; text-align: left; }
    input[type="text"], input[type="tel"], select, textarea { width: 100%; padding: 10px; margin-top: 5px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; box-sizing: border-box; }
    textarea { resize: vertical; height: 100px; }
    .submit-btn { width: 100%; background: #d32f2f; color: white; border: none; padding: 12px; margin-top: 25px; border-radius: 25px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .submit-btn:hover { background: #b71c1c; }
    
    /* 底部分流按鈕 */
    .btn-group { display: flex; justify-content: center; margin-top: 20px; }
    .back-btn { flex: 1; text-align: center; color: #d32f2f; text-decoration: none; font-size: 14px; padding: 10px; background: #ffebee; border-radius: 15px; transition: background 0.2s; font-weight: bold; border: 1px solid #ffcdd2; }
    .back-btn:hover { background: #ffcdcc; }
  </style>
</head>
<body>

<div class="top-nav">
  <a href="index.php" class="nav-btn"><i class="fa-solid fa-house"></i>回首頁</a>
</div>

<div class="form-container">
  <h1>🚨 填寫毛孩走失協尋資料</h1>
  <form method="POST" action="report_lost_submit.php" enctype="multipart/form-data">
    
    <label for="pet_name">毛孩名字：</label>
    <input type="text" id="pet_name" name="pet_name" placeholder="例如：小黃、阿橘" required>

    <label for="type">寵物類別：</label>
    <select id="type" name="type" required>
      <option value="狗">狗</option>
      <option value="貓">貓</option>
      <option value="其他">其他</option>
    </select>

    <label for="city">遺失縣市：</label>
    <select id="city" name="city" required>
       <option value="all">顯示全部地區</option>
        <option value="台北市">台北市</option>
        <option value="新北市">新北市</option>
        <option value="基隆市">基隆市</option>
        <option value="桃園市">桃園市</option>
        <option value="新竹市">新竹市</option>
        <option value="新竹縣">新竹縣</option>
        <option value="宜蘭縣">宜蘭縣</option>
        <option value="苗栗縣">苗栗縣</option>
        <option value="台中市">台中市</option>
        <option value="彰化縣">彰化縣</option>
        <option value="南投縣">南投縣</option>
        <option value="雲林縣">雲林縣</option>
        <option value="嘉義縣">嘉義縣</option>
        <option value="台南市">台南市</option>
        <option value="高雄市">高雄市</option>
        <option value="花蓮縣">花蓮縣</option>
        <option value="台東縣">台東縣</option>
        <option value="屏東縣">屏東縣</option>
    </select>

    <label for="district">遺失區域：</label>
    <input type="text" id="district" name="district" placeholder="例如：楠梓區、西屯區" required>

    <label for="last_seen">常出沒地點 / 具體遺失地：</label>
    <input type="text" id="last_seen" name="last_seen" placeholder="例如：捷運站2號出口附近、XX公園大草皮" required>

    <label for="description">外觀與特徵描述：</label>
    <textarea id="description" name="description" placeholder="例如：左耳有剪耳、走失時戴著黃色項圈、看到食物會坐下，非常親人..." required></textarea>

    <label for="contact_phone">主人聯絡電話：</label>
    <input type="tel" id="contact_phone" name="contact_phone" pattern="[0-9]{10}" placeholder="例如：0912345678" required>

    <label for="pet_image">毛孩照片 (限制 3MB 以下)：</label>
    <input type="file" id="pet_image" name="pet_image" accept="image/*" required>

    <button type="submit" class="submit-btn">送出審核通報</button>
    
    <div class="btn-group">
      <a href="user.php" class="back-btn">👤 返回會員頁面</a>
    </div>
  </form>
</div>

</body>
</html>
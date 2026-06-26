<?php
$pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>領養須知</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      transition: background-image 1s ease-in-out;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      position: relative;
    }

    .brand-title {
      position: absolute;
      top: 20px;
      width: 100%;
      text-align: center;
      font-size: 36px;
      font-weight: bold;
      color: #fff59d;
      z-index: 2;
      text-shadow: 1px 1px 3px rgba(255,255,255,0.9);
    }

    .container {
      background-color: rgba(255, 255, 255, 0.9);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      text-align: center;
      max-width: 600px;
      z-index: 1;
    }

    h2 {
      color: #a67c00;
      margin-bottom: 20px;
    }

    p {
      color: #333;
      margin: 10px 0;
      line-height: 1.6;
    }

    button {
      margin-top: 30px;
      padding: 12px 30px;
      background-color: #fbc02d;
      color: white;
      border: none;
      border-radius: 25px;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s;
    }

    button:hover {
      background-color: #f9a825;
      transform: scale(1.05);
    }

   .top-left-buttons {
      position: absolute;
      top: 20px;
      left: 20px;
      display: flex;
      gap: 12px;
      z-index: 10;
    }

    .top-left-btn {
      padding: 10px 18px;
      background-color: #fbc02d;
      color: white;
      font-weight: bold;
      border-radius: 25px;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: background-color 0.3s ease, transform 0.2s;
    }

    .top-left-btn:hover {
      background-color: #f9a825;
      transform: scale(1.05);
    }
  </style>
</head>
<body>
  <div class="brand-title">寵物之家</div>
  <div class="top-left-buttons">
  <a href="pet_list.php" class="top-left-btn">回前頁</a>
</div>

  <div class="container">
    <h2>📌 領養須知</h2>
    <p>1. 領養後需定期照護，禁止棄養。</p>
    <p>2. 領養者需年滿 20 歲，具穩定居住與經濟能力。</p>
    <p>3. 寵物需定期施打疫苗與健康檢查。</p>
    <p>4. 寵物之家保留最終審核與撤回領養的權利。</p>
    
    <form method="get" action="adopt_form.php">
      <input type="hidden" name="pet_id" value="<?= htmlspecialchars($pet_id) ?>">
      <button type="submit">我同意，開始申請</button>
    </form>
  </div>

  <!-- 背景輪播 -->
  <script>
    const images = [
      'image/home page.jpeg',
      'image/login page.jpg',
      'image/user.webp'
    ];
    let index = 0;
    function changeBackground() {
      document.body.style.backgroundImage = `url('${images[index]}')`;
      index = (index + 1) % images.length;
    }
    setInterval(changeBackground, 3000);
    changeBackground();
  </script>
</body>
</html>

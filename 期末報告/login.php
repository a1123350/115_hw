<?php
// 接收從首頁按鈕傳過來的跳轉目標，如果沒有帶參數，登入成功就預設去 user.php
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'user.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>登入頁面 - 寵物之家</title>
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
      top: 30px;
      width: 100%;
      text-align: center;
      font-size: 32px;
      font-weight: bold;
      color: #fff59d;
      z-index: 1000;
      text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.7);
    }

    .login-container {
      background: rgba(255, 255, 255, 0.7);
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      z-index: 999;
    }

    h2 {
      text-align: center;
      color: #b28900;
      margin-bottom: 30px;
      font-size: 24px;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
      color: #a67c00;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 16px;
      box-sizing: border-box;
      background: rgba(255, 255, 255, 0.7);     
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #fbc02d;
      color: white;
      border: 1px solid #f9a825;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s;
    }

    button:hover {
      background-color: #f9a825;
      transform: scale(1.03);
    }

    p {
      text-align: center;
      margin-top: 20px;
    }

    a {
      color: #a67c00;
      text-decoration: none;
      font-weight: bold;
    }

    a:hover {
      text-decoration: underline;
    }

    .top-right {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 999;
    }

    .home-link {
      background-color: #fff59d;
      color: #8d6e00;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      transition: background-color 0.3s, transform 0.2s ease;
    }

    .home-link:hover {
      background-color: #fff176;
      transform: scale(1.05);
    }
  </style>
</head>
<body>

  <div class="brand-title">寵物之家</div>

  <div class="login-container">
    <div class="top-right">
      <a href="index.php" class="home-link">回首頁</a>
    </div>
    <h2>登入</h2>
    <form method="POST" action="login_check.php">
      <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

      <label for="username">帳號：</label>
      <input type="text" name="username" id="username" required>

      <label for="password">密碼：</label>
      <input type="password" name="password" id="password" required>

      <button type="submit">登入</button>

      <p><a href="register.html">還沒有帳號？點此註冊</a></p>
    </form>
  </div>

  <script>
    const images = [
      'image/home page.jpeg',
      'image/login page.jpg',
      'image/user.webp'
    ];

    let index = 0;
    const body = document.body;

    function changeBackground() {
      body.style.backgroundImage = `url('${images[index]}')`;
      index = (index + 1) % images.length;
    }

    setInterval(changeBackground, 2000);
    changeBackground();
  </script>

</body>
</html>
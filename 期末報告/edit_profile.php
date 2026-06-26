<?php
session_start();
include('db.php');

$user_id = $_SESSION['user_id'] ?? 1; // 測試用預設為 1

// 一、處理表單送出更新 (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // 基本驗證
    if (empty($nickname)) {
        $error_msg = "暱稱不能為空！";
    } else {
        // 更新資料庫
        $update_stmt = $conn->prepare("UPDATE users SET nickname = ?, email = ?, phone = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $nickname, $email, $phone, $user_id);
        
        if ($update_stmt->execute()) {
            echo "<script>alert('個人資料更新成功！'); location.href='user.php';</script>";
            exit;
        } else {
            $error_msg = "系統錯誤，更新失敗！";
        }
    }
}

// 二、查詢目前的最新資料 (用於填入 input 的 value)
$stmt = $conn->prepare("SELECT username, nickname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$username = htmlspecialchars($user['username'] ?? '');
$nickname_val = htmlspecialchars($user['nickname'] ?? '');
$email_val = htmlspecialchars($user['email'] ?? '');
$phone_val = htmlspecialchars($user['phone'] ?? '');
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>修改個人資料 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background-color: #fffde7;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }

    .container {
      background-color: rgba(255, 255, 255, 0.9);
      padding: 40px 50px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      max-width: 420px;
      width: 100%;
      z-index: 1;
    }

    h1 {
      color: #a67c00;
      margin-bottom: 25px;
      font-size: 24px;
      text-align: center;
    }

    label {
      display: block;
      text-align: left;
      font-weight: bold;
      color: #5d4037;
      margin-top: 12px;
      font-size: 14px;
    }

    form input {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      margin-bottom: 5px;
      border-radius: 10px;
      border: 1px solid #ccc;
      font-size: 16px;
      box-sizing: border-box;
    }

    form input:disabled {
      background-color: #e0e0e0;
      color: #777;
      cursor: not-allowed;
    }

    .error-text {
      color: #ff3d00;
      font-size: 13px;
      text-align: left;
      margin-bottom: 10px;
    }

    .btn-group {
      display: flex;
      gap: 10px;
      margin-top: 25px;
    }

    .submit-btn, .cancel-btn {
      flex: 1;
      padding: 12px;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      text-align: center;
      text-decoration: none;
      transition: 0.2s;
    }

    .submit-btn {
      background-color: #f9a825;
      color: white;
    }

    .submit-btn:hover {
      background-color: #fbc02d;
    }

    .cancel-btn {
      background-color: #e0e0e0;
      color: #555;
    }

    .cancel-btn:hover {
      background-color: #d5d5d5;
    }
  </style>
</head>
<body style="background-image: url('image/home page.jpeg');">

  <div class="container">
    <h1>📝 修改個人資料</h1>
    
    <?php if(isset($error_msg)): ?>
      <div class="error-text">⚠️ <?= $error_msg ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <label>帳號 (不可修改)：</label>
      <input type="text" value="<?= $username ?>" disabled>

      <label for="nickname">個人暱稱：</label>
      <input type="text" id="nickname" name="nickname" value="<?= $nickname_val ?>" required placeholder="請輸入您想顯示的暱稱">

      <label for="email">電子信箱：</label>
      <input type="email" id="email" name="email" value="<?= $email_val ?>" placeholder="example@mail.com">

      <label for="phone">聯絡電話：</label>
      <input type="tel" id="phone" name="phone" value="<?= $phone_val ?>" placeholder="0912345678">

      <div class="btn-group">
        <a href="user.php" class="cancel-btn">取消</a>
        <button type="submit" class="submit-btn">儲存修改</button>
      </div>
    </form>
  </div>

</body>
</html>
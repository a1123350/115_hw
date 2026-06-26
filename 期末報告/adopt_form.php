<?php
session_start();
include('db.php');

// 初始化變數避免錯誤
$phone_number = '';
$reason = '';
$error = '';
$pet = null; // 明確初始化 $pet
$pet_id = 0;

// GET 模式：顯示寵物資料
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pet_id'])) {
    $pet_id = (int)$_GET['pet_id'];
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $pet = $stmt->get_result()->fetch_assoc();
    if (!$pet) {
        die('找不到該寵物');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $phone_number = trim($_POST['phone_number'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // 重新撈取寵物資料，避免重新整理時畫面空白
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $pet = $stmt->get_result()->fetch_assoc();

    if (!isset($_SESSION['user_id'])) {
        $error = "請先登入才能申請領養。";
    } else {
        $user_id = $_SESSION['user_id'];

        if ($reason === '' || $phone_number === '') {
            $error = "請填寫所有欄位";
        } elseif (!preg_match('/^09\d{8}$/', $phone_number)) {
            $error = "請輸入正確的台灣手機號碼格式 (09xxxxxxxx)";
        } else {
            // 寫入申請資料
            $stmt = $conn->prepare("INSERT INTO adoptions (user_id, pet_id, phone_number, reason, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiss", $user_id, $pet_id, $phone_number, $reason);
            $stmt->execute();

            // 更新寵物狀態
            $update = $conn->prepare("UPDATE pets SET status = '申請中' WHERE id = ?");
            $update->bind_param("i", $pet_id);
            $update->execute();

            header("Location: pet_list.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>申請領養</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
  <style>
    /* 您的 CSS 樣式保留不變 */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Noto Sans TC', sans-serif; background-size: cover; background-position: center; display: flex; justify-content: center; align-items: center; height: 100vh; position: relative; }
    .brand-title { position: absolute; top: 20px; width: 100%; text-align: center; font-size: 36px; font-weight: bold; color: #fff59d; z-index: 2; text-shadow: 1px 1px 3px rgba(255,255,255,0.9); }
    .container { background-color: rgba(255, 255, 255, 0.85); padding: 40px 50px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); text-align: center; max-width: 480px; width: 100%; z-index: 1; }
    h1 { color: #a67c00; margin-bottom: 20px; font-size: 26px; }
    .notice { font-size: 16px; background: #fffde7; color: #5d4037; padding: 10px; border-radius: 10px; margin-bottom: 20px; text-align: left; }
    label { display: block; text-align: left; margin-bottom: 5px; color: #5d4037; font-weight: bold; }
    input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; margin-bottom: 15px; }
    textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; resize: vertical; min-height: 120px; margin-bottom: 20px; }
    button { width: 100%; padding: 12px; background-color: #fbc02d; color: white; border: none; border-radius: 25px; font-size: 16px; cursor: pointer; transition: 0.3s; }
    button:hover { background-color: #f9a825; transform: scale(1.05); }
    .error { color: red; margin-bottom: 15px; font-weight: bold; }
    .top-right-btn, .top-left-btn { position: absolute; top: 20px; padding: 10px 18px; background-color: #fbc02d; color: white; font-size: 14px; text-decoration: none; font-weight: bold; border-radius: 25px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: 0.3s; z-index: 2; }
    .top-right-btn { right: 20px; }
    .top-left-btn { left: 20px; }
    .top-right-btn:hover, .top-left-btn:hover { background-color: #f9a825; transform: scale(1.05); }
  </style>
</head>
<body>
  <div class="brand-title">寵物之家</div>
  <a href="index.html" class="top-right-btn">回首頁</a>
  <a href="user.html" class="top-left-btn">回使用者頁面</a>

  <div class="container">
    <h1>領養申請表單</h1>

    <?php if (!empty($pet)): ?>
      <div class="notice">
        <strong>🐾 您想領養的寵物：</strong><br>
        <?= htmlspecialchars($pet['name']) ?>（品種：<?= htmlspecialchars($pet['type']) ?>，年齡：<?= htmlspecialchars($pet['age']) ?>）
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm();">
      <input type="hidden" name="pet_id" value="<?= htmlspecialchars($pet_id) ?>">

      <label for="phone_number">聯絡電話（手機）：</label>
      <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($phone_number) ?>" placeholder="例如：0912345678" required>

      <label for="reason">領養原因：</label>
      <textarea name="reason" id="reason" placeholder="請說明為何您領養這隻寵物" required><?= htmlspecialchars($reason) ?></textarea>

      <button type="submit">提交申請</button>
    </form>
  </div>

  <script>
    // 補上遺失的前端驗證函式
    function validateForm() {
        const phone = document.getElementById('phone_number').value.trim();
        const regex = /^09\d{8}$/;
        if (!regex.test(phone)) {
            alert('請輸入正確的台灣手機號碼格式 (09xxxxxxxx)');
            return false;
        }
        return true;
    }

    // 背景圖片輪播
    const images = ['image/home page.jpeg', 'image/login page.jpg', 'image/user.webp'];
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
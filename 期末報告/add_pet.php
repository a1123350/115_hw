<?php
session_start();
include('db.php');

// 🔒 頂級權限防火牆
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("⚠️ 權限不足！");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $age = (int)$_POST['age'];
    $status = $_POST['status'] ?? '可領養';

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        $upload_dir = 'image/';
        $target_file = $upload_dir . 'pet_' . uniqid() . '.' . $image_extension;
        $image_path = $target_file;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($image_tmp, $target_file)) {
            $error = "❌ 圖片上傳失敗，請檢查目錄與權限";
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO pets (name, type, age, image, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $name, $type, $age, $image_path, $status);

        if ($stmt->execute()) {
            $success = "🎉 新增成功！網頁將在 3 秒後自動返回寵物管理後台。";
        } else {
            $error = "資料庫錯誤：" . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>新增寵物 - 寵物之家後台</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <?php if ($success): ?>
    <meta http-equiv="refresh" content="3;url=admin.php?tab=pets">
  <?php endif; ?>
  <style>
    body { font-family: 'Noto Sans TC', sans-serif; padding: 40px 20px; background-color: #fffde7; }
    .form-container { max-width: 500px; margin: auto; background-color: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    h1 { text-align: center; color: #5d4037; font-size: 24px; margin-bottom: 25px; border-bottom: 3px solid #fbc02d; padding-bottom: 10px; }
    label { font-weight: bold; color: #5d4037; display: block; margin-bottom: 5px; margin-top: 10px; }
    input[type="text"], input[type="number"], select, input[type="file"] { width: 100%; padding: 12px; margin-bottom: 16px; border-radius: 10px; border: 1px solid #ddd; font-size: 15px; }
    button { width: 100%; background-color: #fbc02d; color: white; padding: 12px; font-size: 16px; font-weight: bold; border: none; border-radius: 10px; cursor: pointer; transition: background 0.2s; }
    button:hover { background-color: #f9a825; }
    .message { text-align: center; margin-bottom: 16px; padding: 12px; border-radius: 8px; font-weight: 500; background-color: #e8f5e9; color: #2e7d32; }
    .error { background-color: #ffebee; color: #c62828; }
    .back-link { display: block; text-align: center; margin-top: 20px; color: #f9a825; text-decoration: none; font-weight: bold; }
    .back-link:hover { text-decoration: underline; }
    .top-left-btn { position: absolute; top: 20px; left: 20px; padding: 10px 18px; background-color: #fbc02d; color: white; font-weight: bold; border-radius: 20px; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
  </style>
</head>
<body>

<a href="admin.php?tab=pets" class="top-left-btn">← 回管理後台</a>

<div class="form-container">
  <h1>🐾 新增收容毛孩</h1>

  <?php if ($success): ?>
    <div class="message"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="message error"><?= $error ?></div>
  <?php endif; ?>

  <form action="" method="POST" enctype="multipart/form-data">
    <label>毛孩名字</label>
    <input type="text" name="name" placeholder="例如：大黃" required>

    <label>品種 / 類型</label>
    <input type="text" name="type" placeholder="例如：米克斯貓、柴犬" required>

    <label>估計年齡 (歲)</label>
    <input type="number" name="age" min="0" value="0" required>

    <label>目前狀態</label>
    <select name="status">
      <option value="可領養">可領養</option>
      <option value="申請中">申請中</option>
      <option value="已領養">已領養</option>
    </select>

    <label>上傳寵物照片</label>
    <input type="file" name="image" accept="image/*">

    <button type="submit">✨ 確認上架毛孩</button>
  </form>

  <a class="back-link" href="admin.php?tab=pets">← 放棄並返回寵物管理</a>
</div>

</body>
</html>
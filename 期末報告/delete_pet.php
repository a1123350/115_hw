<?php
session_start();
include('db.php');

// 🔒 頂級權限防火牆
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("⚠️ 權限不足！");
}

$success = '';
$error = '';
$pet = null;

$id = $_GET['id'] ?? 0;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pet = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pet) {
    $image_path = $pet['image'] ?? '';
    
    // 移除實體圖片避免留下一堆無用死檔
    if ($image_path && file_exists($image_path)) {
        @unlink($image_path); 
    }

    $stmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // 💡 修正導向：精準回到新後台的寵物管理頁籤
        header("Location: admin.php?tab=pets");
        exit; 
    } else {
        $error = "❌ 刪除失敗，請稍後再試！";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>確認刪除寵物 - 寵物之家後台</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans TC', sans-serif; padding: 40px 20px; background-color: #fffde7; }
        .form-container { max-width: 500px; margin: auto; background-color: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06); text-align: center; border-top: 6px solid #d32f2f; }
        h1 { color: #c62828; font-size: 22px; margin-bottom: 20px; font-weight: 700; }
        .pet-preview { background-color: #fafafa; padding: 15px; border-radius: 10px; margin: 20px 0; text-align: left; border: 1px solid #eee; }
        .pet-preview p { margin: 8px 0; color: #5d4037; font-size: 15px; }
        button { width: 100%; background-color: #d32f2f; color: white; padding: 12px; font-size: 16px; font-weight: bold; border: none; border-radius: 10px; cursor: pointer; transition: background 0.2s; }
        button:hover { background-color: #b71c1c; }
        .error { color: red; margin-bottom: 15px; font-weight: bold; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #f57f17; text-decoration: none; font-weight: bold; }
        .back-link:hover { text-decoration: underline; }
        .top-left-btn { position: absolute; top: 20px; left: 20px; padding: 10px 18px; background-color: #fbc02d; color: white; font-weight: bold; border-radius: 20px; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<a href="admin.php?tab=pets" class="top-left-btn">← 回管理後台</a>

<div class="form-container">
    <h1>⚠️ 警告：確定要完全移除此毛孩？</h1>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($pet): ?>
        <div class="pet-preview">
            <p><strong>🐾 毛孩名字：</strong> <?= htmlspecialchars($pet['name']) ?></p>
            <p><strong>🧬 品種項目：</strong> <?= htmlspecialchars($pet['type']) ?></p>
            <p><strong>📊 目前狀態：</strong> <?= htmlspecialchars($pet['status']) ?></p>
        </div>
        <p style="color:#757575; font-size:13px; margin-bottom:20px;">注意：此操作將會連同寵物照片自伺服器中永久刪除，且無法復原。</p>
        <form method="POST">
            <button type="submit">🔥 確定永久刪除</button>
        </form>
    <?php else: ?>
        <p>❌ 查無此寵物資料或已被移除。</p>
    <?php endif; ?>

    <a class="back-link" href="admin.php?tab=pets">↩ 取消操作並返回</a>
</div>

</body>
</html>
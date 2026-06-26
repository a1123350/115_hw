<?php
session_start();
include('db.php');

// 🔒 頂級權限防火牆
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("<h2 style='text-align:center; margin-top:50px; color:#d32f2f;'>⚠️ 權限不足！</h2>");
}

$success = '';
$error = '';

// 確保 ID 有正確拿到，允許從 GET 或 POST 取得
$id = 0;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
}

if ($id > 0) {
    // 撈取原始資料
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pet = $result->fetch_assoc();

    if (!$pet) {
        die("<h2 style='text-align:center; margin-top:50px; color:#d32f2f;'>⚠️ 找不到該寵物資料。</h2>");
    }

    // 處理表單送出
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $age = (int)$_POST['age'];
        $status = $_POST['status'] ?? '可領養';
        
        $image_path = $pet['image']; // 預設使用舊圖
        
        // 檢查是否有上傳新檔案
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_name = $_FILES['image']['name'];
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
            
            // 🛡️ 安全修正：圖片副檔名白名單檢查
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($image_extension, $allowed_extensions)) {
                $error = "❌ 不允許的上傳檔案類型！僅限 JPG, PNG, GIF, WEBP。";
            } else {
                $upload_dir = 'image/';
                $target_file = $upload_dir . 'pet_' . uniqid() . '.' . $image_extension;

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (move_uploaded_file($image_tmp, $target_file)) {
                    // 刪除舊圖
                    if (!empty($pet['image']) && file_exists($pet['image'])) {
                        @unlink($pet['image']);
                    }
                    $image_path = $target_file;
                } else {
                    $error = "❌ 新圖片上傳失敗";
                }
            }
        }

        // 如果沒有錯誤，執行更新
        if (!$error) {
            $stmt = $conn->prepare("UPDATE pets SET name = ?, type = ?, age = ?, image = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssissi", $name, $type, $age, $image_path, $status, $id);

            if ($stmt->execute()) {
                $success = "🎉 資料更新成功！網頁將在 3 秒後自動返回寵物管理頁籤。";
                // 同步更新當前陣列，避免外觀顯示舊資料
                $pet['name'] = $name;
                $pet['type'] = $type;
                $pet['age'] = $age;
                $pet['status'] = $status;
                $pet['image'] = $image_path; 
                
                // 💡 關鍵修正：透過 Header 直接在 3 秒後把管理員送回 admin.php 的 pets 頁籤
                header("Refresh: 3; url=admin.php?tab=pets");
            } else {
                $error = "資料庫錯誤：" . $stmt->error;
            }
        }
    }
} else {
    $error = "無效的寵物 ID";
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>✏️ 編輯收容毛孩資料</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Noto Sans TC', sans-serif; }
        body { background-color: #fffde7; padding: 40px 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .edit-container { max-width: 600px; width: 100%; background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-top: 6px solid #fbc02d; }
        h2 { color: #5d4037; margin-bottom: 25px; text-align: center; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; }
        .alert-danger { background-color: #ffebee; color: #c62828; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; color: #5d4037; margin-bottom: 8px; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 12px; border: 2px solid #ffe082; border-radius: 10px; font-size: 15px; outline: none; transition: border 0.2s; }
        input[type="text"]:focus, input[type="number"]:focus, select:focus { border-color: #fbc02d; }
        .current-img { margin-top: 10px; max-width: 150px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: block; }
        .btn-area { display: flex; gap: 10px; margin-top: 30px; }
        .btn { flex: 1; padding: 12px; border: none; border-radius: 25px; font-size: 16px; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; transition: all 0.2s; }
        .btn-submit { background-color: #fbc02d; color: white; }
        .btn-submit:hover { background-color: #f9a825; transform: translateY(-2px); }
        .btn-cancel { background-color: #e0e0e0; color: #616161; }
        .btn-cancel:hover { background-color: #bdbdbd; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="edit-container">
    <h2>🐾 編輯毛孩資料</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($id > 0 && isset($pet)): ?>
        <form method="POST" action="edit_pet.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $pet['id'] ?>">

            <div class="form-group">
                <label>毛孩名字</label>
                <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>品種 / 類型</label>
                <input type="text" name="type" value="<?= htmlspecialchars($pet['type']) ?>" required>
            </div>

            <div class="form-group">
                <label>年齡 (歲)</label>
                <input type="number" name="age" value="<?= (int)$pet['age'] ?>" min="0" required>
            </div>

            <div class="form-group">
                <label>目前狀態</label>
                <select name="status">
                    <option value="可領養" <?= $pet['status'] === '可領養' ? 'selected' : '' ?>>🟢 可領養</option>
                    <option value="已領養" <?= $pet['status'] === '已領養' ? 'selected' : '' ?>>🔴 已領養</option>
                    <option value="醫療中" <?= $pet['status'] === '醫療中' ? 'selected' : '' ?>>🟡 醫療中</option>
                </select>
            </div>

            <div class="form-group">
                <label>更換毛孩照片 (若不更換請留空)</label>
                <input type="file" name="image" accept="image/*">
                <?php if (!empty($pet['image'])): ?>
                    <p style="font-size: 13px; color: #757575; margin-top: 8px;">當前照片：</p>
                    <img src="<?= htmlspecialchars($pet['image']) ?>" class="current-img" alt="current pet">
                <?php endif; ?>
            </div>

            <div class="btn-area">
                <a href="admin.php?tab=pets" class="btn btn-cancel">返回列表</a>
                <button type="submit" class="btn btn-submit">💾 儲存修改</button>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
<?php
session_start();
include('db.php');

// 檢查使用者是否登入
$user_id = $_SESSION['user_id'] ?? 1; // 測試用預設為 1

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    // 檢查是否有上傳錯誤
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('上傳失敗，請稍後再試。'); location.href='user.php';</script>";
        exit;
    }

    // 檢查檔案類型是否為圖片
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        echo "<script>alert('只允許上傳 JPG, PNG 或 WEBP 格式的圖片！'); location.href='user.php';</script>";
        exit;
    }

    // 限制檔案大小 (例如 2MB)
    $max_size = 2 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        echo "<script>alert('圖片檔案不能超過 2MB！'); location.href='user.php';</script>";
        exit;
    }

    // 建立唯一的檔名防止覆蓋 (例如: avatar_1_1623456789.jpg)
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    
    // 設定儲存路徑
    $upload_dir = 'uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $target_path = $upload_dir . $new_filename;

    // 將暫存檔移至目標路徑
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        
        // 可選：先查詢舊的頭貼檔案，將其刪除（節省伺服器空間）
        $query_old = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
        $query_old->bind_param("i", $user_id);
        $query_old->execute();
        $old_avatar = $query_old->get_result()->fetch_assoc()['avatar'] ?? '';
        if (!empty($old_avatar) && file_exists($old_avatar) && $old_avatar !== 'image/default-avatar.png') {
            unlink($old_avatar);
        }

        // 更新資料庫中的頭貼路徑
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $target_path, $user_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('頭貼更新成功！'); location.href='user.php';</script>";
        } else {
            echo "<script>alert('資料庫更新失敗！'); location.href='user.php';</script>";
        }
    } else {
        echo "<script>alert('檔案搬移失敗，請檢查資料夾寫入權限。'); location.href='user.php';</script>";
    }
} else {
    header("Location: user.php");
    exit;
}
?>
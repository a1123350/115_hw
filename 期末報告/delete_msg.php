<?php
session_start();
include('db.php');

// 1. 安全檢查：如果沒登入，直接踢走
if (!isset($_SESSION['username'])) {
    die("權限不足，請先登入系統！");
}

// 2. 檢查有沒有帶入要刪除的留言 ID
if (isset($_GET['id'])) {
    $msg_id = intval($_GET['id']);
    $current_user = $_SESSION['username'];

    // 3. 安全檢查：先去資料庫查這則留言，確認是不是當前登入的使用者寫的
    $stmt = $conn->prepare("SELECT user_name, image_path FROM guestbook WHERE id = ?");
    $stmt->bind_param("i", $msg_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $msg = $result->fetch_assoc();
        
        // 核心安全把關：資料庫裡的使用者必須等於目前 Session 的使用者
        if ($msg['user_name'] === $current_user) {
            
            // 📸 實體檔案刪除邏輯：如果留言有上傳過圖片，且檔案真的存在，就先把它從硬碟刪除
            if (!empty($msg['image_path']) && file_exists($msg['image_path'])) {
                unlink($msg['image_path']); // 刪除 uploads/ 資料夾裡的實體圖檔
            }

            // 4. 執行刪除資料庫欄位
            $delete_stmt = $conn->prepare("DELETE FROM guestbook WHERE id = ?");
            $delete_stmt->bind_param("i", $msg_id);
            $delete_stmt->execute();

            // 刪除成功，導回原留言板頁面
            header("Location: guestbook.php?delete=success");
            exit();
        } else {
            die("危險操作！你沒有權限刪除他人的留言。");
        }
    } else {
        die("找不到該則留言！");
    }
} else {
    header("Location: guestbook.php");
    exit();
}
?>
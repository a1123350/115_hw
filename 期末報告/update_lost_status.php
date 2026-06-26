<?php
session_start();
include('db.php');

// 🔒 基本檢查：必須是登入會員才能操作
if (!isset($_SESSION['user_id'])) {
    die("⚠️ 請先登入系統");
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];
    $current_user = $_SESSION['user_id'];
    $is_admin = ($_SESSION['role'] ?? '') === 'admin';

    // 先抓出這筆走失資料，確認權限，順便多抓毛孩名字 (pet_name) 填寫通知用
    $stmt = $conn->prepare("SELECT user_id, pet_name FROM lost_pets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pet = $result->fetch_assoc();

    if (!$pet) {
        die("⚠️ 找不到該筆通報資料");
    }

    $pet_owner_id = $pet['user_id'];
    $pet_name = htmlspecialchars($pet['pet_name']);

    // 權限核心判定：只有【管理員】或是【該案件的通報主人】才可以把狀態改成「已團圓」
    if ($status === '已團圓') {
        if ($is_admin || $pet_owner_id == $current_user) {
            $update = $conn->prepare("UPDATE lost_pets SET status = '已團圓' WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();
            
            // 🌟 補上小鈴鐺：發送結案恭喜通知給通報主人
            $noti_title = "🎉 走失毛孩團圓大喜訊！";
            $noti_content = "恭喜您！您通報協尋的毛孩【" . $pet_name . "】狀態已成功更新為「已團圓」結案。非常高興看到寶貝平安回家，祝福你們未來的生活平安快樂！";
            
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $pet_owner_id, $noti_title, $noti_content);
            $noti_stmt->execute();
            
            // 根據是從前台還是後台點擊，跳轉回對應頁面
            if (isset($_GET['from']) && $_GET['from'] === 'front') {
                echo "<script>alert('恭喜團圓！已通知管理員，確認後將為您撤下案件。'); location.href='index.php';</script>";
            } else {
                header("Location: admin.php?tab=lost&msg=success");
            }
            exit();
        } else {
            die("⚠️ 您不是該案件的通報人，無法操作。");
        }
    } 
    
    // 徹底撤下（刪除）：這個進階權限通常「只留給管理員」
    if ($status === '撤下') {
        if ($is_admin) {
            $delete = $conn->prepare("DELETE FROM lost_pets WHERE id = ?");
            $delete->bind_param("i", $id);
            $delete->execute();
            
            // 🌟 補上小鈴鐺：通知主人案件已經由管理員下架維護
            $noti_title = "🚨 協尋案件撤下通知";
            $noti_content = "您好，您先前發布的毛孩【" . $pet_name . "】協尋公告，已由系統管理員執行下架撤案（可能因時間過久或已順利結案維護）。若有任何疑問歡迎聯繫園區。";
            
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $pet_owner_id, $noti_title, $noti_content);
            $noti_stmt->execute();

            header("Location: admin.php?tab=lost&msg=deleted");
            exit();
        } else {
            die("⚠️ 只有系統管理員有權限徹底撤下資料");
        }
    }

} else {
    header("Location: index.php");
    exit();
}
?>
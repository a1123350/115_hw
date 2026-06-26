<?php
session_start();
include('db.php');

// 🔒 頂級權限防火牆：防止外人越權操作 API
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("⚠️ 權限不足，無法執行此操作。");
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $application_id = (int)$_GET['id'];
    $new_status = $_GET['status'];

    // 🌟 1. 在更新前，先查出這筆申請是「哪位會員(user_id)」申請的、以及「寵物名稱」
    $info_stmt = $conn->prepare("SELECT ar.user_id, p.name AS pet_name FROM adoptions ar JOIN pets p ON ar.pet_id = p.id WHERE ar.id = ?");
    $info_stmt->bind_param("i", $application_id);
    $info_stmt->execute();
    $info_res = $info_stmt->get_result()->fetch_assoc();

    // 更新申請的狀態
    $stmt = $conn->prepare("UPDATE adoptions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $application_id);
    $stmt->execute();

    // 根據申請 ID 找到對應的 pet_id
    $pet_stmt = $conn->prepare("SELECT pet_id FROM adoptions WHERE id = ?");
    $pet_stmt->bind_param("i", $application_id);
    $pet_stmt->execute();
    $pet_result = $pet_stmt->get_result();
    $row = $pet_result->fetch_assoc();

    if ($row) {
        $pet_id = $row['pet_id'];

        // 如果是駁回，則把 pet 狀態改回「可領養」
        if ($new_status === '駁回') {
            $update_pet = $conn->prepare("UPDATE pets SET status = '可領養' WHERE id = ?");
            $update_pet->bind_param("i", $pet_id);
            $update_pet->execute();
        }
        // 如果是核准，則把 pet 狀態改成「已領養」
        elseif ($new_status === '核准') {
            $update_pet = $conn->prepare("UPDATE pets SET status = '已領養' WHERE id = ?");
            $update_pet->bind_param("i", $pet_id);
            $update_pet->execute();
        }
    }

    // 🌟 2. 如果成功撈到會員資訊，寫入一筆站內通知訊息
    if ($info_res) {
        $target_user_id = $info_res['user_id'];
        $pet_name = htmlspecialchars($info_res['pet_name']);
        
        $noti_title = "🐾 領養申請結果通知";
        if ($new_status === '核准') {
            $noti_content = "好消息！您申請領養的毛孩【" . $pet_name . "】已經通過管理員審核囉！請近期留意電話或簡訊，園區將有專人與您聯繫後續的互動與領養手續事宜。";
        } else {
            $noti_content = "您好，感謝您對毛孩【" . $pet_name . "】的喜愛。由於該毛孩申請人數較多，本次未能成功媒合。園區還有許多等家的浪浪，歡迎您繼續關注！";
        }

        // 塞入 notifications 資料表 (預設未讀 is_read = 0)
        $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
        $noti_stmt->bind_param("iss", $target_user_id, $noti_title, $noti_content);
        $noti_stmt->execute();
    }

    // 💡 精準重定向回新後台的「領養表單管理」頁籤
    header("Location: admin.php?tab=adoptions");
    exit;
}
?>
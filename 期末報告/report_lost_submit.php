<?php
session_start();
include('db.php');

// 🔒 檢查是否有登入
if (!isset($_SESSION['user_id'])) {
    die("未授權的操作！請先登入系統。");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $pet_name = trim($_POST['pet_name']);
    $type = $_POST['type'];
    $city = $_POST['city'];
    $district = trim($_POST['district']);
    $last_seen = trim($_POST['last_seen']);
    $description = trim($_POST['description']);
    $contact_phone = trim($_POST['contact_phone']);
    
    $image_destination = null;

    // 🔒 圖片資安防禦檢查機制
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['pet_image']['tmp_name'];
        $file_name = $_FILES['pet_image']['name'];
        $file_size = $_FILES['pet_image']['size'];
        
        // 1. 限制上傳大小 3MB
        if ($file_size > 3 * 1024 * 1024) {
            die("<script>alert('圖片檔案過大，請勿超過 3MB！'); history.back();</script>");
        }

        // 2. 限制安全副檔名與真實圖像 MIME 檢查
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $check_image = getimagesize($file_tmp_path);
        
        if (in_array($file_ext, $allowed_exts) && $check_image !== false) {
            
            // 3. 重新命名檔案，防止檔名重複或惡意代碼腳本攻擊
            $new_file_name = "lost_" . $user_id . "_" . time() . "." . $file_ext;
            
            // 建立存放資料夾
            $upload_dir = 'uploads/lost_pets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $image_destination = $upload_dir . $new_file_name;
            move_uploaded_file($file_tmp_path, $image_destination);
        } else {
            die("<script>alert('不合法的檔案格式！僅限 JPG, PNG, WebP。'); history.back();</script>");
        }
    }

    // 🔒 寫入資料庫（預設狀態為「審核中」，此時前台首頁抓不到這筆，安全上架）
    $stmt = $conn->prepare("INSERT INTO lost_pets (user_id, pet_name, type, city, district, last_seen, description, image_path, contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '審核中')");
    $stmt->bind_param("issssssss", $user_id, $pet_name, $type, $city, $district, $last_seen, $description, $image_destination, $contact_phone);
    
    if ($stmt->execute()) {
        echo "<script>alert('通報資料已送出！管理員審核完成後會立即在首頁刊登。'); window.location.href='user.php';</script>";
        exit();
    } else {
        echo "系統錯誤，請聯繫管理人員。";
    }
}
?>
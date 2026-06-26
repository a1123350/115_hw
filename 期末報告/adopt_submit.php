<?php
require 'config.php';
require 'db.php';


// 確認使用者已登入
if (!isset($_SESSION['user_id'])) {
    echo "請先登入！<br><a href='login.html'>登入</a>";
    exit;
}

// 獲取表單資料
$user_id = $_SESSION['user_id'];
$pet_id = $_POST['pet_id'] ?? null;
$reason = $_POST['reason'] ?? '';

if ($pet_id && $reason) {
    // 插入領養申請資料
    $stmt = $pdo->prepare("INSERT INTO adoption_requests (user_id, pet_id, reason, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $pet_id, $reason]);

    echo "領養申請已提交成功！<br><a href='user.php'>返回使用者頁面</a>";
} else {
    echo "請填寫所有必要欄位！<br><a href='adopt_form.html'>返回</a>";
}
?>

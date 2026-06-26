<?php
session_start();
include('db.php');

$user_id = $_SESSION['user_id'] ?? null;
$purpose = $_POST['purpose'] ?? '單純參訪(領養諮詢)';
$group_size = isset($_POST['group_size']) ? (int)$_POST['group_size'] : 1;
$visit_date = $_POST['visit_date'] ?? '';
$phone = $_POST['phone'] ?? '';
$created_at = date('Y-m-d H:i:s');

if ($user_id && $visit_date && $phone && $group_size > 0) {
    
    // 🔒 後端終極防禦：設定上限為 5 人
    $max_daily_limit = 5;

    // 🔍 【關鍵修正】去資料庫統計該日期目前「有效」的總名額（排除各式取消與駁回）
    $check_sql = "SELECT SUM(group_size) as current_total FROM appointments WHERE visit_date = ? AND status IN ('待確認', '已確認')";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $visit_date);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result()->fetch_assoc();
    $already_booked = $check_res['current_total'] ? (int)$check_res['current_total'] : 0;

    // ⚖️ 判斷：已經報名人數 + 本次申請人數 是否超過上限？
    if (($already_booked + $group_size) > $max_daily_limit) {
        $remaining = $max_daily_limit - $already_booked;
        echo "<script>
            alert('❌ 報名失敗！該日期剩餘名額僅剩 {$remaining} 位，您申請了 {$group_size} 位已超載。\\n請回上頁修正人數或更換日期！');
            window.history.back();
        </script>";
        exit();
    }

    // ✍️ 判定通過，將新欄位寫入資料庫
    $stmt = $conn->prepare("INSERT INTO appointments (user_id, visit_date, purpose, group_size, phone, created_at, status) VALUES (?, ?, ?, ?, ?, ?, '待確認')");
    $stmt->bind_param("ississ", $user_id, $visit_date, $purpose, $group_size, $phone, $created_at);

    if ($stmt->execute()) {
        $_SESSION['appointment_id'] = $conn->insert_id;
        header("Location: visit_success.php");
        exit;
    } else {
        echo "❌ 資料儲存失敗：" . $stmt->error;
    }
} else {
    echo "⚠️ 請確認已登入且表單資料填寫完整";
}
?>
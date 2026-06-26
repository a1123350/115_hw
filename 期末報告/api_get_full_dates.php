<?php
header('Content-Type: application/json; charset=utf-8');
include('db.php');

$full_dates = [];

// 🔍 【關鍵修正】只統計「待確認」與「已確認」的總人數，若大於等於 5 人，才算額滿
$sql = "SELECT visit_date FROM appointments 
        WHERE status IN ('待確認', '已確認') 
        GROUP BY visit_date 
        HAVING SUM(group_size) >= 5";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $full_dates[] = $row['visit_date'];
    }
}

// 以 JSON 格式回傳給 visit_form.php
echo json_encode($full_dates);
exit();
?>
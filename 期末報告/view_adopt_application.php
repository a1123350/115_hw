<?php
session_start();
include('db.php');

// 🔒 頂級權限防火牆
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("<h2 style='text-align:center; margin-top:50px; color:#d32f2f;'>⚠️ 權限不足，本頁面僅限管理員查看。</h2>");
}

if (!isset($_GET['id'])) {
    die("❌ 缺少申請 ID");
}

$application_id = (int)$_GET['id'];

// 撈取申請詳細資料
$stmt = $conn->prepare("SELECT ar.*, u.username AS user_name, p.name AS pet_name
                        FROM adoptions ar
                        JOIN users u ON ar.user_id = u.id
                        JOIN pets p ON ar.pet_id = p.id
                        WHERE ar.id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

if (!$application) {
    die("❌ 找不到該筆領養申請紀錄");
}

// 💡 根據目前狀態決定標籤顏色
$status = $application['status'];
$badge_style = "background-color: #ffe082; color: #e65100;"; // 預設：審核中（橘黃）
if ($status === '核准') {
    $badge_style = "background-color: #e8f5e9; color: #2e7d32;"; // 綠色
} elseif ($status === '駁回') {
    $badge_style = "background-color: #ffebee; color: #c62828;"; // 紅色
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>🐾 查看領養申請詳情 - 寵物之家後台</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans TC', sans-serif; background-color: #fffde7; padding: 50px 20px; margin: 0; }
        h1 { color: #5d4037; text-align: center; margin-bottom: 30px; font-weight: 700; font-size: 26px; }
        .info-box { max-width: 650px; margin: auto; background-color: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 40px; border-top: 6px solid #fbc02d; }
        .info-item { display: flex; border-bottom: 1px solid #f5f5f5; padding: 18px 0; font-size: 16px; align-items: flex-start; }
        .info-item:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #755f57; width: 130px; flex-shrink: 0; display: inline-block; }
        .value { color: #333; flex-grow: 1; line-height: 1.6; }
        .status-badge { padding: 4px 14px; border-radius: 20px; font-weight: bold; font-size: 14px; display: inline-block; }
        .btn-back { text-align: center; margin-top: 35px; }
        .btn-back a { display: inline-block; background-color: #fbc02d; color: white; padding: 12px 35px; border-radius: 25px; text-decoration: none; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: all 0.2s; }
        .btn-back a:hover { background-color: #f9a825; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,0.15); }
    </style>
</head>
<body>

<h1>📜 領養申請詳細審查</h1>

<div class="info-box">
    <div class="info-item">
        <span class="label">👤 申請人帳號：</span>
        <span class="value"><strong><?= htmlspecialchars($application['user_name']) ?></strong></span>
    </div>
    <div class="info-item">
        <span class="label">🐱 盼望領養毛孩：</span>
        <span class="value" style="color:#e65100; font-weight:bold; font-size: 18px;"><?= htmlspecialchars($application['pet_name']) ?></span>
    </div>
    <div class="info-item">
        <span class="label">📅 提出申請時間：</span>
        <span class="value"><?= htmlspecialchars($application['created_at']) ?></span>
    </div>
    <div class="info-item">
        <span class="label">📞 填寫聯絡電話：</span>
        <span class="value" style="letter-spacing: 1px; font-weight: 500;"><?= htmlspecialchars($application['phone_number'] ?? '未填寫') ?></span>
    </div>
    <div class="info-item">
        <span class="label">💡 家庭環境與原因：</span>
        <span class="value" style="background-color: #fafafa; padding: 12px; border-radius: 8px; border: 1px solid #eee; display: block;"><?= nl2br(htmlspecialchars($application['reason'])) ?></span>
    </div>
    <div class="info-item">
        <span class="label">🚦 當前審核狀態：</span>
        <span class="value">
            <span class="status-badge" style="<?= $badge_style ?>"><?= htmlspecialchars($status) ?></span>
        </span>
    </div>
</div>

<div class="btn-back">
    <a href="admin.php?tab=adoptions">← 返回審核管理中心</a>
</div>

</body>
</html>
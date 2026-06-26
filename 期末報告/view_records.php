<?php
session_start();
include('db.php');

// 權限防火牆
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("權限不足");
}

if (!isset($_GET['user_id'])) {
    die("缺少 user_id");
}

$user_id = (int)$_GET['user_id'];

$user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    die("找不到該使用者");
}

$records_stmt = $conn->prepare("
    SELECT a.created_at, p.name AS pet_name, a.status
    FROM adoptions a
    JOIN pets p ON a.pet_id = p.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$records_stmt->bind_param("i", $user_id);
$records_stmt->execute();
$records = $records_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>領養紀錄 - <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans TC', sans-serif; background-color: #fffde7; padding: 50px 20px; }
        h1 { text-align: center; color: #5d4037; font-weight: 700; margin-bottom: 10px; }
        .sub-title { text-align: center; color: #757575; margin-bottom: 30px; }
        .table-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 20px; text-align: center; font-size: 15px; }
        th { background-color: #fff59d; color: #5d4037; font-weight: bold; }
        td { border-bottom: 1px solid #eee; color: #424242; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fffde7; }
        .status-tag { font-weight: bold; color: #f57c00; }
        .no-data { text-align: center; padding: 50px; color: #9e9e9e; font-size: 16px; background: white; max-width: 800px; margin: 0 auto; border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
        .top-btn { text-align: center; margin-top: 35px; }
        .btn { background-color: #fbc02d; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: all 0.2s; }
        .btn:hover { background-color: #f9a825; transform: translateY(-2px); display: inline-block; }
    </style>
</head>
<body>

<h1>🔍 會員歷程追蹤</h1>
<div class="sub-title">正在查看用戶 <strong><?= htmlspecialchars($user['username']) ?></strong> 的歷史領養申請紀錄</div>

<div class="table-container">
    <?php if ($records->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>⏱️ 申請時間</th>
                <th>🐱 寵物名稱</th>
                <th>🚦 審查狀態</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($record = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($record['created_at']) ?></td>
                    <td><strong style="color:#5d4037;"><?= htmlspecialchars($record['pet_name']) ?></strong></td>
                    <td><span class="status-tag"><?= htmlspecialchars($record['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="no-data">🍃 該會員目前非常單純，尚無任何毛孩領養申請紀錄。</div>
    <?php endif; ?>
</div>

<div class="top-btn">
    <a href="admin.php?tab=users" class="btn">← 返回會員管理</a>
</div>

</body>
</html>
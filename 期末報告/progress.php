<?php
session_start();
include('db.php');

// 🔐 安全檢查：落實真正的安全防護，沒登入就不能看進度
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=progress.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. 查詢預約參訪
$stmt1 = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY visit_date DESC");
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$appointments = $stmt1->get_result();

// 2. 查詢領養申請
$stmt2 = $conn->prepare("
    SELECT adoptions.*, pets.name AS pet_name, pets.image AS pet_image, pets.status AS pet_status 
    FROM adoptions 
    JOIN pets ON adoptions.pet_id = pets.id 
    WHERE adoptions.user_id = ? 
    ORDER BY adoptions.created_at DESC
");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$adoptions = $stmt2->get_result();

// 💡 3. 新增：查詢該使用者的「走失通報申請進度」
$stmt3 = $conn->prepare("SELECT * FROM lost_pets WHERE user_id = ? ORDER BY created_at DESC");
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$lost_reports = $stmt3->get_result();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>申請進度查詢 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background-color: #fffde7;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      min-height: 100vh;
      margin: 0;
      padding: 80px 20px 40px 20px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .container {
      background-color: rgba(255, 255, 255, 0.9);
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      max-width: 650px; /* 稍微加寬一點點，容納更多欄位 */
      width: 100%;
      z-index: 1;
    }
    table {
      width: 100%;
      margin-top: 10px;
      margin-bottom: 25px;
      border-collapse: collapse;
    }
    th {
      background-color: #fbc02d;
      color: white;
      padding: 10px 6px;
      font-size: 14px;
    }
    td {
      padding: 10px 6px;
      border-bottom: 1px solid #ddd;
      text-align: center;
      font-size: 14px;
      color: #333;
    }
    tr:hover {
      background-color: rgba(251, 192, 45, 0.05);
    }
    h2 {
      margin-top: 25px; /* 調整間距 */
      font-size: 20px;
      color: #f57f17;
      text-align: left;
      border-left: 4px solid #f57f17;
      padding-left: 10px;
      margin-bottom: 15px;
    }
    h2:first-of-type { margin-top: 10px; } /* 第一個標題頂部不需要太寬 */
    p {
      color: #777;
      font-size: 14px;
      margin: 15px 0;
    }
    .top-right-btn, .top-left-btn {
      position: absolute;
      top: 20px;
      padding: 10px 18px;
      background-color: #fbc02d;
      color: white;
      font-size: 14px;
      text-decoration: none;
      font-weight: bold;
      border-radius: 25px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: background-color 0.3s ease, transform 0.2s;
      z-index: 10;
    }
    .top-right-btn { right: 20px; }
    .top-left-btn { left: 20px; }
    .top-right-btn:hover, .top-left-btn:hover {
      background-color: #f9a825;
      transform: scale(1.05);
    }
    
    /* 狀態標籤顏色加強 */
    .status-badge { font-weight: bold; }
    .status-review { color: #f57c00; } /* 審核中/待審核：橘色 */
    .status-approved { color: #388e3c; } /* 協尋中/通過：綠色 */
    .status-reject { color: #d32f2f; } /* 駁回：紅色 */
  </style>
</head>
<body style="background-image: url('image/home page.jpeg');">
  <a href="index.php" class="top-right-btn">回首頁</a>
  <a href="user.php" class="top-left-btn">回使用者頁面</a>

  <div class="container">
    <!-- 1. 預約參訪查詢 -->
    <h2>📅 預約參訪查詢</h2>
    <?php if ($appointments->num_rows > 0): ?>
      <table>
        <tr>
          <th>預約日期</th>
          <th>聯絡電話</th>
          <th>審核狀態</th>
        </tr>
        <?php while ($row = $appointments->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['visit_date']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><strong><?= htmlspecialchars($row['status'] ?? '審核中') ?></strong></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p>尚無預約紀錄</p>
    <?php endif; ?>

    <!-- 2. 領養申請進度 -->
    <h2>🐶 領養申請進度</h2>
    <?php if ($adoptions->num_rows > 0): ?>
      <table>
        <tr>
          <th>申請毛孩</th>
          <th>外觀照片</th>
          <th>申請日期</th>
          <th>目前進度</th>
        </tr>
        <?php while ($row = $adoptions->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['pet_name']) ?></strong></td>
            <td>
              <?php if (!empty($row['pet_image'])): ?>
                <img src="<?= htmlspecialchars($row['pet_image']) ?>" alt="pet" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
              <?php else: ?>
                無圖片
              <?php endif; ?>
            </td>
            <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
            <td><?= htmlspecialchars($row['status'] ?? '審核中') ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p>尚無領養申請紀錄</p>
    <?php endif; ?>

    <!-- 💡 3. 新增：走失通報審核進度區塊 -->
    <h2>🚨 走失通報進度</h2>
    <?php if ($lost_reports->num_rows > 0): ?>
      <table>
        <tr>
          <th>毛孩名字</th>
          <th>通報照片</th>
          <th>走失地區</th>
          <th>審核狀態</th>
        </tr>
        <?php while ($row = $lost_reports->fetch_assoc()): ?>
          <?php 
            // 依據不同狀態設定不同 class 顏色
            $status_class = 'status-review';
            $current_status = $row['status'];
            if ($current_status === '協尋中' || $current_status === '已尋回') {
                $status_class = 'status-approved';
            } elseif ($current_status === '不通過' || $current_status === '已駁回') {
                $status_class = 'status-reject';
            }
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['pet_name']) ?></strong></td>
            <td>
              <?php if (!empty($row['image_path'])): ?>
                <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="lost_pet" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
              <?php else: ?>
                無圖片
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['city']) . htmlspecialchars($row['district']) ?></td>
            <td><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($current_status) ?></span></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p>尚無走失通報紀錄</p>
    <?php endif; ?>

  </div>
</body>
</html>
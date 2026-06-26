<?php  
session_start();
include('db.php');

// 🔐 安全檢查：如果未登入，強制踢回登入頁，並記住原本想來這一頁
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=pet_list.php");
    exit();
}

$type = isset($_GET['type']) && $_GET['type'] !== '' ? "%" . trim($_GET['type']) . "%" : "%";
$min_age = isset($_GET['min_age']) && $_GET['min_age'] !== '' ? (int)$_GET['min_age'] : 0;
$max_age = isset($_GET['max_age']) && $_GET['max_age'] !== '' ? (int)$_GET['max_age'] : 100;

// 🌟 核心修正：加入 FIELD(status, '可領養', '申請中', '已領養') 進行自訂規則排序
$stmt = $conn->prepare("SELECT * FROM pets WHERE type LIKE ? AND age BETWEEN ? AND ? ORDER BY FIELD(status, '可領養', '申請中', '已領養'), id DESC");
$stmt->bind_param("sii", $type, $min_age, $max_age);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>寵物列表 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background: linear-gradient(to bottom right, #fff9c4, #ffe082);
      background-size: cover;
      background-repeat: no-repeat;
      background-position: center;
      min-height: 100vh;
      margin: 0;
      padding: 80px 20px 40px 20px;
    }

    h1 {
      text-align: center;
      font-size: 36px;
      font-weight: bold;
      color: #f57f17;
      text-shadow: 1px 1px 2px rgba(255,255,255,0.7);
      margin-bottom: 30px;
    }

    .search-bar {
      text-align: center;
      margin-bottom: 30px;
    }

    .search-input {
      padding: 10px 14px;
      font-size: 16px;
      margin: 5px;
      border: 1px solid #ccc;
      border-radius: 8px;
      width: 180px;
      background: rgba(255, 255, 255, 0.7);
    }

    .search-btn {
      padding: 10px 20px;
      background-color: #fbc02d;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
    }

    .search-btn:hover {
      background-color: #f9a825;
    }

    .pet-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .pet-card {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      padding: 20px;
      transition: transform 0.2s ease;
      text-align: center;
    }

    .pet-card:hover {
      transform: translateY(-5px);
    }

    .pet-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 12px;
    }

    .pet-name {
      font-size: 20px;
      font-weight: bold;
      color: #b28900;
    }

    .pet-info {
      color: #5d4037;
      margin-top: 6px;
    }

    .action-button {
      margin-top: 15px;
      padding: 10px;
      background-color: #fbc02d;
      color: white;
      border: 1px solid #f9a825;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s;
      width: 100%;
    }

    .action-button:hover {
      background-color: #f9a825;
      transform: scale(1.03);
    }

    .action-button:disabled {
      background-color: #ccc;
      border-color: #bbb;
      cursor: not-allowed;
    }
          
    .top-buttons {
      position: absolute;
      top: 20px;
      left: 20px;
      right: 20px;
      display: flex;
      justify-content: space-between;
      z-index: 10;
    }

    .top-left-btn, .top-right-btn {
      padding: 10px 18px;
      background-color: #fbc02d;
      color: white;
      font-weight: bold;
      border-radius: 25px;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: background-color 0.3s ease, transform 0.2s;
    }

    .top-left-btn:hover, .top-right-btn:hover {
      background-color: #f9a825;
      transform: scale(1.05);
    }
  </style>
</head>
<body>
        
<div class="top-buttons">
  <a href="user.php" class="top-left-btn">回使用者頁面</a>
  <a href="index.php" class="top-right-btn">回首頁</a>
</div>

<h1>🐾 寵物領養清單</h1>

<div class="search-bar">
  <form method="GET" action="">
    <input type="text" name="type" class="search-input" placeholder="品種" value="<?= isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '' ?>">
    <input type="number" name="min_age" class="search-input" placeholder="最小年齡" min="0" max="100" value="<?= isset($_GET['min_age']) ? htmlspecialchars($_GET['min_age']) : '' ?>">
    <input type="number" name="max_age" class="search-input" placeholder="最大年齡" min="0" max="100" value="<?= isset($_GET['max_age']) ? htmlspecialchars($_GET['max_age']) : '' ?>">
    <button type="submit" class="search-btn">搜尋</button>
  </form>
</div>

<div class="pet-container">
<?php while($row = $result->fetch_assoc()): ?>
  <div class="pet-card">
    <?php if (!empty($row['image'])): ?>
      <img src="<?= htmlspecialchars($row['image']) ?>" alt="寵物圖片" class="pet-img">
    <?php else: ?>
      <div style="height:180px; display:flex; justify-content:center; align-items:center; background:#eee; border-radius:10px; margin-bottom:12px; color:#aaa;">尚未上傳圖片</div>
    <?php endif; ?>
    
    <div class="pet-name"><?= htmlspecialchars($row['name']) ?></div>
    <div class="pet-info">品種：<?= htmlspecialchars($row['type']) ?></div>
    <div class="pet-info">年齡：<?= htmlspecialchars($row['age']) ?> 歲</div>
    
    <?php $status = $row['status'] ?? '可領養'; ?>     
    <div class="pet-info">狀態：<?= htmlspecialchars($status) ?></div>
    
    <form action="adopt_rules.php" method="GET">
      <input type="hidden" name="pet_id" value="<?= $row['id'] ?>">
      <button type="submit" class="action-button" <?= ($status !== '可領養') ? 'disabled' : '' ?>>
        <?= ($status === '申請中') ? '申請中' : '領養' ?>
      </button>
    </form>
  </div>
<?php endwhile; ?>
</div>

</body>
</html>
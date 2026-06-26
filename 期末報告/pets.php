<?php
require 'config.php';
require 'db.php';

// 獲取所有寵物資料
$pets = $pdo->query("SELECT * FROM pets")->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>寵物介紹</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background: linear-gradient(135deg, #fce4ec, #ffffff);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background-color: white;
      padding: 40px 50px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      text-align: center;
      max-width: 600px;
      width: 100%;
    }

    h1 {
      color: #d81b60;
      margin-bottom: 30px;
    }

    .pet-card {
      margin: 20px 0;
      padding: 20px;
      background-color: #f8bbd0;
      border-radius: 15px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .pet-img {
      width: 100%;
      max-height: 200px;
      object-fit: cover;
      border-radius: 10px;
    }

    .pet-card h3 {
      color: #e91e63;
      margin-top: 10px;
    }
  </style>
</head>
<body>

  <div class="container">
    <h1>寵物介紹</h1>

    <div class="pets-container">
      <?php foreach ($pets as $pet): ?>
        <div class="pet-card">
          <img src="images/<?= htmlspecialchars($pet['image']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>" class="pet-img">
          <h3><?= htmlspecialchars($pet['name']) ?></h3>
          <p>類型：<?= htmlspecialchars($pet['type']) ?><br>年齡：<?= htmlspecialchars($pet['age']) ?> 歲</p>
          <a href="adopt_form.php?pet_id=<?= $pet['id'] ?>" class="btn">領養申請</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</body>
</html>

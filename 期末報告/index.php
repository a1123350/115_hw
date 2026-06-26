<?php
// 1. 引入你現現有的資料庫連接設定檔
require_once 'db.php';

// 啟動 Session，用來判斷使用者目前是否已經登入
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. 從你的 pets 資料表查詢真實數據
$sql_adopted = "SELECT COUNT(*) as total FROM pets WHERE status = '已領養'";
$res_adopted = $conn->query($sql_adopted);
$adopted_count = 15; // 預設先設 15
if ($res_adopted) {
    $row_adopted = $res_adopted->fetch_assoc();
    $adopted_count += $row_adopted['total']; 
}

$sql_waiting = "SELECT COUNT(*) as total FROM pets WHERE status = '可領養'";
$res_waiting = $conn->query($sql_waiting);
$waiting_count = 0;
if ($res_waiting) {
    $row_waiting = $res_waiting->fetch_assoc();
    $waiting_count = $row_waiting['total'];
}

// ===== 🌟 🆕 改成統計「本月累計參訪人數」 =====
// 使用 SUM(group_size) 把所有已確認預約的人數全部加總
// COALESCE(..., 0) 的作用是：如果這個月剛好一筆預約都沒有，會自動回傳 0 而不是空值 (NULL)
$sql_visit_people = "SELECT COALESCE(SUM(group_size), 0) as total_people FROM appointments WHERE status = '已確認' AND YEAR(visit_date) = YEAR(CURDATE()) AND MONTH(visit_date) = MONTH(CURDATE())";
$res_visit_people = $conn->query($sql_visit_people);
$real_people_count = 0;

if ($res_visit_people) {
    $row_people = $res_visit_people->fetch_assoc();
    $real_people_count = (int)$row_people['total_people'];
}

// 💡 判斷：人數如果破百，同樣自動精簡高亮為 "99+ 人"
$display_visit_text = ($real_people_count > 99) ? "99+ 人" : $real_people_count . " 人";
// ===============================================

$lost_stmt = $conn->query("SELECT * FROM lost_pets WHERE status IN ('協尋中', '已團圓') ORDER BY field(status, '協尋中', '已團圓'), created_at DESC");


// 💡 【新功能資料查詢】為了在首頁秀出部分內容，我們先捞出最新的幾筆資料
// (註：如果你的資料表名稱或欄位不同，請自行微調)

// A. 最新 3 筆幸福留言 (假設狀態為已審核或直接顯示)
$msg_stmt = $conn->query("SELECT * FROM guestbook ORDER BY id DESC LIMIT 3");

// B. 最新 3 篇照護百科
$wiki_stmt = $conn->query("SELECT * FROM pet_wiki ORDER BY id DESC LIMIT 3");

// C. 最新 3 筆急需募集的物資 (status = '募集中' 或 '已核準')
$supply_stmt = $conn->query("SELECT * FROM donation_needs WHERE status = '募集中' ORDER BY id DESC LIMIT 3");


// 3. 處理「自動路由跳轉」的動態 Function
function getServiceUrl($targetUrl) {
    if (isset($_SESSION['username']) || isset($_SESSION['user_id'])) {
        return $targetUrl;
    } else {
        return "login.php?redirect=" . urlencode($targetUrl);
    }
}

// 💡 判斷當前登入者是否為管理員
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>🐾 寵物之家 - 首頁</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Noto Sans TC', sans-serif;
      background-color: #fffde7;
      color: #5d4037;
    }
    /* 頂部導覽列 */
    .navbar {
      display: flex; justify-content: space-between; align-items: center;
      padding: 20px 40px; background: #ffffff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      position: sticky; top: 0; z-index: 100;
    }
    .nav-logo { font-size: 28px; font-weight: bold; color: #b28900; text-decoration: none; }
    .nav-links a { text-decoration: none; color: #6d4c41; font-weight: bold; margin-left: 20px; transition: color 0.3s; }
    .nav-links a:hover { color: #f9a825; }
    .nav-links .btn-nav-login { background-color: #fbc02d; color: white; padding: 8px 20px; border-radius: 20px; }
    .nav-links .btn-nav-login:hover { background-color: #f9a825; }

    /* 主視覺 Banner 區 */
    .hero-section {
      height: 50vh; background-size: cover; background-position: center;
      position: relative; display: flex; justify-content: center; align-items: center; color: white;
      transition: background-image 1s ease-in-out;
    }
    .hero-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4); }
    .hero-content { position: relative; z-index: 2; text-align: center; }
    .hero-content h1 { font-size: 42px; color: #fff59d; margin-bottom: 10px; text-shadow: 2px 2px 8px rgba(0,0,0,0.6); }
    .hero-content p { font-size: 18px; text-shadow: 1px 1px 4px rgba(0,0,0,0.6); }

    /* 即時數據看板 */
    .stats-container {
      display: flex; justify-content: space-around; background: white;
      max-width: 800px; margin: -30px auto 40px auto; padding: 25px;
      border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); position: relative; z-index: 10;
    }
    .stat-item { text-align: center; }
    .stat-item h3 { font-size: 36px; color: #f9a825; margin-bottom: 5px; }
    .stat-item p { font-size: 14px; color: #8d6e00; font-weight: bold; }

    /* 核心功能卡片區（尋找、預約、進度） */
    .section-title { text-align: center; font-size: 30px; color: #a67c00; margin: 50px 0 20px 0; }
    .features-container { display: flex; justify-content: center; gap: 30px; padding: 0 20px 20px 20px; flex-wrap: wrap; }
    .feature-card-link { text-decoration: none; color: inherit; }
    .feature-card {
      background: white; padding: 30px; border-radius: 15px; width: 280px; text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.03); transition: transform 0.3s, box-shadow 0.3s;
    }
    .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .feature-icon { font-size: 40px; margin-bottom: 15px; }
    .feature-card h4 { font-size: 20px; color: #a67c00; margin-bottom: 10px; }
    .feature-card p { font-size: 14px; color: #795548; line-height: 1.4; }

    /* 🆕 三大預覽內容區塊佈局（類似新聞、資訊板塊） */
    .content-layout-section {
      max-width: 1140px; margin: 40px auto; padding: 0 20px;
      display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 30px;
    }
    .content-box {
      background: #ffffff; border-radius: 16px; padding: 25px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.04); display: flex; flex-direction: column;
    }
    .content-box-header {
      display: flex; justify-content: space-between; align-items: center;
      border-bottom: 2px solid #fff8e1; padding-bottom: 12px; margin-bottom: 15px;
    }
    .content-box-header h3 { font-size: 20px; color: #8d6e00; display: flex; align-items: center; gap: 8px; }
    .more-link { font-size: 14px; color: #f9a825; text-decoration: none; font-weight: bold; transition: 0.2s; }
    .more-link:hover { color: #c43e00; text-decoration: underline; }
    
    /* 預覽列表項目樣式 */
    .preview-list { list-style: none; display: flex; flex-direction: column; gap: 12px; flex-grow: 1; }
    .preview-item {
      padding: 10px; border-radius: 8px; background: #fffde7;
      transition: 0.2s; display: flex; flex-direction: column; gap: 4px;
    }
    .preview-item:hover { background: #fff9c4; }
    .preview-title { font-weight: bold; color: #5d4037; font-size: 15px; }
    .preview-desc { font-size: 13px; color: #795548; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .preview-meta { font-size: 11px; color: #a1887f; text-align: right; margin-top: 2px; }

    /* 協尋專區專屬 CSS 樣式 */
    .lost-section { max-width: 1140px; margin: 50px auto 20px auto; padding: 25px; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .lost-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ffebee; padding-bottom: 15px; margin-bottom: 5px; flex-wrap: wrap; gap: 15px; }
    .lost-section h2 { color: #d32f2f; margin: 0; font-size: 26px; }
    .lost-section .subtitle { color: #666; margin: 10px 0 25px 0; text-align: left; font-size: 14px; }
    .report-trigger-btn { background: #d32f2f; color: white; text-decoration: none; padding: 10px 20px; border-radius: 25px; font-weight: bold; font-size: 14px; transition: 0.2s; box-shadow: 0 4px 10px rgba(211, 47, 47, 0.2); }
    .report-trigger-btn:hover { background: #b71c1c; transform: translateY(-2px); }
    .lost-slider { display: flex; gap: 20px; overflow-x: auto; padding: 15px 5px; scroll-behavior: smooth; }
    .lost-slider::-webkit-scrollbar { height: 8px; }
    .lost-slider::-webkit-scrollbar-thumb { background: #ffc107; border-radius: 10px; }
    .lost-card { flex: 0 0 280px; background: #fff; border: 1px solid #eee; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); position: relative; overflow: hidden; transition: 0.3s; display: flex; flex-direction: column; }
    .lost-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .badge { position: absolute; top: 10px; left: 10px; color: #fff; padding: 4px 10px; font-size: 12px; font-weight: bold; border-radius: 10px; z-index: 2; }
    .badge.seeking { background: #e53935; }
    .badge.reunited { background: #43a047; }
    .lost-img { width: 100%; height: 180px; object-fit: cover; }
    .no-img { width: 100%; height: 180px; background: #eee; display: flex; justify-content: center; align-items: center; color: #aaa; font-size: 14px; }
    .lost-info { padding: 15px; text-align: left; flex-grow: 1; display: flex; flex-direction: column; }
    .lost-info h3 { margin: 0 0 10px 0; color: #333; font-size: 18px; }
    .lost-info p { margin: 5px 0; font-size: 14px; color: #555; line-height: 1.4; }
    .call-btn { margin-top: auto; padding: 10px; background: #ffb300; color: #fff; text-align: center; text-decoration: none; font-weight: bold; border-radius: 10px; display: block; transition: 0.2s; font-size: 14px; }
    .call-btn:hover { background: #ffa000; }
    .footer { background-color: #6d4c41; color: #fff59d; text-align: center; padding: 25px 20px; margin-top: 60px; font-size: 14px; }
  </style> 
</head>
<body>

  <nav class="navbar">
    <a href="index.php" class="nav-logo">🐾 寵物之家</a>
    <div class="nav-links">
      <a href="index.php">首頁</a>
      <?php if(isset($_SESSION['username'])): ?>
        <?php if($is_admin): ?>
          <a href="admin.php" style="color: #28a745; font-weight: bold;">🛡️ 後台管理系統</a>
        <?php else: ?>
          <a href="user.php">我的主頁</a>
        <?php endif; ?>
        <a href="logout.php" style="color: #d32f2f;">登出</a>
      <?php else: ?>
        <a href="login.php" class="btn-nav-login">登入 / 註冊</a>
      <?php endif; ?>
    </div>
  </nav>

  <header class="hero-section" id="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1>歡迎來到寵物之家</h1>
      <p>我們致力於為每一隻動物找到溫暖的家！</p>
    </div>
  </header>

  <section class="stats-container">
    <div class="stat-item">
      <h3><?php echo $adopted_count; ?> 隻</h3>
      <p>🐾 累計成功領養</p>
    </div>
    <div class="stat-item">
      <h3><?php echo $waiting_count; ?> 隻</h3>
      <p>🐶 正在等家的毛孩</p>
    </div>
    <div class="stat-item">
      <h3><?php echo $display_visit_text; ?></h3>
      <p>⏰ 本月累計來訪人數</p>
    </div>
  </section>

  <h2 class="section-title">展開你們的故事</h2>
  <section class="features-container">
    <a href="<?php echo getServiceUrl('pet_list.php'); ?>" class="feature-card-link">
      <div class="feature-card">
        <div class="feature-icon">🐶</div>
        <h4>尋找毛孩</h4>
        <p>線上查看開放領養的貓狗詳細資訊。</p>
      </div>
    </a>
    <a href="<?php echo getServiceUrl('visit_form.php'); ?>" class="feature-card-link">
      <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h4>預約參訪</h4>
        <p>一鍵線上預約，親自到現場互動接觸。</p>
      </div>
    </a>
    <a href="<?php echo getServiceUrl('progress.php'); ?>" class="feature-card-link">
      <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <h4>進度追蹤</h4>
        <p>即時查詢您的各項申請與審核進度。</p>
      </div>
    </a>
  </section>

  <section class="content-layout-section">

    <div class="content-box">
      <div class="content-box-header">
        <h3>💝 幸福家庭留言</h3>
        <a href="guestbook.php" class="more-link">看更多 ➔</a>
      </div>
      <ul class="preview-list">
        <?php if ($msg_stmt && $msg_stmt->num_rows > 0): ?>
          <?php while($msg = $msg_stmt->fetch_assoc()): ?>
            <li class="preview-item">
              <span class="preview-title">👤 <?= htmlspecialchars($msg['user_name'] ?? '暖心家長') ?></span>
              <span class="preview-desc"><?= htmlspecialchars($msg['content']) ?></span>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="color:#999; font-size:13px;">暫無留言，期待您的幸福回報！</p>
        <?php endif; ?>
      </ul>
    </div>

    <div class="content-box">
      <div class="content-box-header">
        <h3>📖 寵物照護小百科</h3>
        <a href="wiki.php" class="more-link">看更多 ➔</a>
      </div>
      <ul class="preview-list">
        <?php if ($wiki_stmt && $wiki_stmt->num_rows > 0): ?>
          <?php while($wiki = $wiki_stmt->fetch_assoc()): ?>
            <li class="preview-item" onclick="location.href='wiki_detail.php?id=<?= $wiki['id'] ?>'" style="cursor:pointer;">
              <span class="preview-title">💡 <?= htmlspecialchars($wiki['title']) ?></span>
              <span class="preview-desc"><?= htmlspecialchars($wiki['summary'] ?? $wiki['content']) ?></span>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <li class="preview-item">
            <span class="preview-title">💡 幼貓進家第一週注意事項</span>
            <span class="preview-desc">準備獨立安靜的房間，切勿急著洗澡與強行抱持...</span>
          </li>
          <li class="preview-item">
            <span class="preview-title">💡 汪星人挑食怎麼辦？</span>
            <span class="preview-desc">定時定量是關鍵，固定放飯20分鐘後不吃即收走...</span>
          </li>
        <?php endif; ?>
      </ul>
    </div>

   <div class="content-box">
      <div class="content-box-header">
        <h3>📦 急需愛心物資</h3>
        <a href="<?php echo getServiceUrl('donation_form.php'); ?>" class="more-link">我要捐贈 ➔</a>
      </div>
      <ul class="preview-list">
        <?php if ($supply_stmt && $supply_stmt->num_rows > 0): ?>
          <?php while($supply = $supply_stmt->fetch_assoc()): 
              // 🌟 抓取已募集數量與需求總量
              $raised = intval($supply['quantity_raised'] ?? 0);
              $needed = htmlspecialchars($supply['quantity_needed']); // 例如 "10 袋"
          ?>
            <li class="preview-item">
              <span class="preview-title">🎯 <?= htmlspecialchars($supply['item_name']) ?></span>
              <span class="preview-desc">
                目前進度：<strong style="color: #c62828;"><?= $raised ?></strong> / <?= $needed ?>
              </span>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <li class="preview-item">
            <span class="preview-title">🎯 幼犬離乳高能量罐頭</span>
            <span class="preview-desc">目前進度：<strong style="color: #c62828;">0</strong> / 50 罐</span>
          </li>
          <li class="preview-item">
            <span class="preview-title">🎯 成貓低敏乾糧 (不限品牌)</span>
            <span class="preview-desc">目前進度：<strong style="color: #c62828;">2</strong> / 10 袋</span>
          </li>
        <?php endif; ?>
      </ul>
    </div>

  </section>

  <section class="lost-section">
    <div class="lost-header">
      <h2>🚨 緊急失蹤協尋專區</h2>
      <a href="<?php echo getServiceUrl('report_lost_form.php'); ?>" class="report-trigger-btn">➕ 我要通報走失</a>
    </div>
    <p class="subtitle">幫助毛孩找到回家的路！可使用下方按鈕篩選地區，左右滑動檢視</p>

    <div class="filter-select-container" style="text-align: left; margin-bottom: 25px;">
      <label for="cityFilter" style="font-weight: bold; margin-right: 10px; color: #5d4037;">🔍 切換顯示地區：</label>
      <select id="cityFilter" onchange="filterDistrict(this.value)" style="padding: 8px 15px; border-radius: 20px; border: 1px solid #ccc; font-size: 14px; font-weight: bold; color: #5d4037; background: #fff; cursor: pointer; outline: none;">
        <option value="all">顯示全部地區</option>
        <option value="台北市">台北市</option>
        <option value="新北市">新北市</option>
        <option value="基隆市">基隆市</option>
        <option value="桃園市">桃園市</option>
        <option value="新竹市">新竹市</option>
        <option value="新竹縣">新竹縣</option>
        <option value="宜蘭縣">宜蘭縣</option>
        <option value="苗栗縣">苗栗縣</option>
        <option value="台中市">台中市</option>
        <option value="彰化縣">彰化縣</option>
        <option value="南投縣">南投縣</option>
        <option value="雲林縣">雲林縣</option>
        <option value="嘉義縣">嘉義縣</option>
        <option value="台南市">台南市</option>
        <option value="高雄市">高雄市</option>
        <option value="花蓮縣">花蓮縣</option>
        <option value="台東縣">台東縣</option>
        <option value="屏東縣">屏東縣</option>
      </select>
    </div>

    <div class="lost-slider" id="lostSlider">
      <?php if ($lost_stmt && $lost_stmt->num_rows > 0): ?>
        <?php while($row = $lost_stmt->fetch_assoc()): ?>
          <div class="lost-card" data-city="<?= htmlspecialchars($row['city']) ?>">
            
            <?php if ($row['status'] === '已團圓'): ?>
              <div class="badge reunited">🎉 已團圓</div>
            <?php else: ?>
              <div class="badge seeking">協尋中</div>
            <?php endif; ?>

            <?php if (!empty($row['image_path'])): ?>
              <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="走失毛孩" class="lost-img">
            <?php else: ?>
              <div class="no-img">暫無照片</div>
            <?php endif; ?>
            
            <div class="lost-info">
              <h3>🐾 <?= htmlspecialchars($row['pet_name']) ?> (<?= htmlspecialchars($row['type']) ?>)</h3>
              <p><strong>📍 失蹤地點：</strong><?= htmlspecialchars($row['city']) ?><?= htmlspecialchars($row['district']) ?></p>
              <p><strong>🏠 常出沒處：</strong><?= htmlspecialchars($row['last_seen']) ?></p>
              <p><strong>📝 特徵：</strong><?= htmlspecialchars($row['description']) ?></p>
              
              <?php if ($row['status'] === '已團圓'): ?>
                <div style="margin-top: auto; padding: 10px; background: #e8f5e9; color: #2e7d32; text-align: center; font-weight: bold; border-radius: 10px; font-size: 14px;">
                  ❤️ 謝謝大家！已順利回家！
                </div>
              <?php else: ?>
                <a href="tel:<?= htmlspecialchars($row['contact_phone']) ?>" class="call-btn">📞 聯繫主人：<?= htmlspecialchars($row['contact_phone']) ?></a>
              <?php endif; ?>

              <?php if (isset($_SESSION['user_id']) && ($row['user_id'] == $_SESSION['user_id'] || $is_admin) && $row['status'] === '協尋中'): ?>
                <div style="margin-top: 10px; text-align: center;">
                  <a href="update_lost_status.php?id=<?= $row['id'] ?>&status=已團圓&from=front" 
                     style="background-color: #42a5f5; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; display: inline-block; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                     onclick="return confirm('確定要協助將此案件更新為「已團圓」結案嗎？')">
                     🎉 結案（已找到毛孩）
                  </a>
                </div>
              <?php endif; ?>

            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color:#777; width:100%; text-align:center; padding: 40px 0;">目前沒有正在協尋中的毛孩，大家都安全在團聚中！</p>
      <?php endif; ?>
    </div>
  </section>

  <footer class="footer">
    <p><b>寵物之家官方網站</b></p>
    <p style="margin-top: 5px; font-size: 12px; color: #bcaaa4;">© 2026 寵物之家 管理端與使用者平台</p>
  </footer>

  <script>
    const images = ['image/home page.jpeg', 'image/login page.jpg', 'image/user.webp'];
    let index = 0;
    const hero = document.getElementById('hero');
    function changeHeroBackground() {
      if(hero) {
        hero.style.backgroundImage = `url('${images[index]}')`;
        index = (index + 1) % images.length;
      }
    }
    setInterval(changeHeroBackground, 4000);
    changeHeroBackground();

    function filterDistrict(city) {
      const cards = document.querySelectorAll('.lost-card');
      cards.forEach(card => {
        const cardCity = card.getAttribute('data-city');
        if (city === 'all' || cardCity === city) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
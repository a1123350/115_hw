<?php
session_start();
include('db.php');

// 🔒 權限防火牆：管理員誤入時，強制遣送回後台
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}
// 假設使用者已登入並儲存 user_id，測試用預設為 1
$user_id = $_SESSION['user_id'] ?? 1; 

// 1. 查詢使用者個人資料
$user_stmt = $conn->prepare("SELECT username, email, nickname, avatar, phone FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_res = $user_stmt->get_result()->fetch_assoc();

// 預設值處理
$username = htmlspecialchars($user_res['username'] ?? '未知用戶');
$nickname = htmlspecialchars($user_res['nickname'] ?? $username);
$email = htmlspecialchars($user_res['email'] ?? '未填寫');
$phone = htmlspecialchars($user_res['phone'] ?? '未填寫');
$avatar = !empty($user_res['avatar']) ? htmlspecialchars($user_res['avatar']) : 'image/default-avatar.png';

// 🌟 新增：統計當前使用者未讀通知數量 (is_read = 0)
$unread_stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 🌟 新增：撈取這個人的所有歷史通知 (最新的排在最前面)
$noti_stmt = $conn->prepare("SELECT id, title, content, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC");
$noti_stmt->bind_param("i", $user_id);
$noti_stmt->execute();
$noti_res = $noti_stmt->get_result();

// 2. 數據小物件統計
// 統計預約次數
$count_app_stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE user_id = ?");
$count_app_stmt->bind_param("i", $user_id);
$count_app_stmt->execute();
$total_appointments = $count_app_stmt->get_result()->fetch_assoc()['total'];

// 統計領養申請次數
$count_adopt_stmt = $conn->prepare("SELECT COUNT(*) as total FROM adoptions WHERE user_id = ?");
$count_adopt_stmt->bind_param("i", $user_id);
$count_adopt_stmt->execute();
$total_adoptions = $count_adopt_stmt->get_result()->fetch_assoc()['total'];

// 3. 隨機寵物小知識物件
$tips = [
    "🐶 狗狗搖尾巴不一定代表高興，有時也可能代表緊張或焦慮喔！",
    "🐱 貓咪發出呼嚕聲，除了高興，有時候也是在自我安撫與療癒。",
    "🐾 養寵物可以有效降低人體的壓力荷爾蒙（皮質醇），讓人心情變好！",
    "🐰 兔子的牙齒是一生都會不停生長的，所以需要充足的牧草來磨牙。",
    "🐈 貓咪不愛喝水容易引發腎臟疾病，可以嘗試用流動水裝飾來吸引牠們！"
];
$random_tip = $tips[array_rand($tips)];
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>會員中心 - 寵物之家</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* 🌟 站內通知小鈴鐺新樣式 */
    .noti-bell-container { 
      display: flex; 
      align-items: center; 
      gap: 15px; 
      background: #fff8e1; 
      padding: 15px 20px; 
      border-radius: 15px; 
      border: 1px solid #ffe082; 
      text-align: left;
    }
    .bell-icon-wrapper { 
      position: relative; 
      font-size: 26px; 
      color: #f57f17;
    }
    .bell-badge { 
      position: absolute; 
      top: -5px; 
      right: -5px; 
      background: #e53935; 
      color: white; 
      border-radius: 50%; 
      padding: 2px 6px; 
      font-size: 11px; 
      font-weight: bold; 
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .noti-list-box {
      /* 設定最大高度剛好容納約 3 條通知，超過就會阻斷並允許下滑 */
      max-height: 215px; 
      overflow-y: auto;
      padding-right: 8px;
    }
    
    /* 💡 額外優化：讓滾動條（隨你網頁風格）變得精緻好看 */
    .noti-list-box::-webkit-scrollbar {
      width: 6px;
    }
    .noti-list-box::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    .noti-list-box::-webkit-scrollbar-thumb {
      background: #ffb300;
      border-radius: 10px;
    }
    .noti-list-box::-webkit-scrollbar-thumb:hover {
      background: #b388ff;
    }
    .noti-card { 
      background: white; 
      border-radius: 12px; 
      padding: 15px; 
      margin-top: 12px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.02); 
      border-left: 5px solid #ffb300; 
      text-align: left;
    }
    .noti-card.unread { 
      border-left-color: #e53935; 
      background: #fffde7; 
    }
    .noti-header { 
      display: flex; 
      justify-content: space-between; 
      font-weight: bold; 
      font-size: 14px; 
      color: #5d4037; 
      margin-bottom: 5px; 
    }
    .noti-time { 
      font-size: 11px; 
      color: #aaa; 
      font-weight: normal; 
    }
    .noti-body { 
      font-size: 13.5px; 
      color: #795548; 
      line-height: 1.5; 
    }      
          
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Noto Sans TC', sans-serif;
      background-color: #fffde7;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      transition: background-image 1s ease-in-out;
      min-height: 100vh;
      padding: 80px 20px 40px 20px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .brand-title {
      position: absolute;
      top: 20px;
      width: 100%;
      text-align: center;
      font-size: 36px;
      font-weight: bold;
      color: #fff59d;
      z-index: 10;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
      pointer-events: none;
    }

    /* 頂部導覽按鈕 */
    .top-nav {
      position: absolute;
      top: 20px;
      left: 20px;
      right: 20px;
      display: flex;
      justify-content: space-between;
      z-index: 10;
    }

    .nav-btn {
      padding: 10px 18px;
      background-color: #fbc02d;
      color: white;
      font-size: 14px;
      text-decoration: none;
      font-weight: bold;
      border-radius: 25px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: background-color 0.3s ease, transform 0.2s;
    }

    .nav-btn:hover {
      background-color: #f9a825;
      transform: scale(1.05);
    }

    /* 儀表板主要佈局 (響應式 Grid) */
    .dashboard-container {
      max-width: 900px;
      width: 100%;
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 25px;
      z-index: 1;
    }

    @media (max-width: 768px) {
      .dashboard-container {
        grid-template-columns: 1fr;
      }
    }

    /* 共通卡片樣式 */
    .card {
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      padding: 30px;
      text-align: center;
    }

    /* 左側：個人檔案卡片 */
    .profile-card {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .avatar-wrapper {
      position: relative;
      width: 120px;
      height: 120px;
      margin-bottom: 15px;
    }

    .avatar-img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #fff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .upload-label {
      position: absolute;
      bottom: 0;
      right: 0;
      background-color: #f9a825;
      color: white;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      font-size: 14px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      transition: 0.3s;
    }

    .upload-label:hover {
      background-color: #fbc02d;
      transform: scale(1.1);
    }

    .profile-card h2 {
      color: #a67c00;
      margin-bottom: 5px;
      font-size: 22px;
    }

    .profile-card .username-tag {
      font-size: 14px;
      color: #777;
      margin-bottom: 20px;
    }

    .user-details {
      width: 100%;
      text-align: left;
      margin-bottom: 20px;
      font-size: 15px;
      color: #5d4037;
    }

    .user-details p {
      margin: 10px 0;
      border-bottom: 1px dashed #e0e0e0;
      padding-bottom: 5px;
    }

    .user-details i {
      width: 25px;
      color: #f9a825;
    }

    .edit-profile-btn {
      width: 100%;
      padding: 10px;
      background: none;
      border: 2px solid #f9a825;
      color: #f9a825;
      border-radius: 25px;
      font-size: 15px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
    }

    .edit-profile-btn:hover {
      background-color: #f9a825;
      color: white;
    }

    .logout-btn {
      margin-top: 15px;
      width: 100%;
      padding: 10px;
      background-color: #ff7043;
      border: none;
      color: white;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
    }

    .logout-btn:hover {
      background-color: #f4511e;
    }

    /* 右側：主要功能與延伸模組 */
    .main-panel {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* 數據分析小物件 */
    .stats-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .stat-box {
      background: rgba(251, 192, 45, 0.15);
      border-radius: 15px;
      padding: 15px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .stat-box i {
      font-size: 30px;
      color: #f57f17;
    }

    .stat-info th {
      font-size: 13px;
      color: #757575;
    }

    .stat-info .stat-num {
      font-size: 22px;
      font-weight: bold;
      color: #a67c00;
    }

    /* 核心三大功能按鈕區 */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
    }

    @media (max-width: 500px) {
      .features-grid {
        grid-template-columns: 1fr;
      }
    }

    .feature-item {
      background: #fff;
      padding: 20px 10px;
      border-radius: 15px;
      border: 2px solid #fff59d;
      text-decoration: none;
      color: #5d4037;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      font-weight: bold;
      box-shadow: 0 4px 10px rgba(0,0,0,0.03);
      transition: 0.3s ease;
    }

    .feature-item i {
      font-size: 28px;
      color: #f9a825;
    }

    .feature-item:hover {
      transform: translateY(-5px);
      border-color: #f9a825;
      background-color: #f9a825;
      color: white;
    }

    .feature-item:hover i {
      color: white;
    }

    /* 小知識物件 */
    .tip-box {
      background: linear-gradient(135deg, #fffde7, #fff9c4);
      border-left: 5px solid #fbc02d;
      padding: 15px;
      border-radius: 0 15px 15px 0;
      text-align: left;
      font-size: 14px;
      color: #5d4037;
      line-height: 1.5;
    }

    .tip-box h4 {
      margin-bottom: 5px;
      color: #e65100;
    }

    /* 隱藏的檔案上傳輸入框 */
    #avatar-file {
      display: none;
    }
  </style>
</head>
<body>

  <div class="brand-title">寵物之家</div>

  <div class="top-nav">
    <a href="login.php" class="nav-btn"><i class="fa-solid fa-arrow-left"></i> 回登入頁</a>
    <a href="index.php" class="nav-btn">回首頁 <i class="fa-solid fa-house"></i></a>
  </div>

  <div class="dashboard-container">
    
    <div class="card profile-card">
      <form action="update_avatar.php" method="POST" enctype="multipart/form-data" id="avatar-form">
        <div class="avatar-wrapper">
          <img src="<?= $avatar ?>" alt="使用者頭貼" class="avatar-img" id="avatar-preview">
          <label for="avatar-file" class="upload-label">
            <i class="fa-solid fa-camera"></i>
          </label>
          <input type="file" id="avatar-file" name="avatar" accept="image/*" onchange="submitAvatar()">
        </div>
      </form>

      <h2><?= $nickname ?></h2>
      <div class="username-tag">@<?= $username ?></div>

      <div class="user-details">
        <p><i class="fa-solid fa-envelope"></i> 信箱：<?= $email ?></p>
        <p><i class="fa-solid fa-phone"></i> 電話：<?= $phone ?></p>
        <p><i class="fa-solid fa-id-card"></i> 身份：一般會員</p>
      </div>

      <button class="edit-profile-btn" onclick="location.href='edit_profile.php'"><i class="fa-solid fa-user-gear"></i> 修改個人資料</button>
      
      <form action="logout.php" method="POST" style="width:100%;">
        <button type="submit" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> 登出帳戶</button>
      </form>
    </div>

    <div class="main-panel">
      <div class="main-panel">
      
      <div class="card" style="padding: 20px;">
        <div class="noti-bell-container">
          <div class="bell-icon-wrapper">
            <i class="fa-solid fa-bell"></i>
            <?php if ($unread_count > 0): ?>
              <span class="bell-badge"><?= $unread_count ?></span>
            <?php endif; ?>
          </div>
          <div>
            <h4 style="color: #e65100; font-size: 15px; margin-bottom: 2px;">系統訊息通知中心</h4>
            <span style="font-size: 13.5px; color: #5d4037;">
              <?= $unread_count > 0 ? "您有 <strong>{$unread_count}</strong> 則全新未讀的審核進度訊息唷！" : "目前沒有全新未讀通知。" ?>
            </span>
          </div>
        </div>

        <div class="noti-list-box" style="margin-top: 5px;">
          <?php if ($noti_res && $noti_res->num_rows > 0): ?>
            <?php while($noti = $noti_res->fetch_assoc()): ?>
              <div class="noti-card <?= $noti['is_read'] == 0 ? 'unread' : '' ?>">
                <div class="noti-header">
                  <span><?= htmlspecialchars($noti['title']) ?></span>
                  <span class="noti-time"><i class="fa-regular fa-clock"></i> <?= $noti['created_at'] ?></span>
                </div>
                <div class="noti-body">
                  <?= htmlspecialchars($noti['content']) ?>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p style="text-align: center; color: #aaa; padding: 20px 0; font-size: 13px;">目前沒有收到任何系統通知訊息。</p>
          <?php endif; ?>
        </div>
      </div>
            
      <div class="card" style="padding: 20px;">
        <div class="stats-row">
          <div class="stat-box">
            <i class="fa-solid fa-calendar-check"></i>
            <div class="stat-info">
              <div style="font-size:13px; color:#666;">累計預約</div>
              <div class="stat-num"><?= $total_appointments ?> 次</div>
            </div>
          </div>
          <div class="stat-box">
            <i class="fa-solid fa-heart-circle-check"></i>
            <div class="stat-info">
              <div style="font-size:13px; color:#666;">領養申請</div>
              <div class="stat-num"><?= $total_adoptions ?> 件</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <h3 style="text-align:left; color:#a67c00; margin-bottom:15px; font-size:18px;"><i class="fa-solid fa-star"></i> 核心服務中心</h3>
        <div class="features-grid">
          <a href="visit_form.php" class="feature-item">
            <i class="fa-solid fa-calendar-days"></i>
            <span>預約參訪</span>
          </a>
          <a href="pet_list.php" class="feature-item">
            <i class="fa-solid fa-paw"></i>
            <span>尋找領養</span>
          </a>
          <a href="progress.php" class="feature-item">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
            <span>進度查詢</span>
          </a>
        </div>
      </div>

      <div class="card" style="padding: 20px;">
        <div class="tip-box">
          <h4><i class="fa-solid fa-lightbulb"></i> 每日毛孩冷知識</h4>
          <p><?= $random_tip ?></p>
        </div>
      </div>

    </div>

  </div>

  <script>
    const images = [
      'image/home page.jpeg',
      'image/login page.jpg',
      'image/user.webp'
    ];
    let index = 0;

    function changeBackground() {
      document.body.style.backgroundImage = `url('${images[index]}')`;
      index = (index + 1) % images.length;
    }
    setInterval(changeBackground, 4000); // 延長到4秒切換一次，畫面比較穩定不刺眼
    changeBackground();
    
    // 🌟 自動消除未讀通知：當頁面完全載入 1.5 秒後，發送請求將通知全部設為已讀，並隱藏紅色數字標籤
    window.addEventListener('DOMContentLoaded', (event) => {
        setTimeout(() => {
            fetch('mark_notifications_read.php')
            .then(response => response.text())
            .then(data => {
                const badge = document.querySelector('.bell-badge');
                if (badge) {
                    badge.style.display = 'none'; // 隱藏紅色數字
                }
                // 移除所有通知卡片的未讀高亮背景色
                document.querySelectorAll('.noti-card.unread').forEach(card => {
                    card.classList.remove('unread');
                });
            });
        }, 1500); // 延遲 1.5 秒，讓使用者先看到有新通知的紅點提示，體驗更好
    });

    // 當使用者選取新頭貼時，自動送出表單
    function submitAvatar() {
      const fileInput = document.getElementById('avatar-file');
      if(fileInput.files.length > 0) {
         document.getElementById('avatar-form').submit();
      }
    }
  </script>

</body>
</html>
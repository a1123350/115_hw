<?php
session_start();
date_default_timezone_set('Asia/Taipei');
include('db.php');

// 🔒 頂級權限防火牆：只有管理員可以進來
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("<h2 style='text-align:center; margin-top:50px; color:#d32f2f;'>⚠️ 權限不足！本頁面僅限系統管理員登入使用。</h2>");
}

// --- 🚨 處理走失審核的操作邏輯（已整合小鈴鐺通知） ---
if (isset($_GET['lost_action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['lost_action'];
    
    // 🌟 先查出是哪位會員通報的、以及毛孩的名字，用來寫通知
    $lost_stmt = $conn->prepare("SELECT user_id, pet_name FROM lost_pets WHERE id = ?");
    $lost_stmt->bind_param("i", $id);
    $lost_stmt->execute();
    $lost_info = $lost_stmt->get_result()->fetch_assoc();

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE lost_pets SET status = '協尋中' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // 🌟 補上通知：審核通過
        if ($lost_info) {
            $noti_title = "🚨 協尋公告審核通過！";
            $noti_content = "您好！您通報協尋的毛孩【" . htmlspecialchars($lost_info['pet_name']) . "】已通過管理員審核，目前公告已正式發布至首頁。希望寶貝能集合大眾力量儘快平安歸來！";
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $lost_info['user_id'], $noti_title, $noti_content);
            $noti_stmt->execute();
        }
        
    } elseif ($action === 'reject') {
        // 🌟 駁回前先發通知（因為後面會被 DELETE 掉）
        if ($lost_info) {
            $noti_title = "❌ 協尋通報未通過審核";
            $noti_content = "您好，您先前填寫的毛孩【" . htmlspecialchars($lost_info['pet_name']) . "】協尋通報因內容資訊不足或重複填寫，未能通過審核。若有需要請重新修正後再行通報，謝謝。";
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $lost_info['user_id'], $noti_title, $noti_content);
            $noti_stmt->execute();
        }
        
        $stmt = $conn->prepare("DELETE FROM lost_pets WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: admin.php?tab=lost&msg=success");
    exit();
}

// --- 📦 處理愛心物資管理的操作邏輯 ---
if (isset($_POST['supply_action'])) {
    $action = $_POST['supply_action'];
    
    if ($action === 'add') {
        $item_name = trim($_POST['item_name']);
        $quantity_needed = intval($_POST['quantity_needed']);
        $unit = trim($_POST['unit'] ?? '袋'); 
        
        if (!empty($item_name) && $quantity_needed > 0) {
            $stmt = $conn->prepare("INSERT INTO donation_needs (item_name, quantity_needed, status) VALUES (?, ?, '募集中')");
            $full_needed = $quantity_needed . " " . $unit;
            $stmt->bind_param("ss", $item_name, $full_needed);
            $stmt->execute();
        }
    } elseif ($action === 'update_progress' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $raised = intval($_POST['quantity_raised']);
        
        $stmt = $conn->prepare("UPDATE donation_needs SET quantity_raised = ? WHERE id = ?");
        $stmt->bind_param("ii", $raised, $id);
        $stmt->execute();
        
    } elseif ($action === 'toggle_status' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $current_status = $_POST['current_status'];
        $new_status = ($current_status === '募集中') ? '已額滿' : '募集中';
        $stmt = $conn->prepare("UPDATE donation_needs SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM donation_needs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: admin.php?tab=supplies&msg=success");
    exit();
}

// 🌟 --- 處理民眾「捐贈意願回報」的審核邏輯（優化金流防呆） ---
if (isset($_GET['user_donation_action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['user_donation_action'];
    
    $don_stmt = $conn->prepare("SELECT ud.user_name, ud.item_name, ud.quantity, ud.shipping_method, u.id AS u_id FROM user_donations ud JOIN users u ON ud.user_name = u.username WHERE ud.id = ?");
    $don_stmt->bind_param("i", $id);
    $don_stmt->execute();
    $don_info = $don_stmt->get_result()->fetch_assoc();

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE user_donations SET status = '已核準' WHERE id = ?");
        
        if ($don_info) {
            if ($don_info['shipping_method'] === '銀行轉帳 / 匯款') {
                $noti_title = "💳 感謝您的愛心捐款入帳！";
                $noti_content = "親愛的會員您好，園區已確認收到您回報的愛心轉帳經費共【新台幣 " . number_format($don_info['quantity']) . " 元】，管理員已對帳審核通過！這筆經費將全數用於毛孩們的日常伙食與醫療開銷，由衷感謝您的慷慨解囊！";
            } else {
                $noti_title = "💝 感謝您的愛心物資捐贈！";
                $noti_content = "親愛的會員您好，園區已成功核對並收到您回報捐贈的物資【" . htmlspecialchars($don_info['item_name']) . " " . htmlspecialchars($don_info['quantity']) . "】，管理員已審核通過。非常感謝您的熱心善舉，為等家的毛孩們補滿滿滿的能量！";
            }
            
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $don_info['u_id'], $noti_title, $noti_content);
            $noti_stmt->execute();
        }
        
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE user_donations SET status = '已拒絕' WHERE id = ?");
        
        if ($don_info) {
            if ($don_info['shipping_method'] === '銀行轉帳 / 匯款') {
                // 🌟 改為溫和的「對帳失敗」通知台詞
                $noti_title = "⚠️ 愛心捐款對帳未成功";
                $noti_content = "您好，您先前填寫金額 " . htmlspecialchars($don_info['quantity']) . " 元的匯款回報，因管理員核對園區帳戶後，查無對應之帳號後五碼流水帳紀錄。請您確認後五碼是否填寫正確，或歡迎聯絡園區協助核對，再次感謝您的愛心。";
            } else {
                $noti_title = "⚠️ 物資捐贈回報審核未通過";
                $noti_content = "您好，您先前填寫的【" . htmlspecialchars($don_info['item_name']) . "】物資捐贈回報，因資訊核對不符（或物流單據照片無法辨識），未能通過審核。若有任何疑問，歡迎聯絡園區核對。";
            }
            $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, content, is_read) VALUES (?, ?, ?, 0)");
            $noti_stmt->bind_param("iss", $don_info['u_id'], $noti_title, $noti_content);
            $noti_stmt->execute();
        }
    } elseif ($action === 'reset') {
        // 🌟 🆕 新增：重新核對功能，將狀態洗回待審核
        $stmt = $conn->prepare("UPDATE user_donations SET status = '待審核' WHERE id = ?");
    }
    
    if (isset($stmt)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin.php?tab=user_donations&msg=success");
    exit();
}

// --- 📖 處理寵物照護百科的文章管理邏輯 ---
if (isset($_POST['wiki_action'])) {
    $action = $_POST['wiki_action'];
    
    if ($action === 'add') {
        $title = trim($_POST['title']);
        $summary = trim($_POST['summary']);
        $content = trim($_POST['content']);
        
        if (!empty($title) && !empty($content)) {
            $stmt = $conn->prepare("INSERT INTO pet_wiki (title, summary, content) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $summary, $content);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM pet_wiki WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin.php?tab=wiki_manage&msg=success");
    exit();
}

// 🗄️ 統一撈取分頁所需的資料
$wiki_admin_res = $conn->query("SELECT * FROM pet_wiki ORDER BY id DESC");
$users_res = $conn->query("SELECT * FROM users");

// ===== 📅 預約資料撈取與隔日統計 =====
$tomorrow_date = date('Y-m-d', strtotime('+1 day'));
$tomorrow_stmt = $conn->prepare("SELECT a.*, u.username FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.visit_date = ? AND a.status != '已取消' AND a.status != '取消' ORDER BY a.created_at ASC");
$tomorrow_stmt->bind_param("s", $tomorrow_date);
$tomorrow_stmt->execute();
$tomorrow_res = $tomorrow_stmt->get_result();

// 🌟 🆕 優化排序：1. 未來/今天的排上面，過去的沉到底部 2. 狀態【待確認】優先 3. 日期由近到遠
$visit_res = $conn->query("SELECT a.*, u.username FROM appointments a JOIN users u ON a.user_id = u.id ORDER BY (a.visit_date < CURDATE()) ASC, FIELD(a.status, '待確認') DESC, a.visit_date ASC, a.created_at DESC");

$adopt_res = $conn->query("SELECT ar.id, ar.created_at, u.username AS user_name, p.name AS pet_name, ar.status FROM adoptions ar JOIN users u ON ar.user_id = u.id JOIN pets p ON ar.pet_id = p.id ORDER BY FIELD(ar.status, '審核中') DESC, ar.created_at DESC");
$pets_res = $conn->query("SELECT * FROM pets");
$lost_res = $conn->query("SELECT * FROM lost_pets WHERE status IN ('審核中', '協尋中', '已團圓') ORDER BY FIELD(status, '審核中', '協尋中', '已團圓'), created_at DESC");
$supply_res = $conn->query("SELECT * FROM donation_needs ORDER BY status DESC, created_at DESC");

$user_donations_res = $conn->query("SELECT * FROM user_donations ORDER BY id DESC");
$pending_count_res = $conn->query("SELECT COUNT(*) as total FROM user_donations WHERE status = '待審核'");
$pending_count = $pending_count_res->fetch_assoc()['total'] ?? 0;

// 安全權限檢查陣列（修正重複覆蓋 Bug）
$active_tab = $_GET['tab'] ?? 'dashboard';
$allowed_tabs = ['dashboard', 'users', 'visit', 'adoptions', 'pets', 'lost', 'supplies', 'user_donations', 'wiki_manage'];
if (!in_array($active_tab, $allowed_tabs)) {
    $active_tab = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>🐾 寵物之家 - 系統進階後台管理</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Noto Sans TC', sans-serif; }
    body { background-color: #fffde7; min-height: 100vh; padding: 30px 20px; background-attachment: fixed; }
    .header-area { max-width: 1200px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center; }
    .brand-title { font-size: 28px; font-weight: bold; color: #5d4037; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
    .nav-buttons a { text-decoration: none; padding: 8px 16px; background-color: #fbc02d; color: white; font-weight: bold; border-radius: 20px; margin-left: 10px; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.2s; }
    .nav-buttons a:hover { background-color: #f9a825; transform: translateY(-2px); }
    .admin-wrapper { max-width: 1200px; margin: 0 auto; background: rgba(255, 255, 255, 0.93); border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; display: flex; flex-direction: column; }
    .admin-navbar { display: flex; background: #fbc02d; padding: 5px 10px 0 10px; border-bottom: 4px solid #f9a825; gap: 5px; overflow-x: auto; }
    .nav-tab { padding: 12px 20px; color: #5d4037; font-weight: bold; font-size: 15px; border-radius: 12px 12px 0 0; cursor: pointer; transition: all 0.3s ease; border: none; background: transparent; outline: none; white-space: nowrap; }
    .nav-tab:hover { background: rgba(255, 255, 255, 0.3); }
    .nav-tab.active { background: rgba(255, 255, 255, 0.93); color: #e65100; border-bottom: 4px solid rgba(255, 255, 255, 0.93); margin-bottom: -4px; position: relative; z-index: 2; }
    .admin-content { padding: 35px; min-height: 500px; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
    .dash-card { background: white; padding: 25px 20px; border-radius: 15px; border-left: 5px solid #fbc02d; box-shadow: 0 4px 10px rgba(0,0,0,0.03); text-align: center; }
    .dash-card h3 { color: #757575; font-size: 16px; margin-bottom: 10px; }
    .dash-card .num { font-size: 32px; font-weight: bold; color: #5d4037; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    th { background-color: #fff59d; color: #5d4037; padding: 14px; font-weight: bold; font-size: 15px; }
    td { padding: 14px; border-bottom: 1px solid #eee; text-align: center; font-size: 15px; color: #424242; vertical-align: middle; }
    tr:hover { background-color: #fefdf0; }
    
    .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 13px; font-weight: bold; }
    .status-badge.pending { background: #fff3e0; color: #ef6c00; }
    .status-badge.approved { background: #e8f5e9; color: #2e7d32; }
    .status-badge.rejected { background: #ffebee; color: #c62828; }
    img.pet-thumb { width: 65px; height: 65px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    
    .btn-action { display: inline-block; padding: 6px 14px; margin: 2px; font-weight: bold; font-size: 13px; color: white; background-color: #fbc02d; border: none; border-radius: 20px; text-decoration: none; cursor: pointer; transition: background 0.2s; }
    .btn-action:hover { background-color: #f9a825; transform: translateY(-1px); }
    .btn-danger { background-color: #e53935; }
    .btn-danger:hover { background-color: #c62828; }
    .btn-success { background-color: #43a047; }
    .btn-success:hover { background-color: #2e7d32; }
    .user-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: white; border-radius: 10px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .user-info .name { font-weight: bold; color: #5d4037; font-size: 16px; }
    .user-info .role { font-size: 13px; color: #757575; background: #eee; padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 4px; }
    .no-data { text-align: center; color: #9e9e9e; padding: 40px; font-size: 15px; }
    h2.panel-title { color: #5d4037; margin-bottom: 20px; font-size: 22px; border-left: 5px solid #fbc02d; padding-left: 10px; }
    .inline-form { background: #fffde7; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; border: 1px solid #ffe082; }
    .inline-form input { padding: 8px 15px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; outline: none; }

    .tomorrow-summary-box { background: #fff8e1; border: 2px dashed #ffe082; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
    .tomorrow-summary-title { font-size: 16px; font-weight: bold; color: #e65100; margin-bottom: 10px; display: flex; align-items: center; gap: 5px; }
    .tomorrow-badge { background: #ffb300; color: white; padding: 2px 8px; border-radius: 6px; font-size: 13px; }
    .badge-purpose { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: bold; color: white; }
    .bg-visit { background-color: #42a5f5; }
    .bg-dog { background-color: #ab47bc; }
    .bg-shower { background-color: #26a69a; }

    .filter-btn-group { display: flex; gap: 10px; margin-bottom: 15px; }
    .filter-btn { background: #e0e0e0; color: #333; border: none; padding: 8px 16px; border-radius: 15px; font-weight: bold; font-size: 13px; cursor: pointer; transition: 0.2s; }
    .filter-btn.active { background: #e65100; color: white; box-shadow: 0 2px 6px rgba(230,81,0,0.2); }
  </style>
</head>
<body style="background-image: linear-gradient(rgba(255, 255, 255, 0.20), rgba(255, 255, 255, 0.20)), url('image/home page.jpeg'); background-size: cover; background-position: center; background-attachment: fixed;">       
       
  <div class="header-area">
    <div class="brand-title" style="color: #FFF592; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.4), 0 0 5px rgba(0, 0, 0, 0.2);">寵物之家系統後台</div>
    <div class="nav-buttons">
      <a href="index.php">回首頁</a>
      <a href="logout.php">登出系統</a>
    </div>
  </div>

  <div class="admin-wrapper">
    <div class="admin-navbar">
      <button id="tab-dashboard" class="nav-tab <?= $active_tab=='dashboard'?'active':'' ?>" onclick="switchTab('dashboard')">📊 數據中心</button>
      <button id="tab-users" class="nav-tab <?= $active_tab=='users'?'active':'' ?>" onclick="switchTab('users')">👥 使用者管理</button>
      <button id="tab-visit" class="nav-tab <?= $active_tab=='visit'?'active':'' ?>" onclick="switchTab('visit')">📅 預約與志工管理</button>
      <button id="tab-adoptions" class="nav-tab <?= $active_tab=='adoptions'?'active':'' ?>" onclick="switchTab('adoptions')">📜 領養表單管理</button>
      <button id="tab-pets" class="nav-tab <?= $active_tab=='pets'?'active':'' ?>" onclick="switchTab('pets')">🐱 寵物上架管理</button>
      <button id="tab-lost" class="nav-tab <?= $active_tab=='lost'?'active':'' ?>" onclick="switchTab('lost')">🚨 走失協尋審核 (<?= $lost_res->num_rows ?>)</button>
      <button id="tab-supplies" class="nav-tab <?= $active_tab=='supplies'?'active':'' ?>" onclick="switchTab('supplies')">📦 園區公告募集 (<?= $supply_res->num_rows ?>)</button>
      <button id="tab-user_donations" class="nav-tab <?= $active_tab=='user_donations'?'active':'' ?>" onclick="switchTab('user_donations')">💝 民眾捐贈審核 (<?= $pending_count ?>)</button>
      <button id="tab-wiki_manage" class="nav-tab <?= $active_tab=='wiki_manage'?'active':'' ?>" onclick="switchTab('wiki_manage')">📖 百科文章管理 (<?= $wiki_admin_res->num_rows ?>)</button>
    </div>

    <div class="admin-content">

      <div id="panel-dashboard" class="tab-panel <?= $active_tab=='dashboard'?'active':'' ?>">
        <h2 class="panel-title">系統數據概覽</h2>
        <p style="color:#666;">歡迎回來！以下是目前網站營運的核心實時數據：</p>
        <div class="dashboard-grid">
          <div class="dash-card"><h3>總註冊會員</h3><div class="num"><?= $users_res->num_rows ?> 人</div></div>
          <div class="dash-card"><h3>活動/參訪總案數</h3><div class="num"><?= $visit_res->num_rows ?> 件</div></div>
          <div class="dash-card"><h3>領養申請案</h3><div class="num"><?= $adopt_res->num_rows ?> 件</div></div>
          <div class="dash-card"><h3>架上收容寵物</h3><div class="num"><?= $pets_res->num_rows ?> 隻</div></div>
          <div class="dash-card" style="border-left-color: #e53935;"><h3>協尋專區總案數</h3><div class="num" style="color:#e53935;"><?= $lost_res->num_rows ?> 件</div></div>
          <div class="dash-card" style="border-left-color: #0288d1;"><h3>募集物資品項</h3><div class="num" style="color:#0288d1;"><?= $supply_res->num_rows ?> 項</div></div>
          <div class="dash-card" style="border-left-color: #43a047;"><h3>民眾捐贈回報</h3><div class="num" style="color:#43a047;"><?= $user_donations_res->num_rows ?> 筆</div></div>
        </div>
      </div>

      <div id="panel-users" class="tab-panel <?= $active_tab=='users'?'active':'' ?>">
        <h2 class="panel-title">會員權限管理</h2>
        <div>
          <?php while($user = $users_res->fetch_assoc()): ?>
            <div class="user-row">
              <div class="user-info">
                <span class="name"><?= htmlspecialchars($user['username']) ?></span><br>
                <span class="role">身分：<?= htmlspecialchars($user['role']) ?></span>
              </div>
              <div>
                <form method="get" action="view_records.php" style="display:inline;">
                  <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                  <button class="btn-action" type="submit">🔍 查看紀錄</button>
                </form>
                <form method="post" action="delete_user.php" style="display:inline;" onsubmit="return confirm('確定要刪除這個使用者嗎？');">
                  <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                  <button class="btn-action btn-danger" type="submit">🗑️ 刪除</button>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <div id="panel-visit" class="tab-panel <?= $active_tab=='visit'?'active':'' ?>">
        <h2 class="panel-title">預約參訪與志工活動審核中心</h2>
        
        <div class="tomorrow-summary-box">
          <div class="tomorrow-summary-title">
            <span>📅 明日來訪概覽 (<?= $tomorrow_date ?>)</span>
            <span class="tomorrow-badge">共 <?= $tomorrow_res->num_rows ?> 組預約</span>
          </div>
          <?php if ($tomorrow_res->num_rows > 0): ?>
            <p style="font-size: 14px; color: #5d4037; line-height: 1.6;">
              <?php 
              $idx = 1;
              while($t_app = $tomorrow_res->fetch_assoc()) {
                  $t_purpose = $t_app['purpose'] ?? '單純參訪';
                  echo "<b>({$idx})</b> 會員 <b>" . htmlspecialchars($t_app['username']) . "</b> 等 " . htmlspecialchars($t_app['group_size'] ?? 1) . " 人 — 【" . htmlspecialchars($t_purpose) . "】 (電話: " . htmlspecialchars($t_app['phone']) . ")<br>";
                  $idx++;
              }
              ?>
            </p>
          <?php else: ?>
            <p style="font-size: 14px; color: #757575;">明天目前沒有安排任何參訪或志工活動喔！</p>
          <?php endif; ?>
        </div>

        <p style="color:#666; font-size:14px; margin-bottom: 10px;">📌 系統排序提示：<b>【待確認】的案件會優先排在最前面</b>，並自動依據預約日期由近到遠（即將到來的日子由上往下）排序。</p>
        <table>
          <thead>
            <tr>
              <th>申請會員</th>
              <th>報名項目</th>
              <th>預約日期</th>
              <th>人數</th>
              <th>聯絡電話</th>
              <th>目前狀態</th>
              <th>審核操作</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($visit_res->num_rows > 0): ?>
              <?php while($app = $visit_res->fetch_assoc()): 
                  $purpose = $app['purpose'] ?? '單純參訪(領養諮詢)';
                  $badge_class = 'bg-visit';
                  if (strpos($purpose, '遛狗') !== false) { $badge_class = 'bg-dog'; }
                  elseif (strpos($purpose, '洗澡') !== false || strpos($purpose, '志工') !== false) { $badge_class = 'bg-shower'; }
                  $group_size = $app['group_size'] ?? 1;

                  // 🌟 🆕 核心邏輯：判斷這筆預約的日期是否已經「小於今天」
                  $is_past = (strtotime($app['visit_date']) < strtotime(date('Y-m-d')));
              ?>
                <!-- 🌟 🆕 如果是過去的日期，整行 <tr> 加上反灰與淡化樣式 -->
                <tr style="<?= $is_past ? 'background-color: #f5f5f5; color: #9e9e9e; opacity: 0.7;' : '' ?>">
                  <td><strong><?= htmlspecialchars($app['username']) ?></strong></td>
                  <td>
                    <!-- 🌟 🆕 如果過期，把原本亮眼的標籤背景改淡（灰底白字），視覺上更和諧 -->
                    <span class="badge-purpose <?= $badge_class ?>" style="<?= $is_past ? 'background-color: #bdbdbd; color: white;' : '' ?>">
                      <?= htmlspecialchars($purpose) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($app['visit_date']) ?></td>
                  <td><strong><?= htmlspecialchars($group_size) ?></strong> 人</td>
                  <td><?= htmlspecialchars($app['phone']) ?></td>
                  
                  <!-- 🌟 🆕 狀態欄位判定 -->
                  <td>
                    <?php if ($is_past): ?>
                      <!-- 🌟 🆕 過去的日期，不論原本狀態是什麼，直接顯示【已結束】 -->
                      <span style="font-weight:bold; color: #757575;">⏳ 已結束</span>
                    <?php else: ?>
                      <!-- 未來的日期，維持你原本漂亮的顏色判斷 -->
                      <span style="font-weight:bold; color:<?= $app['status']=='待確認'?'#e65100':($app['status']=='已取消' || $app['status']=='取消'?'#888':'#43a047') ?>">
                        <?= htmlspecialchars($app['status']) ?>
                      </span>
                    <?php endif; ?>
                  </td>

                  <!-- 🌟 🆕 操作欄位判定 -->
                  <td>
                    <?php if ($is_past): ?>
                      <!-- 🌟 🆕 過去的日期，按鈕直接隱藏，寫上已結案提示（避免管理員誤點） -->
                      <span style="color: #9e9e9e; font-size:13px;"><i class="fa-solid fa-lock"></i> 歷史紀錄已結案</span>
                    <?php else: ?>
                      <!-- 未來的日期，維持原本的「確認 / 取消」按鈕 -->
                      <?php if ($app['status'] === '待確認'): ?>
                        <a href="confirm_appointment.php?id=<?= $app['id'] ?>" class="btn-action btn-success">✅ 確認</a>
                        <a href="admin_cancel_appointment.php?id=<?= $app['id'] ?>" class="btn-action btn-danger">❌ 取消</a>
                      <?php else: ?>
                        <span style="color: gray; font-size:13px;">✔ 已完成處理</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="no-data">目前沒有任何預約活動紀錄。</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div id="panel-adoptions" class="tab-panel <?= $active_tab=='adoptions'?'active':'' ?>">
        <h2 class="panel-title">毛孩領養審核中心</h2>
        <table>
          <thead>
            <tr>
              <th>申請日期</th>
              <th>申請者</th>
              <th>想領養的毛孩</th>
              <th>目前狀態</th>
              <th>審核操作</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($adopt = $adopt_res->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($adopt['created_at']) ?></td>
                <td><?= htmlspecialchars($adopt['user_name']) ?></td>
                <td><strong style="color:#e65100;"><?= htmlspecialchars($adopt['pet_name']) ?></strong></td>
                <td><strong><?= htmlspecialchars($adopt['status']) ?></strong></td>
                <td>
                  <a href="view_adopt_application.php?id=<?= $adopt['id'] ?>" class="btn-action">📄 詳細審查</a>
                  <a href="update_status.php?id=<?= $adopt['id'] ?>&status=核准" class="btn-action btn-success" onclick="return confirm('確定核准此領養申請嗎？')">批准</a>
                  <a href="update_status.php?id=<?= $adopt['id'] ?>&status=駁回" class="btn-action btn-danger" onclick="return confirm('確定拒絕此領養申請嗎？')">拒絕</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div id="panel-pets" class="tab-panel <?= $active_tab=='pets'?'active':'' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
          <h2 class="panel-title" style="margin:0;">園區內寵物名冊</h2>
          <a href="add_pet.php" class="btn-action btn-success" style="padding: 8px 16px; border-radius: 10px;">➕ 新增收容毛孩</a>
        </div>
        <table>
          <thead>
            <tr>
              <th>照片</th>
              <th>名字</th>
              <th>品種/類型</th>
              <th>年齡</th>
              <th>目前狀態</th>
              <th>維護操作</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($pet = $pets_res->fetch_assoc()): ?>
              <tr>
                <td>
                  <?php if (!empty($pet['image'])): ?>
                    <img src="<?= htmlspecialchars($pet['image']) ?>" class="pet-thumb" alt="pet">
                  <?php else: ?>
                    <span style="color:#aaa; font-size:13px;">無照片</span>
                  <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($pet['name']) ?></strong></td>
                <td><?= htmlspecialchars($pet['type']) ?></td>
                <td><?= htmlspecialchars($pet['age']) ?> 歲</td>
                <td><?= htmlspecialchars($pet['status']) ?></td>
                <td>
                  <a href="edit_pet.php?id=<?= (int)$pet['id'] ?>" class="btn-action">✏️ 編輯</a>
                  <a href="delete_pet.php?id=<?= (int)$pet['id'] ?>" class="btn-action btn-danger" onclick="return confirm('確定要從資料庫完全移除這隻毛孩的資料嗎？')">🗑️ 刪除</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div id="panel-lost" class="tab-panel <?= $active_tab=='lost'?'active':'' ?>">
        <h2 class="panel-title">🚨 走失協尋 - 民間通報審核中心</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">請過濾民眾上傳的內容。批准後該案件會立即出現在網站首頁供全民協尋。</p>
        <?php if ($lost_res->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>通報照片</th>
                <th>毛孩名字</th>
                <th>遺失地點</th>
                <th>最常出沒點</th>
                <th style="width: 25%;">外觀特徵</th>
                <th>通報聯絡人</th>
                <th>審核決策</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($lost = $lost_res->fetch_assoc()): ?>
                <tr>
                  <td>
                    <?php if(!empty($lost['image_path'])): ?>
                      <img src="<?= htmlspecialchars($lost['image_path']) ?>" class="pet-thumb" alt="lost">
                    <?php else: ?>
                      <span style="color:#aaa;">無照片</span>
                    <?php endif; ?>
                  </td>
                  <td><strong><?= htmlspecialchars($lost['pet_name'] ?? '') ?></strong> (<?= htmlspecialchars($lost['type'] ?? '') ?>)</td>
                  <td><span style="background:#ffe082; padding:3px 8px; border-radius:5px; font-size:13px; font-weight:bold;"><?= htmlspecialchars($lost['city'] ?? '') ?><?= htmlspecialchars($lost['district'] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($lost['last_seen'] ?? '') ?></td>
                  <td style="text-align: left; line-height: 1.4; font-size:13px;"><?= htmlspecialchars($lost['description'] ?? '') ?></td>
                  <td><?= htmlspecialchars($lost['contact_phone'] ?? '') ?></td>
                  <td>
                    <?php if ($lost['status'] === '審核中'): ?>
                      <a href="admin.php?lost_action=approve&id=<?= $lost['id'] ?>" class="btn-action btn-success" onclick="return confirm('確定通過此件並發布至首頁嗎？')">✔ 通過</a>
                      <a href="admin.php?lost_action=reject&id=<?= $lost['id'] ?>" class="btn-action btn-danger" onclick="return confirm('確定駁回並刪除這筆通報嗎？')">❌ 駁回</a>
                      <span style="display:block; font-size:12px; color:#e65100; margin-top:5px;">(待審核)</span>
                    <?php elseif ($lost['status'] === '協尋中'): ?>
                      <a href="update_lost_status.php?id=<?= $lost['id'] ?>&status=<?= urlencode('已團圓') ?>" class="btn-action" style="background-color: #42a5f5;" onclick="return confirm('太棒了！確定這隻毛孩已經順利找回了嗎？')">🎉 已找到！</a>
                      <a href="update_lost_status.php?id=<?= $lost['id'] ?>&status=<?= urlencode('撤下') ?>" class="btn-action btn-danger" onclick="return confirm('確定要將此通報案件從系統完全撤下刪除嗎？')">🗑️ 徹底撤下</a>
                      <span style="display:block; font-size:12px; color:#43a047; margin-top:5px;">(前台協尋中)</span>
                    <?php elseif ($lost['status'] === '已團圓'): ?>
                      <span style="display:inline-block; padding: 4px 8px; background: #e8f5e9; color: #2e7d32; border-radius: 4px; font-size: 13px; font-weight: bold; margin-right: 5px;">🎉 主人回報已找到</span>
                      <a href="update_lost_status.php?id=<?= $lost['id'] ?>&status=<?= urlencode('撤下') ?>" class="btn-action btn-danger" onclick="return confirm('確定要將這件「已團圓」的公告徹底從系統撤下嗎？')">🗑️ 徹底撤下</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="no-data">🎉 完美！目前沒有任何走失協尋案件。</div>
        <?php endif; ?>
      </div>

      <div id="panel-supplies" class="tab-panel <?= $active_tab=='supplies'?'active':'' ?>">
        <h2 class="panel-title">📦 園區物資募集管理中心</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">在此發佈或調整前台「急需愛心物資」區塊的募集公告清單，並可隨時清點更動最新募集進度。</p>
        
        <form method="post" action="admin.php?tab=supplies" class="inline-form">
          <input type="hidden" name="supply_action" value="add">
          <label style="font-weight:bold; color:#5d4037;">新增募集項目：</label>
          <input type="text" name="item_name" placeholder="例如：成貓低敏飼料" required style="width:200px;">
          <input type="number" name="quantity_needed" placeholder="需求數量 (例如: 10)" min="1" required style="width:140px;">
          <input type="text" name="unit" placeholder="單位 (例如: 袋/罐/箱)" required style="width:100px;">
          <button type="submit" class="btn-action btn-success" style="border-radius:6px; padding: 8px 16px;">➕ 發佈募集</button>
        </form>

        <?php if ($supply_res && $supply_res->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>發佈時間</th>
                <th>物資品項名稱</th>
                <th>🎯 目前進度量 (可即時修改)</th>
                <th>需求總量</th>
                <th>目前狀態</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($supply = $supply_res->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($supply['created_at']) ?></td>
                  <td><strong><?= htmlspecialchars($supply['item_name']) ?></strong></td>
                  <td>
                    <form method="post" action="admin.php?tab=supplies" style="display:flex; justify-content:center; align-items:center; gap:5px;">
                      <input type="hidden" name="supply_action" value="update_progress">
                      <input type="hidden" name="id" value="<?= $supply['id'] ?>">
                      <span>已收到</span>
                      <input type="number" name="quantity_raised" value="<?= intval($supply['quantity_raised'] ?? 0) ?>" min="0" style="width: 70px; padding: 4px; text-align: center; border-radius: 4px; border: 1px solid #ccc;">
                      <button type="submit" class="btn-action btn-success" style="padding: 4px 10px; font-size: 12px; border-radius: 4px;">💾 儲存</button>
                    </form>
                  </td>
                  <td><span style="color:#e65100; font-weight:bold;"><?= htmlspecialchars($supply['quantity_needed']) ?></span></td>
                  <td>
                    <span style="font-weight:bold; color:<?= $supply['status']=='募集中'?'#e65100':'#43a047' ?>">
                      <?= $supply['status']=='募集中'?'⏳ 募集中':'✅ 已額滿' ?>
                    </span>
                  </td>
                  <td>
                    <form method="post" action="admin.php?tab=supplies" style="display:inline;">
                      <input type="hidden" name="supply_action" value="toggle_status">
                      <input type="hidden" name="id" value="<?= $supply['id'] ?>">
                      <input type="hidden" name="current_status" value="<?= $supply['status'] ?>">
                      <button type="submit" class="btn-action" style="background-color: #42a5f5;">
                        🔄 標記為<?= $supply['status']=='募集中'?'【已額滿】':'【募集中】' ?>
                      </button>
                    </form>
                    <form method="post" action="admin.php?tab=supplies" style="display:inline;" onsubmit="return confirm('確定要完全刪除此募集品項嗎？');">
                      <input type="hidden" name="supply_action" value="delete">
                      <input type="hidden" name="id" value="<?= $supply['id'] ?>">
                      <button type="submit" class="btn-action btn-danger">🗑️ 刪除</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="no-data">目前沒有發佈任何物資募集項目。</div>
        <?php endif; ?>
      </div>

      <div id="panel-user_donations" class="tab-panel <?= $active_tab=='user_donations'?'active':'' ?>">
        <h2 class="panel-title">💝 民眾愛心捐贈回報審核中心</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">以下是熱心民眾填寫的回報。收到實體包裹或確認轉帳無誤後，請給予「核准」。</p>

        <div class="filter-btn-group">
          <button type="button" class="filter-btn active" onclick="filterDonations('all')">📋 顯示全部</button>
          <button type="button" class="filter-btn" onclick="filterDonations('material')">🦴 實物捐贈明細</button>
          <button type="button" class="filter-btn" onclick="filterDonations('money')">💳 愛心捐款流水帳</button>
        </div>

        <?php if ($user_donations_res && $user_donations_res->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>捐贈會員</th>
                <th>項目明細 / 匯款後五碼</th>
                <th>數量 / 金額</th>
                <th>配送/提供方式</th>
                <th>物流單據 / 匯款截圖</th>
                <th>回報時間</th>
                <th>審核狀態</th>
                <th>決策操作</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $user_donations_res->fetch_assoc()): 
                  $row_type = ($row['shipping_method'] === '銀行轉帳 / 匯款') ? 'money' : 'material';
              ?>
                <tr class="donation-row" data-type="<?= $row_type ?>">
                  <td><?= $row['id'] ?></td>
                  <td><b><?= htmlspecialchars($row['user_name']) ?></b></td>
                  <td><?= htmlspecialchars($row['item_name']) ?></td>
                  <td>
                    <?php if($row_type === 'money'): ?>
                      <strong style="color:#e65100; font-size:16px;">$<?= number_format($row['quantity']) ?></strong> 元
                    <?php else: ?>
                      <strong style="color:#5d4037; font-size:16px;"><?= $row['quantity'] ?></strong> 件/袋
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="background:<?= $row_type==='money'?'#e8f5e9':'#e3f2fd' ?>; color:<?= $row_type==='money'?'#2e7d32':'#0d47a1' ?>; padding:3px 8px; border-radius:5px; font-size:13px; font-weight:bold;">
                      <?= htmlspecialchars($row['shipping_method']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                      <a href="<?= htmlspecialchars($row['image_path']) ?>" target="_blank" title="點擊檢視高解析度原圖">
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" class="pet-thumb" alt="憑證">
                      </a>
                    <?php else: ?>
                      <span style="color:#aaa; font-size:13px;">（未附憑證）</span>
                    <?php endif; ?>
                  </td>
                  <td><?= $row['created_at'] ?></td>
                  <td>
                    <?php if ($row['status'] === '待審核'): ?>
                      <span class="status-badge pending">⏳ 待審核</span>
                    <?php elseif ($row['status'] === '已核準'): ?>
                      <span class="status-badge approved">✅ 已核准</span>
                    <?php else: ?>
                      <span class="status-badge rejected"><?= $row_type === 'money' ? '❌ 帳目不符' : '❌ 已拒絕' ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($row['status'] === '待審核'): ?>
                      <a href="admin.php?user_donation_action=approve&id=<?= $row['id'] ?>" class="btn-action btn-success" onclick="return confirm('確定要核准這筆捐贈並列入紀錄嗎？')">核准</a>
                      
                      <?php if($row_type === 'money'): ?>
                        <a href="admin.php?user_donation_action=reject&id=<?= $row['id'] ?>" class="btn-action btn-danger" style="background-color: #d84315;" onclick="return confirm('查無此筆帳目嗎？這將會發送對帳失敗通知給民眾，但保留紀錄。')">帳目不符</a>
                      <?php else: ?>
                        <a href="admin.php?user_donation_action=reject&id=<?= $row['id'] ?>" class="btn-action btn-danger" onclick="return confirm('確定駁回這筆捐贈明細嗎？')">拒絕</a>
                      <?php endif; ?>

                    <?php else: ?>
                      <?php if ($row['status'] === '已拒絕' && $row_type === 'money'): ?>
                        <a href="admin.php?user_donation_action=reset&id=<?= $row['id'] ?>" class="btn-action" style="background-color: #0288d1;" onclick="return confirm('確定要將此筆款項重新轉為「待審核」再次對帳嗎？')">🔄 重新核對</a>
                      <?php else: ?>
                        <span style="color:#aaa; font-size:13px;">✔ 審核已結案</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="no-data">目前尚無民眾提交物資捐贈意願回報。</div>
        <?php endif; ?>
      </div>

      <div id="panel-wiki_manage" class="tab-panel <?= $active_tab=='wiki_manage'?'active':'' ?>">
        <h2 class="panel-title">📖 寵物照護小百科文章管理中心</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">您可以在此發布實用的日常飼養與醫療照護觀念，普及新手飼主正確教育。</p>
        
        <form method="post" action="admin.php?tab=wiki_manage" class="inline-form" style="display:flex; flex-direction:column; align-items:flex-start; gap:12px;">
          <input type="hidden" name="wiki_action" value="add">
          <div style="width:100%;">
            <label style="font-weight:bold; color:#5d4037; display:block; margin-bottom:5px;">1. 文章標題：</label>
            <input type="text" name="title" placeholder="例如：貓咪日常水分補充三大妙招" required style="width:100%; max-width:500px;">
          </div>
          <div style="width:100%;">
            <label style="font-weight:bold; color:#5d4037; display:block; margin-bottom:5px;">2. 前台簡介/摘要 (不超過40字)：</label>
            <input type="text" name="summary" placeholder="例如：貓咪不愛喝水容易引發腎臟疾病，可以用流動水來吸引牠們..." required style="width:100%; max-width:500px;">
          </div>
          <div style="width:100%;">
            <label style="font-weight:bold; color:#5d4037; display:block; margin-bottom:5px;">3. 完整專家照護內文：</label>
            <textarea name="content" placeholder="請填寫詳細文章科普指南..." required style="width:100%; max-width:600px; height:120px; padding:10px; border-radius:6px; border:1px solid #ccc; font-size:14px; outline:none; font-family:inherit;"></textarea>
          </div>
          <button type="submit" class="btn-action btn-success" style="border-radius:6px; padding: 10px 20px; font-size:14px; margin-top:5px;">➕ 立即發布照護文章</button>
        </form>

        <h3 style="margin-top:30px; font-size:16px; color:#5d4037;">目前已發布指南清單</h3>
        <?php if ($wiki_admin_res && $wiki_admin_res->num_rows > 0): ?>
          <table>
            <thead>
              <tr>
                <th style="width:25%;">指南標題</th>
                <th style="width:50%;">文章簡介摘要</th>
                <th style="width:25%;">管理操作</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($w_row = $wiki_admin_res->fetch_assoc()): ?>
                <tr>
                  <td style="text-align:left; font-weight:bold;">💡 <?= htmlspecialchars($w_row['title']) ?></td>
                  <td style="text-align:left; font-size:13.5px; color:#666;"><?= htmlspecialchars($w_row['summary']) ?></td>
                  <td>
                    <form method="post" action="admin.php?tab=wiki_manage" style="display:inline;" onsubmit="return confirm('確定要完全下架並刪除這篇照護文章嗎？');">
                      <input type="hidden" name="wiki_action" value="delete">
                      <input type="hidden" name="id" value="<?= $w_row['id'] ?>">
                      <button type="submit" class="btn-action btn-danger">刪除下架</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="no-data">目前資料庫中尚無任何自訂百科文章。</div>
        <?php endif; ?>
      </div>

    </div> </div>

  <script>
    function switchTab(tabName) {
      document.querySelectorAll('.nav-tab').forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
      
      const targetBtn = document.getElementById('tab-' + tabName);
      const targetPanel = document.getElementById('panel-' + tabName);
      
      if (targetBtn && targetPanel) {
          targetBtn.classList.add('active');
          targetPanel.classList.add('active');
          window.history.pushState({}, '', 'admin.php?tab=' + tabName);
      }
    }

    function filterDonations(type) {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        if(event && event.target) {
            event.target.classList.add('active');
        }

        document.querySelectorAll('.donation-row').forEach(row => {
            if (type === 'all' || row.getAttribute('data-type') === type) {
                row.style.display = ''; 
            } else {
                row.style.display = 'none'; 
            }
        });
    }

    window.addEventListener('DOMContentLoaded', (event) => {
        const activeTabFromPHP = '<?= $active_tab ?>';
        if(activeTabFromPHP) {
            switchTab(activeTabFromPHP);
        }
    });
  </script>
</body>
</html>
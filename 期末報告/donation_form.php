<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    if (!isset($_SESSION['username'])) {
        die("請先登入系統後再填寫捐贈表單！");
    }
    
    $user_name = $_SESSION['username'];
    $donation_type = $_POST['donation_type'] ?? 'material'; // material (物資) 或 money (金錢)
    $image_path = null; 

    // 🌟 1. 根據捐贈類型，決定寫入資料庫的內容
    if ($donation_type === 'money') {
        $amount = intval($_POST['money_amount']);
        $last_five = trim($_POST['bank_last_five']);
        
        $item_name = "【金錢捐贈】轉帳後五碼：" . $last_five;
        $quantity = $amount; 
        $shipping_method = "銀行轉帳 / 匯款";
    } else {
        $item_name = trim($_POST['item_name']);
        $quantity = intval($_POST['quantity']);
        $shipping_method = $_POST['shipping_method'];
    }

    // 🌟 2. 配合前端拆分上傳欄位，後端動態判斷要抓哪一個 input 的 name
    $file_field = ($donation_type === 'money') ? 'donation_img_money' : 'donation_img_material';

    // 處理照片上傳邏輯
    if (isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES[$file_field]['tmp_name'];
        $file_name = $_FILES[$file_field]['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (in_array($file_ext, $allowed_exts)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_file_name = 'donation_' . date('Ymd_His') . '_' . rand(100, 999) . '.' . $file_ext;
            $target_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                $image_path = $target_path;
            }
        }
    }

    // 🌟 3. 後端安全防護：若沒有成功拿到圖片路徑，直接阻斷不寫入資料表
    if (empty($image_path)) {
        $message = "<div class='alert error'>❌ 錯誤！您必須上傳相關物資憑證或匯款成功截圖。</div>";
    } elseif (!empty($item_name) && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO user_donations (user_name, item_name, quantity, shipping_method, image_path, status) VALUES (?, ?, ?, ?, ?, '待審核')");
        $stmt->bind_param("ssiss", $user_name, $item_name, $quantity, $shipping_method, $image_path);
        
        if ($stmt->execute()) {
            $message = "<div class='alert success'>🎉 回報成功！您的愛心回報已送交管理員審核，謝謝您的愛心！</div>";
        } else {
            $message = "<div class='alert error'>❌ 系統錯誤，請稍後再試。</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert error'>❌ 請填寫正確的回報資訊與金額/數量。</div>";
    }
}

$supply_res = $conn->query("SELECT * FROM donation_needs WHERE status = '募集中' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>📦 園區急需物資與愛心捐贈</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Noto Sans TC', sans-serif; }
    body { background-color: #fffde7; color: #5d4037; padding: 40px 20px; }
    .container { max-width: 850px; margin: 0 auto; background: white; border-radius: 20px; padding: 35px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
    h1 { color: #8d6e00; text-align: center; margin-bottom: 15px; }
    .btn-back { display: inline-block; text-decoration: none; background: #fbc02d; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; }
    .intro-text { text-align: center; color: #795548; font-size: 15px; line-height: 1.6; margin-bottom: 30px; }
    
    .supply-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 35px; }
    .supply-card { background: #fffde7; padding: 20px; border-radius: 12px; border-left: 5px solid #d32f2f; }
    .supply-name { font-size: 16px; font-weight: bold; color: #333; }
    .supply-qty { margin-top: 8px; font-size: 14px; color: #e65100; font-weight: bold; }

    .info-section { background: #fafafa; border: 1px dashed #bcaaa4; padding: 25px; border-radius: 12px; margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width:600px){ .info-section { grid-template-columns: 1fr; } }
    .info-box h3 { color: #5d4037; margin-bottom: 12px; font-size: 17px; border-bottom: 2px solid #ffe082; padding-bottom: 4px; }
    .info-box p { font-size: 14px; color: #6d4c41; line-height: 1.8; margin-bottom: 5px; }

    .donation-form-box { background: #fff8e1; border: 2px solid #ffe082; padding: 30px; border-radius: 15px; margin-top: 40px; }
    .form-tabs { display: flex; gap: 10px; margin-bottom: 25px; justify-content: center; }
    .form-tab-btn { background: #ffe082; border: none; padding: 10px 20px; font-weight: bold; color: #5d4037; border-radius: 20px; cursor: pointer; font-size: 14px; transition: 0.2s; }
    .form-tab-btn.active { background: #e65100; color: white; }
    
    .form-panel { display: none; }
    .form-panel.active { display: block; }
    .form-group { margin-bottom: 15px; text-align: left; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #5d4037; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; }
    .btn-submit { background: #e65100; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; font-size: 16px; margin-top: 10px; }
    .btn-submit:hover { background: #bf360c; }
    
    .alert { padding: 12px; border-radius: 6px; font-weight: bold; margin-bottom: 20px; text-align: center; }
    .alert.success { background: #e8f5e9; color: #2e7d32; }
    .alert.error { background: #ffebee; color: #c62828; }
    .login-prompt-btn { display: block; text-align: center; text-decoration: none; background: #fbc02d; color: white; padding: 15px; border-radius: 10px; font-weight: bold; margin-top: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="btn-back">⬅ 返回首頁</a>
    <h1>📦 園區愛心捐贈專區</h1>
    <p class="intro-text">
      園區內的浪浪們日常消耗極大，不論是實體物資或醫藥經費，都非常需要各界的愛心協助。<br>
      如果您願意伸出援手，可參考下方資訊，並在登入後填寫表單回報，由衷感謝您的善心！
    </p>

    <?= $message; ?>

    <h2 style="font-size:20px; margin-bottom:15px; color:#5d4037; border-left: 5px solid #fbc02d; padding-left: 10px;">⏳ 目前極缺之物資品項</h2>
    <div class="supply-grid">
      <?php if ($supply_res && $supply_res->num_rows > 0): ?>
        <?php while($supply = $supply_res->fetch_assoc()): 
            $raised = intval($supply['quantity_raised'] ?? 0);
            $needed = htmlspecialchars($supply['quantity_needed']); 
        ?>
          <div class="supply-card">
            <div class="supply-name">🎯 <?= htmlspecialchars($supply['item_name']) ?></div>
            <div class="supply-qty" style="color: #c62828;">
               📊 目前進度：<span style="font-size:18px; font-weight:bold Haus;"><?= $raised ?></span> / <?= $needed ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="supply-card">
          <div class="supply-name">🎯 幼犬離乳高能量罐頭</div>
          <div class="supply-qty">📊 目前進度：0 / 50 罐 (目前極缺)</div>
        </div>
      <?php endif; ?>
    </div>

    <h2 style="font-size:20px; margin-bottom:15px; color:#5d4037; border-left: 5px solid #fbc02d; padding-left: 10px;">💝 捐贈管道與管道資訊</h2>
    <div class="info-section">
      <div class="info-box">
        <h3>🚛 實體物資寄送</h3>
        <p><strong>收件地址：</strong> 100 台北市中正區愛心路 88 號</p>
        <p><strong>收件單位：</strong> 寵物之家 園區物資組 收</p>
        <p><strong>聯絡電話：</strong> (02) 2345-6789</p>
      </div>
      <div class="info-box">
        <h3>💰 愛心經費匯款</h3>
        <p><strong>銀行名稱：</strong> 台灣愛心商業銀行 (代碼: 999)</p>
        <p><strong>戶名：</strong> 社團法人寵物之家流浪動物守護協會</p>
        <p><strong>匯款帳號：</strong> 1234-5678-9012-34</p>
        <p style="color:#d32f2f; font-size:12px;">※ 轉帳後請於下方填寫，感謝您的愛心捐款。</p>
      </div>
    </div>

    <div class="donation-form-box">
      <?php if (isset($_SESSION['username'])): ?>
        
        <div class="form-tabs">
          <button type="button" class="form-tab-btn active" onclick="switchForm('material')">🦴 實物捐贈</button>
          <button type="button" class="form-tab-btn" onclick="switchForm('money')">💳 愛心捐款</button>
        </div>
        
        <form method="post" action="donation_form.php" enctype="multipart/form-data">
          <input type="hidden" name="submit_donation" value="1">
          <input type="hidden" name="donation_type" id="donation_type_input" value="material">

          <div id="panel-material" class="form-panel active">
            <div class="form-group">
              <label>1. 預計捐贈的物資名稱：</label>
              <input type="text" name="item_name" class="form-control" placeholder="例如：成貓低敏乾糧 2袋、幼犬罐頭 10罐">
            </div>
            <div class="form-group">
              <label>2. 數量（請填寫純數字）：</label>
              <input type="number" name="quantity" class="form-control" min="1" placeholder="example: 2">
            </div>
            <div class="form-group">
              <label>3. 預計提供方式：</label>
              <select name="shipping_method" class="form-control">
                <option value="郵寄 / 貨運寄送">🚛 郵寄 / 貨運寄送</option>
                <option value="親自送到園區">🚗 親自送到園區</option>
              </select>
            </div>
            <div class="form-group">
              <label>4. 上傳物資憑證（必填）：</label>
              <input type="file" name="donation_img_material" class="form-control" accept="image/*">
              <small style="color: #888; display: block; margin-top: 4px;">※ 請務必上傳寄件單據、購買證明或包裹外觀照片以利園區核對。</small>
            </div>
          </div>

          <div id="panel-money" class="form-panel">
            <div class="form-group">
              <label>1. 匯款總金額 (新台幣 NTD)：</label>
              <input type="number" name="money_amount" class="form-control" min="1" placeholder="例如：2000">
            </div>
            <div class="form-group">
              <label>2. 您的轉帳帳號後五碼：</label>
              <input type="text" name="bank_last_five" class="form-control" maxlength="5" placeholder="例如：12345">
            </div>
            <div class="form-group">
              <label>3. 上傳匯款成功截圖（必填）：</label>
              <input type="file" name="donation_img_money" class="form-control" accept="image/*">
              <small style="color: #888; display: block; margin-top: 4px;">※ 請務必上傳網路銀行轉帳成功畫面或 ATM 交易明細收據。</small>
            </div>
          </div>

          <button type="submit" class="btn-submit">確認送出愛心（等待管理員核對）</button>
        </form>
        
      <?php else: ?>
        <p style="text-align: center; color: #d84315; font-weight: bold; line-height: 1.6;">
          💡 溫馨提醒：未登入的使用者仍可瀏覽上方募集品項。<br>
          如果您已準備好提供協助，請先登入系統以便我們為您建立「愛心審核進度追蹤」唷！
        </p>
        <a href="login.php?redirect=donation_form.php" class="login-prompt-btn">👉 點此登入 / 註冊系統 ➔</a>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // 🌟 頁籤動態切換 JavaScript
    function switchForm(type) {
        document.querySelectorAll('.form-tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        document.querySelectorAll('.form-panel').forEach(panel => panel.classList.remove('active'));
        document.getElementById('panel-' + type).classList.add('active');
        
        document.getElementById('donation_type_input').value = type;
        
        // 🌟 核心修正：動態切換所有輸入欄位與對應檔案上傳欄位的 required 狀態，避免隱藏面板卡單
        if(type === 'money') {
            document.getElementsByName('money_amount')[0].required = true;
            document.getElementsByName('bank_last_five')[0].required = true;
            document.getElementsByName('donation_img_money')[0].required = true; // 金額面板檔案必填
            
            document.getElementsByName('item_name')[0].required = false;
            document.getElementsByName('quantity')[0].required = false;
            document.getElementsByName('donation_img_material')[0].required = false; // 移除物資面板檔案必填
        } else {
            document.getElementsByName('money_amount')[0].required = false;
            document.getElementsByName('bank_last_five')[0].required = false;
            document.getElementsByName('donation_img_money')[0].required = false; // 移除金額面板檔案必填
            
            document.getElementsByName('item_name')[0].required = true;
            document.getElementsByName('quantity')[0].required = true;
            document.getElementsByName('donation_img_material')[0].required = true; // 物資面板檔案必填
        }
    }
    
    // 預設初始化
    window.onload = function() {
        if(document.getElementsByName('item_name')[0]) {
            document.getElementsByName('item_name')[0].required = true;
            document.getElementsByName('quantity')[0].required = true;
            document.getElementsByName('donation_img_material')[0].required = true; // 預設物資面板檔案必填
        }
    }
  </script>
</body>
</html>
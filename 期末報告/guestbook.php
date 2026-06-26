<?php
session_start();
include('db.php');

// ==========================================
// 📥 處理「新增」留言邏輯
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_msg'])) {
    if (!isset($_SESSION['username'])) {
        die("請先登入系統後再執行留言！");
    }
    $user_name = $_SESSION['username'];
    $content = trim($_POST['content']);
    $image_path = null;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_name = $_FILES['photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_ext, $allowed_exts)) {
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            $new_file_name = "guest_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
            $target_path = "uploads/" . $new_file_name;
            if (move_uploaded_file($file_tmp, $target_path)) {
                $image_path = $target_path;
            }
        }
    }
    
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO guestbook (user_name, content, image_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user_name, $content, $image_path);
        $stmt->execute();
        header("Location: guestbook.php?msg=success");
        exit();
    }
}

// ==========================================
// ✏️ 處理「編輯更新」留言邏輯 (方案B 後端)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_msg'])) {
    if (!isset($_SESSION['username'])) {
        die("請先登入系統！");
    }
    $msg_id = intval($_POST['msg_id']);
    $new_content = trim($_POST['edit_content']);
    $current_user = $_SESSION['username'];

    // 安全檢查：先確認這則留言真的是他寫的
    $chk_stmt = $conn->prepare("SELECT user_name, image_path FROM guestbook WHERE id = ?");
    $chk_stmt->bind_param("i", $msg_id);
    $chk_stmt->execute();
    $chk_res = $chk_stmt->get_result();

    if ($chk_res->num_rows === 1) {
        $msg_data = $chk_res->fetch_assoc();
        if ($msg_data['user_name'] === $current_user) {
            
            $image_path = $msg_data['image_path']; // 預設使用原本的照片路徑

            // 📸 判斷使用者有沒有上傳「新照片」要替換
            if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['edit_photo']['tmp_name'];
                $file_name = $_FILES['edit_photo']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_ext, $allowed_exts)) {
                    // 1. 刪除原本的舊照片，釋放硬碟空間
                    if (!empty($image_path) && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    // 2. 上傳新照片
                    $new_file_name = "guest_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
                    $target_path = "uploads/" . $new_file_name;
                    if (move_uploaded_file($file_tmp, $target_path)) {
                        $image_path = $target_path; // 替換成新路徑
                    }
                }
            }

            if (!empty($new_content)) {
                // 更新資料庫
                $update_stmt = $conn->prepare("UPDATE guestbook SET content = ?, image_path = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $new_content, $image_path, $msg_id);
                $update_stmt->execute();
                header("Location: guestbook.php?edit=success");
                exit();
            }
        } else {
            die("危險操作！你沒有權限修改此留言。");
        }
    } else {
        die("找不到該則留言！");
    }
}

// 讀取所有留言
$msg_res = $conn->query("SELECT * FROM guestbook ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>🐾 幸福家庭留言板</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Noto Sans TC', sans-serif; }
    body { background-color: #fffde7; color: #5d4037; padding: 40px 20px; }
    .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 20px; padding: 35px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
    h1 { color: #8d6e00; text-align: center; margin-bottom: 25px; }
    .btn-back { display: inline-block; text-decoration: none; background: #fbc02d; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; }
    
    /* 留言表單 */
    .msg-form { background: #fffde7; padding: 20px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #ffe082; }
    .msg-form textarea { width: 100%; height: 100px; padding: 12px; border-radius: 8px; border: 1px solid #ccc; outline: none; margin: 10px 0; resize: none; font-size: 15px; }
    .file-input-group { margin: 10px 0; font-size: 14px; }
    .btn-submit { background: #f9a825; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; width: 100%; }
    .btn-submit:hover { background: #f57f17; }

    /* 留言列表卡片 */
    .msg-card { background: #fafafa; border-left: 5px solid #ffb300; padding: 20px; border-radius: 0 12px 12px 0; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); position: relative; }
    .msg-user { font-weight: bold; color: #5d4037; font-size: 16px; display: flex; justify-content: space-between; margin-bottom: 10px; padding-right: 120px; /* 留空間給兩個按鈕 */ }
    .msg-text { font-size: 15px; color: #795548; line-height: 1.6; white-space: pre-line; }
    .msg-time { font-size: 11px; color: #bcaaa4; font-weight: normal; margin-left: 10px; }
    
    /* 按鈕群組 */
    .action-group { position: absolute; top: 18px; right: 20px; display: flex; gap: 8px; }
    .btn-action { text-decoration: none; font-size: 13px; font-weight: 500; padding: 3px 8px; border-radius: 4px; transition: all 0.2s; cursor: pointer; background: transparent; }
    .btn-edit { color: #0288d1; border: 1px solid #0288d1; }
    .btn-edit:hover { background: #0288d1; color: white; }
    .btn-delete { color: #d32f2f; border: 1px solid #d32f2f; }
    .btn-delete:hover { background: #d32f2f; color: white; }
    
    /* 圖片樣式 */
    .msg-image-wrapper { margin-top: 12px; max-width: 400px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .msg-image { width: 100%; height: auto; display: block; }

    /* ==========================================
       🌟 CSS 彈出視窗 (Modal) 樣式
       ========================================== */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; visibility: hidden; opacity: 0; transition: all 0.3s ease; }
    .modal-overlay.active { visibility: visible; opacity: 1; }
    .modal-box { background: white; width: 90%; max-width: 500px; border-radius: 16px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; transform: translateY(-20px); transition: all 0.3s ease; }
    .modal-overlay.active .modal-box { transform: translateY(0); }
    .modal-title { font-size: 18px; color: #8d6e00; font-weight: bold; margin-bottom: 15px; }
    .modal-close { position: absolute; top: 15px; right: 20px; font-size: 22px; color: #aaa; cursor: pointer; background: none; border: none; }
    .modal-close:hover { color: #333; }
    .modal-box textarea { width: 100%; height: 120px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin: 10px 0; resize: none; font-size: 15px; }
    .modal-btn-group { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
    .btn-cancel { background: #eee; color: #555; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
    .btn-save { background: #0288d1; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .btn-save:hover { background: #01579b; }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="btn-back">⬅ 返回首頁</a>
    <h1>💝 幸福家庭留言板</h1>
    
    <?php if (isset($_SESSION['username'])): ?>
      <form method="post" action="guestbook.php" class="msg-form" enctype="multipart/form-data">
        <input type="hidden" name="submit_msg" value="1">
        <label><b>分享你的幸福家庭故事：</b> (目前的登入帳號：<?= htmlspecialchars($_SESSION['username']) ?>)</label>
        <textarea name="content" placeholder="請輸入您與毛孩的幸福生活點滴..." required></textarea>
        <div class="file-input-group">
          <label style="color:#5d4037; font-weight:bold;">📸 上傳毛孩近照 (選填)：</label>
          <input type="file" name="photo" accept="image/*">
        </div>
        <button type="submit" class="btn-submit">發佈留言</button>
      </form>
    <?php else: ?>
      <p style="text-align: center; color: #e65100; font-weight: bold; background: #ffe082; padding: 12px; border-radius: 8px; margin-bottom: 30px;">
        💡 溫馨提醒：您需要先登入系統才能留言與分享照片唷！
      </p>
    <?php endif; ?>

    <h2>🌟 歷年溫馨回報 (<?= $msg_res->num_rows ?> 則)</h2>
    <div style="margin-top: 15px;">
      <?php if ($msg_res->num_rows > 0): ?>
        <?php while($msg = $msg_res->fetch_assoc()): ?>
          <div class="msg-card">
            <div class="msg-user">
              <span>👤 <?= htmlspecialchars($msg['user_name']) ?> 的分享 <span class="msg-time"><?= $msg['created_at'] ?></span></span>
            </div>
            
            <?php if (isset($_SESSION['username']) && $_SESSION['username'] === $msg['user_name']): ?>
              <div class="action-group">
                <button type="button" class="btn-action btn-edit" 
        			onclick="openEditModal(<?= $msg['id'] ?>, '<?= htmlspecialchars(str_replace(array("\r", "\n", "'"), array(' ', ' ', "\'"), $msg['content']), ENT_QUOTES, 'UTF-8') ?>')">編輯</button>

                <a href="delete_msg.php?id=<?= $msg['id'] ?>" class="btn-action btn-delete" onclick="return confirm('確定要刪除這則溫馨留言嗎？')">刪除</a>
              </div>
            <?php endif; ?>
            
            <p class="msg-text"><?= htmlspecialchars($msg['content']) ?></p>
            
            <?php if (!empty($msg['image_path']) && file_exists($msg['image_path'])): ?>
              <div class="msg-image-wrapper">
                <img src="<?= htmlspecialchars($msg['image_path']) ?>" class="msg-image" alt="幸福照">
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color:#aaa; text-align:center; padding: 30px;">目前尚無留言，歡迎成為第一個分享幸福的人！</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="modal-overlay" id="editModal">
    <div class="modal-box">
      <button class="modal-close" onclick="closeEditModal()">&times;</button>
      <div class="modal-title">✏️ 修改您的貼文</div>
      
      <form method="post" action="guestbook.php" enctype="multipart/form-data">
        <input type="hidden" name="update_msg" value="1">
        <input type="hidden" name="msg_id" id="modal_msg_id">
        
        <label><b>內文修改：</b></label>
        <textarea name="edit_content" id="modal_content" required></textarea>
        
        <div class="file-input-group" style="margin-top: 15px;">
          <label style="color:#5d4037; font-weight:bold;">📸 更換毛孩近照 (選填)：</label>
          <input type="file" name="edit_photo" accept="image/*">
          <small style="color:#999; display:block; margin-top:4px;">💡 若不更換照片，請保持留空即可。</small>
        </div>
        
        <div class="modal-btn-group">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">取消</button>
          <button type="submit" class="btn-save">儲存修改</button>
        </div>
      </form>
    </div>
  </div>

          <script>
        // 確保函式在全域環境，讓按鈕一定找得到
        window.openEditModal = function(msgId, msgContent) {
            // 1. 先強制彈出警告視窗，測試按鈕到底有沒有活著
            //alert("按鈕動了！收到 ID: " + msgId);

            const modal = document.getElementById('editModal');
            const modalIdInput = document.getElementById('modal_msg_id');
            const modalTextArea = document.getElementById('modal_content');

            if (modal && modalIdInput && modalTextArea) {
                modalIdInput.value = msgId;
                modalTextArea.value = msgContent;
                modal.classList.add('active');
            } else {
                alert("錯誤：網頁上找不到 id='editModal' 的彈出視窗元件！");
            }
        };

        window.closeEditModal = function() {
            const modal = document.getElementById('editModal');
            if (modal) modal.classList.remove('active');
        };

        // 點擊外面關閉
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        });
        </script>
</body>
</html>
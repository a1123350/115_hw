<?php
session_start();
include('db.php');
$wiki_res = $conn->query("SELECT * FROM pet_wiki ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>📖 寵物照護小百科</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Noto Sans TC', sans-serif; }
    body { background-color: #fffde7; color: #5d4037; padding: 40px 20px; }
    .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 20px; padding: 35px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
    h1 { color: #8d6e00; text-align: center; margin-bottom: 5px; }
    .btn-back { display: inline-block; text-decoration: none; background: #fbc02d; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; }
    
    /* 🆕 搜尋欄樣式 */
    .search-wrapper { max-width: 500px; margin: 25px auto; position: relative; }
    .search-input { width: 100%; padding: 12px 20px 12px 45px; border: 2px solid #ffe082; border-radius: 30px; font-size: 15px; outline: none; transition: 0.3s; color: #5d4037; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
    .search-input:focus { border-color: #fbc02d; box-shadow: 0 4px 15px rgba(251,192,45,0.2); }
    .search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #fbc02d; font-size: 16px; }

    .wiki-grid { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
    .wiki-card { background: #fffde7; padding: 20px; border-radius: 12px; cursor: pointer; transition: 0.2s; border: 1px solid #fff59d; }
    .wiki-card:hover { background: #fff9c4; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .wiki-title { font-size: 18px; font-weight: bold; color: #5d4037; margin-bottom: 6px; }
    .wiki-summary { font-size: 14px; color: #795548; line-height: 1.5; }
    
    /* 簡易內文彈窗(Modal) */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
    .modal-content { background: white; max-width: 600px; width: 100%; border-radius: 16px; padding: 30px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .close-btn { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #aaa; }
    .close-btn:hover { color: #000; }
    .modal-body { font-size: 15px; color: #424242; line-height: 1.8; white-space: pre-line; margin-top: 15px; }
    .no-results { text-align: center; color: #aaa; padding: 30px; display: none; font-size: 15px; }
  </style>
</head>
<body>
  <div class="container">
    <a href="index.php" class="btn-back">⬅ 返回首頁</a>
    <h1>📖 寵物照護小百科</h1>
    <p style="text-align:center; color:#795548; font-size:14px;">收錄最專業的貓狗新手飼主觀念，普及正確知識，攜手降低棄養率。</p>

    <div class="search-wrapper">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" id="wikiSearch" class="search-input" placeholder="輸入關鍵字搜尋... (例如：幼貓、挑食)" onkeyup="searchWiki()">
    </div>

    <div class="wiki-grid" id="wikiGrid">
      <?php if ($wiki_res && $wiki_res->num_rows > 0): ?>
        <?php while($wiki = $wiki_res->fetch_assoc()): ?>
          <div class="wiki-card" data-search="<?= htmlspecialchars(strtolower($wiki['title'].$wiki['content'])) ?>" onclick="openWiki('<?= htmlspecialchars($wiki['title'], ENT_QUOTES) ?>', `<?= htmlspecialchars($wiki['content'], ENT_QUOTES) ?>`)">
            <div class="wiki-title">💡 <?= htmlspecialchars($wiki['title']) ?></div>
            <div class="wiki-summary"><?= htmlspecialchars($wiki['summary'] ?? '點擊閱讀完整文章資訊...') ?></div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="wiki-card" data-search="幼貓進家第一週注意事項剛到新家的幼貓容易緊張" onclick="openWiki('幼貓進家第一週注意事項', '剛到新家的幼貓容易緊張，請先將牠安置在小房間，準備好砂盆與水。一週內千萬不要洗澡，避免免疫力下降生病。')">
          <div class="wiki-title">💡 幼貓進家第一週注意事項</div>
          <div class="wiki-summary">準備獨立安靜的房間，切勿急著洗澡與強行抱持...</div>
        </div>
        <div class="wiki-card" data-search="汪星人挑食怎麼辦挑食往往是主人寵出來的" onclick="openWiki('汪星人挑星怎麼辦？', '挑食往往是主人寵出來的。建議採取定時定量制，放碗20分鐘後不管有沒有吃完直接收走。')">
          <div class="wiki-title">💡 汪星人挑食怎麼辦？</div>
          <div class="wiki-summary">定時定量是關鍵，固定放飯20分鐘後不吃即收走...</div>
        </div>
      <?php endif; ?>
      
      <div class="no-results" id="noResults">🔍 找不到與關鍵字相關的照護指南喔！</div>
    </div>
  </div>

  <div class="modal" id="wikiModal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeWiki()">&times;</span>
      <h3 id="modalTitle" style="color:#8d6e00; border-bottom: 2px solid #fff8e1; padding-bottom: 10px;"></h3>
      <div class="modal-body" id="modalBody"></div>
    </div>
  </div>

  <script>
    function openWiki(title, content) {
      document.getElementById('modalTitle').innerText = title;
      document.getElementById('modalBody').innerText = content;
      document.getElementById('wikiModal').style.display = 'flex';
    }
    function closeWiki() {
      document.getElementById('wikiModal').style.display = 'none';
    }
    
    // 🌟 🆕 新增：前端即時搜尋過濾函式
    function searchWiki() {
      const keyword = document.getElementById('wikiSearch').value.toLowerCase().trim();
      const cards = document.querySelectorAll('.wiki-card');
      let visibleCount = 0;
      
      cards.forEach(card => {
          const contentText = card.getAttribute('data-search');
          if (contentText.includes(keyword)) {
              card.style.display = ''; // 顯示
              visibleCount++;
          } else {
              card.style.display = 'none'; // 隱藏
          }
      });
      
      // 判斷是否顯示「查無結果」
      document.getElementById('noResults').style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('wikiModal');
      if (event.target == modal) { modal.style.display = 'none'; }
    }
  </script>
</body>
</html>
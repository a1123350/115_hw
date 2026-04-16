<?php
// 將 Cookie 時間設為過去，即可刪除
setcookie("uID", "", time() - 3600);
header("Location: index.php");
?>
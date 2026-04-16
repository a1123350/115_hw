<?php
session_start();
session_unset(); 
session_destroy(); 
echo "登出成功，正在返回首頁...";
header("Refresh:1; url=index.php");
?>
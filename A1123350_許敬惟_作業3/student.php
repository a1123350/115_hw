<?php
session_start();
if(isset($_SESSION["role"]) && $_SESSION["role"] == 'student'){
    echo "<h1>學生系統</h1>";
    echo "同學你好，歡迎查看你的個人課表。<br/>";
    echo "<a href='logout.php'>登出</a>";
} else {
    echo "<h1>請先登入學生帳號</h1>";
    header("Refresh:2; url=index.php");
}
?>
<?php
session_start();
if(isset($_SESSION["role"]) && $_SESSION["role"] == 'teacher'){
    echo "<h1>教師系統</h1>";
    echo "老師您好，您可以在此輸入學生成績。<br/>";
    echo "<a href='logout.php'>登出</a>";
} else {
    echo "<h1>請先登入老師帳號</h1>";
    header("Refresh:2; url=index.php");
}
?>
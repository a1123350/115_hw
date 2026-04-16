<?php
session_start();
if(isset($_SESSION["role"]) && $_SESSION["role"] == 'admin'){
    echo "<h1>管理員系統</h1>";
    echo "歡迎，管理員！您擁有修改系統的權限。<br/>";
    echo "<a href='logout.php'>登出</a>";
} else {
    echo "<h1>權限不足或未登入！將跳回登入頁面</h1>";
    header("Refresh:2; url=index.php");
}
?>
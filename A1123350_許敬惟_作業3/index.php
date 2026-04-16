<?php
if(isset($_COOKIE['uID'])){
    echo "上次登入的 ID: " . $_COOKIE['uID'] . " <br/>";
    echo "<a href='cookiedel.php'>清除 ID 紀錄 (Delete Cookie)</a><hr/>";
}
?>

<h2>系統登入</h2>
<form action="logincheck.php" method="POST">
    帳號 (ID): <input type="text" name="uID" required><br/>
    密碼 (PWD): <input type="password" name="uPwd" required><br/>
    <input type="submit" value="登入">
</form>

<p>測試帳號提示：<br/>
管理員: admin / 111 | 教師: teacher / 222 | 學生: student / 333</p>

<?php echo "現在時間：" . date("Y-m-d H:i:s", time()); ?>
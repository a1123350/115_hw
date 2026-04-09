<html>
<head>
    <title>夏令營登入系統</title>
</head>

<body style="background-color:rgb(165, 235, 223)">

<center>
    <font size="6"><strong>夏令營報名系統登入</strong></font>
    <hr width="50%">

    <form action="登入判斷.php" method="post">
        <font size="4">
            帳號：<input type="text" name="username"><br><br>
            密碼：<input type="password" name="password"><br><br>

            <input type="submit" value="登入">
            <input type="reset" value="清除">
        </font>
    </form>

    <br>

    <?php
    if(isset($_GET['error'])){
        echo "<font color='red'>登入失敗！請重新輸入帳號密碼</font>";
    }
    ?>

</center>

</body>
</html>
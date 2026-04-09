<html>
<head>
    <title>夏令營行程與報名表</title>
</head>
<body style="background-color:rgb(165, 235, 223)">
    <font size="5" color="black"><strong>夏令營活動行程</strong></font><br>
    <hr>
    
    <table border="1" width="50%" height="150" style="color:black; border-color:black;">
        <caption><b style="color:black;">夏令營活動行程</b></caption>
        <tr>
            <td><b>方案</b></td>
            <td><b>行程</b></td>
            <td><b>費用</b></td>
        </tr>
        <tr>
            <td>方案一</td>
            <td>行程A</td>
            <td>200</td>
        </tr>
        <tr>
            <td>方案二</td>
            <td>行程B</td>
            <td>250</td>
        </tr>
        <tr>
            <td>方案三</td>
            <td>行程C</td>
            <td>300</td>
        </tr>
    </table>

    <br>
    <font size="5" color="black"><strong>夏令營活動報名</strong></font>
    <hr>

    <form name="event_registration" action="報名成功畫面.php" method="post">
        <font color="black">
            姓名: <input name="name" size="10" required><br>
            學號: <input type="text" name="student_id" size="10" maxlength="8"><br>
            電話: <input type="text" name="phone_number" size="10" maxlength="10"><br>
            性別:
            <input type="radio" name="sex" value="male" checked>男性
            <input type="radio" name="sex" value="female">女性<br>
            行程:<br>
            <select name="event" size="1">
                <option value="行程A">行程A</option>
                <option value="行程B">行程B</option>
                <option value="行程C">行程C</option>
            </select><br><br>
            
            <input type="hidden" name="admin_note" value="tom">
            
            <input type="submit" name="send" value="確認送出">
            <input type="reset" name="clear" value="重設內容"><br>
        </font>
    </form>

    <br>
    <a href="夏令營活動.php" style="color:black;"><u>回首頁</u></a>
</body>
</html>
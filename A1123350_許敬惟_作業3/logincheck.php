<?php
session_start();

$uID = $_POST['uID'];
$uPwd = $_POST['uPwd'];
$cookie_expire = time() + 3600;

if ($uID == 'admin' && $uPwd == '111') {
    $_SESSION["role"] = 'admin';
    setcookie("uID", $uID, $cookie_expire);
    header("Location: admin.php");
} elseif ($uID == 'teacher' && $uPwd == '222') {
    $_SESSION["role"] = 'teacher';
    setcookie("uID", $uID, $cookie_expire);
    header("Location: teacher.php");
} elseif ($uID == 'student' && $uPwd == '333') {
    $_SESSION["role"] = 'student';
    setcookie("uID", $uID, $cookie_expire);
    header("Location: student.php");
} else {
    echo "帳號或密碼錯誤！3秒後跳回登入頁";
    header("Refresh:3; url=index.php");
}
?>
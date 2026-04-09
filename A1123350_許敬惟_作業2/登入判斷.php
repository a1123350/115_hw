<?php
$username = $_POST['username'];
$password = $_POST['password'];

$correct_user = "AAA";
$correct_pass = "1111";

if($username == $correct_user && $password == $correct_pass){
    header("Location: 夏令營報名表.php");
} else {
    header("Location: 登入頁面.php?error=1");
}
?>
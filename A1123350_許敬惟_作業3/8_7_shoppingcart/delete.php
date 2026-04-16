<?php
$id = $_GET["Id"];

if (isset($_COOKIE[$id])) {
    foreach ($_COOKIE[$id] as $key => $value) {
        setcookie($id . "[" . $key . "]", "", time() - 3600);
    }
}
header("Location: shoppingcart.php");
exit;
?>
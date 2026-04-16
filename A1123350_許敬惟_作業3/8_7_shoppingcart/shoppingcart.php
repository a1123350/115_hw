<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>購物車內容</title></head>
<body>
    <h2>您的購物車</h2>
    <table border="1" width="80%">
        <tr bgcolor="#CCCCCC">
            <th>功能</th><th>編號</th><th>名稱</th><th>價格</th><th>數量</th>
        </tr>
        <?php
        $total = 0;

        if (isset($_COOKIE)) {
            foreach ($_COOKIE as $id => $data) {
                if (is_array($data)) {
                    echo "<tr>";
                    echo "<td><a href='delete.php?Id=" . $id . "'>刪除</a></td>";
                    
                    $price = 0;
                    $quantity = 0;
                    
                    foreach ($data as $key => $value) {
                        echo "<td>" . $value . "</td>";
                        if ($key == "Price") $price = $value;
                        if ($key == "Quantity") $quantity = $value;
                    }
                    
                    $total += $price * $quantity;
                    echo "</tr>";
                }
            }
        }
        ?>
    </table>
    <h3>總金額：NT$<?php echo $total; ?> 元</h3>
    <hr>
    <a href="catalog.php">回到商品目錄</a>
</body>
</html>
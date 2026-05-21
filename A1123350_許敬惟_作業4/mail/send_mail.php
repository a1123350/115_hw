<?php

require '../config/db.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';
require '../PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$subject = $_POST['subject'];
$content = $_POST['content'];
$mode = $_POST['mode'];
$delay = (int)$_POST['delay'];
$random_count = (int)$_POST['random_count'];

if ($mode == 'all') {

    $stmt = $pdo->query(
        "SELECT * FROM subscribers"
    );

} else {

    $stmt = $pdo->query(
        "SELECT * FROM subscribers
         ORDER BY RAND()
         LIMIT $random_count"
    );
}

$emails = $stmt->fetchAll();

$total = count($emails);

$current = 0;

foreach ($emails as $row) {

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username = 'a1123350@mail.nuk.edu.tw';

        $mail->Password = 'hazqaozgxonapzql';

        $mail->SMTPSecure = 'tls';

        $mail->Port = 587;

        $mail->setFrom(
            'a1123350@mail.nuk.edu.tw',
            '郵件系統'
        );

        $mail->addAddress($row['email']);

        $mail->isHTML(true);

        $mail->Subject = $subject;

        $mail->Body = nl2br($content);

        $mail->send();

        echo "已寄送："
             . $row['email']
             . "<br>";

    } catch (Exception $e) {

        echo "失敗："
             . $row['email']
             . "<br>";
    }

    $current++;

    $progress = round(
        ($current / $total) * 100
    );

    echo "進度："
         . $progress
         . "%<hr>";

    ob_flush();
    flush();

    sleep($delay);
}

echo "全部寄送完成";
?>
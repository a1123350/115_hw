<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>報名成功｜高雄大學夏令營</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: "PingFang TC", "Microsoft JhengHei", sans-serif;
            background-color: rgb(165, 235, 223);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .success-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px 40px;
            border-radius: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
            animation: fadeIn 0.8s ease-out;
        }

        .icon-check {
            width: 80px;
            height: 80px;
            background-color: #00b894;
            color: white;
            font-size: 50px;
            line-height: 80px;
            border-radius: 50%;
            margin: 0 auto 25px;
            box-shadow: 0 8px 15px rgba(0, 184, 148, 0.3);
        }

        h1 {
            color: #2d3436;
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .message {
            font-size: 1.1rem;
            color: #636e72;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .info {
            text-align: left;
            margin-top: 20px;
            font-size: 1rem;
            color: #2d3436;
            line-height: 1.8;
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 15px;
        }

        .highlight {
            color: #0984e3;
            font-weight: bold;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2d3436;
            text-decoration: underline;
            font-weight: bold;
        }

        .back-link:hover {
            color: #00b894;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

<div class="success-card">
    <div class="icon-check">✓</div>
    <h1>報名成功！</h1>

    <?php
        $userName = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '同學';
        $studentId = isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : '未填寫';
        $phone = isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : '未填寫';
        $sex = isset($_POST['sex']) ? htmlspecialchars($_POST['sex']) : '未填寫';
        $event = isset($_POST['event']) ? htmlspecialchars($_POST['event']) : '未選擇';

        if($sex == "male"){
            $sex = "男性";
        } else if($sex == "female"){
            $sex = "女性";
        }
    ?>

    <div class="message">
        <p><span class="highlight"><?php echo $userName; ?></span> 您好，</p>
        您已成功報名 <span class="highlight"><?php echo $event; ?></span>！
    </div>

    <div class="info">
        <strong>您的報名資料如下：</strong><br>
        姓名：<?php echo $userName; ?><br>
        學號：<?php echo $studentId; ?><br>
        電話：<?php echo $phone; ?><br>
        性別：<?php echo $sex; ?><br>
        行程：<?php echo $event; ?>
    </div>

    <a href="夏令營活動.php" class="back-link">回首頁</a>
</div>

</body>
</html>
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
            margin-bottom: 30px;
        }
        .highlight {
            color: #0984e3;
            font-weight: bold;
            border-bottom: 2px solid #0984e3;
        }

        .back-link {
            display: inline-block;
            margin-top: 10px;
            color: #2d3436;
            text-decoration: underline;
            font-weight: bold;
            transition: color 0.3s;
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
        
        <div class="message">
            <?php
                $userName = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '同學';
                $selectedEvent = isset($_POST['event']) ? htmlspecialchars($_POST['event']) : '夏令營活動';
                
                echo "<p><span class='highlight'>{$userName}</span> 您好，</p>";
                echo "我們已收到您對 <span class='highlight'>{$selectedEvent}</span> 的報名！";
            ?>
        </div>

        <a href="夏令營活動.php" class="back-link">回首頁</a>
    </div>

</body>
</html>
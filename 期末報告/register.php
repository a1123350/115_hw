<?php
include('db.php');

$username = $_POST['username'];
$password = $_POST['password'];
// 🌟 新增：接收確認密碼的值
$confirm_password = $_POST['confirm_password'] ?? '';

// 共用訊息顯示函式（符合黃金色調與背景輪播）
function renderMessage($title, $message, $linkText, $linkHref, $color = '#fbc02d') {
    echo "
    <!DOCTYPE html>
    <html lang='zh-Hant'>
    <head>
        <meta charset='UTF-8'>
        <title>$title - 寵物之家</title>
        <link href='https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap' rel='stylesheet'>
        <style>
            body {
                font-family: 'Noto Sans TC', sans-serif;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                transition: background-image 1s ease-in-out;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .message-box {
                background: rgba(255, 255, 255, 0.95);
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                text-align: center;
                max-width: 400px;
                width: 90%;
            }
            h1 {
                color: $color;
                margin-bottom: 16px;
                font-size: 28px;
            }
            p {
                font-size: 16px;
                color: #555;
                line-height: 1.6;
            }
            a {
                display: inline-block;
                margin-top: 25px;
                padding: 12px 28px;
                background-color: $color;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: bold;
                transition: background-color 0.3s ease, transform 0.2s;
            }
            a:hover {
                filter: brightness(0.9);
                transform: scale(1.05);
            }
        </style>
    </head>
    <body>
        <div class='message-box'>
            <h1>$title</h1>
            <p>$message</p>
            <a href='$linkHref'>$linkText</a>
        </div>

        <script>
            const images = ['image/home page.jpeg', 'image/login page.jpg', 'image/user.webp'];
            let index = 0;
            function changeBackground() {
                document.body.style.backgroundImage = `url('\${images[index]}')`;
                index = (index + 1) % images.length;
            }
            setInterval(changeBackground, 4000);
            changeBackground();
        </script>
    </body>
    </html>
    ";
}

// 🌟 新增安全檢查點：後端確認兩次密碼是否完全相同
if ($password !== $confirm_password) {
    renderMessage("❌ 註冊失敗", "兩次輸入的密碼不一致，請重新填寫！", "返回註冊頁", "register.html", "#d32f2f");
    exit(); // 終止程式碼繼續往下執行
}

// 使用預處理語句檢查帳號是否存在，防止注入
$stmt_check = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt_check->bind_param("s", $username);
$stmt_check->execute();
$result = $stmt_check->get_result();

// 註冊流程處理
if ($result->num_rows > 0) {
    renderMessage("❌ 註冊失敗", "很抱歉，此帳號名稱已被註冊，請換一個名字試試看！", "返回註冊頁", "register.html", "#d32f2f");
} else {
    // 預設角色為一般會員 'user'
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        renderMessage("🎉 註冊成功！", "恭喜您成功建立寵物之家帳號，快去登入並展開你們的故事吧！", "前往登入頁", "login.php");
    } else {
        renderMessage("❌ 系統錯誤", "資料庫寫入失敗，請聯絡系統管理員。" . $stmt->error, "返回註冊頁", "register.html", "#d32f2f");
    }
}
?>
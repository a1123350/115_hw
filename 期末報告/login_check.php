<?php
session_start();
include('db.php');

$username = $_POST['username'];
$password = $_POST['password'];

// 使用預處理語句來防止 SQL 注入
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    $_SESSION['user'] = $user;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username']; // 供首頁與各頁面做 Session 導航欄判斷
    $_SESSION['role'] = $user['role'];
    
    // 💥 獲取登入前原本想去的目標頁面，若沒有，登入後預設直接帶回熱鬧的「首頁 index.php」
    $redirect_page = (isset($_POST['redirect']) && !empty($_POST['redirect'])) ? $_POST['redirect'] : 'user.php';

    // 根據角色轉到不同頁面
    if ($user['role'] == 'admin') {
        header("Location: admin.php");
        exit();
    } else {
        // 一般會員：直接跳轉到他刚才被攔截的特定功能頁（如 visit_form.html / pet_list.php）
        header("Location: " . $redirect_page);
        exit();
    }
} else {
    echo '
    <!DOCTYPE html>
    <html lang="zh-Hant">
    <head>
      <meta charset="UTF-8">
      <title>登入錯誤 - 寵物之家</title>
      <style>
        body {
          font-family: "Noto Sans TC", sans-serif;
          background: #f5f5f5;
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100vh;
          margin: 0;
        }
        .error-box {
          background-color: #ffffff;
          border-top: 5px solid #e53935;
          border-radius: 12px;
          padding: 40px;
          box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
          text-align: center;
          max-width: 400px;
          width: 90%;
        }
        h1 {
          color: #e53935;
          font-size: 24px;
          margin-bottom: 15px;
        }
        p {
          color: #666;
          line-height: 1.6;
          margin-bottom: 10px;
        }
        a {
          display: inline-block;
          margin-top: 15px;
          color: #1e88e5;
          text-decoration: none;
          font-weight: bold;
        }
        a:hover {
          text-decoration: underline;
        }
      </style>
    </head>
    <body>
      <div class="error-box">
        <h1>❌ 登入失敗</h1>
        <p>您的帳號或密碼輸入錯誤，請重新確認。</p>
        <p>若您還沒有註冊，請先註冊成為會員後再登入。</p>
        <a href="login.php">返回登入頁面</a>
      </div>
    </body>
    </html>
    ';
}
?>
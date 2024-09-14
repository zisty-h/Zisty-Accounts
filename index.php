<?php
session_start();

// データベース接続
$mysqli = new mysqli("", "", "", "");

// データベース接続のエラーハンドリング
if ($mysqli->connect_error) {
  die('データベースの接続に失敗しました: ' . $mysqli->connect_error);
  exit();
}

// ユーザー情報を取得
$user_id = $_SESSION["user_id"];

// ログイン状態の確認
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
  $ip_address = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
  $x_forwarded_for = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
  $ip_address = trim($x_forwarded_for[0]);
} else {
  $ip_address = $_SERVER['REMOTE_ADDR'];
}
if (isset($_SESSION["user_id"])) {
  $user_id = $_SESSION["user_id"];
  $session_id = session_id();

  $stmt = $mysqli->prepare("SELECT last_login_at, ip_address FROM users_session WHERE session_id = ? AND username = (SELECT username FROM users WHERE id = ?)");
  if ($stmt === false) {
    die('Prepare statement failed: ' . $mysqli->error);
  }
  $stmt->bind_param("si", $session_id, $user_id);
  $stmt->execute();
  $stmt->bind_result($last_login_at, $session_ip_address);
  $stmt->fetch();
  $stmt->close();

  if ($last_login_at && $session_ip_address === $ip_address) {
    $current_time = new DateTime();
    $last_login_time = new DateTime($last_login_at);
    $interval = $current_time->diff($last_login_time);
    if ($interval->days >= 3) {
      session_unset();
      session_destroy();
      header("Location: /login/");
      exit();
    } else {
      $stmt = $mysqli->prepare("UPDATE users_session SET last_login_at = NOW() WHERE session_id = ?");
      if ($stmt === false) {
        die('Prepare statement failed: ' . $mysqli->error);
      }
      $stmt->bind_param("s", $session_id);
      $stmt->execute();
      $stmt->close();
    }
  } else {
    session_unset();
    session_destroy();
    header("Location: /login/");
    exit();
  }
} else {
  header("Location: /login/");
  exit();
}

// 通知取得と更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
  $userId = $_SESSION['user_id'];
  $notificationId = intval($_POST['notification_id']);

  $stmt = $mysqli->prepare("SELECT notifications FROM users WHERE id = ?");
  if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($notifications);
    $stmt->fetch();
    $stmt->close();

    $notificationsArray = json_decode($notifications, true);
    if (!is_array($notificationsArray)) {
      $notificationsArray = [];
    }
    if (!in_array($notificationId, $notificationsArray)) {
      $notificationsArray[] = $notificationId;
    }
    $newNotifications = json_encode($notificationsArray);

    $stmt_update = $mysqli->prepare("UPDATE users SET notifications = ? WHERE id = ?");
    if ($stmt_update) {
      $stmt_update->bind_param("si", $newNotifications, $userId);
      $stmt_update->execute();
      $stmt_update->close();
    }
  }

  $mysqli->close();
  exit();
}


$userId = $_SESSION['user_id'];


// ユーザー名、名前、通知を取得
$query = "SELECT username, name, notifications FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $name, $notifications);
$stmt->fetch();
$stmt->close();

$mysqli->close();

$confirmedNotifications = json_decode($notifications, true);
if (!is_array($confirmedNotifications)) {
  $confirmedNotifications = [];
}

$hasNotifications = false;
?>

<!DOCTYPE html>
<!--

 _______                           ______ _       _
|__   __|                         |___  /(_)     | |
   | |     ___   __ _  _ __ ___      / /  _  ___ | |_  _   _
   | |    / _ \ / _` || '_ ` _ \    / /  | |/ __|| __|| | | |
   | |   |  __/| (_| || | | | | |  / /__ | |\__ \| |_ | |_| |
   |_|    \___| \__,_||_| |_| |_| /_____||_||___/ \__| \__, |
                                                        __/ |
                                                       |___/

 We are TeamZisty!
 If you are watching this, why don't you join our team?
 https://discord.gg/6BPfVm6cST

-->
<html lang="ja">

<head>
  <meta charset="utf-8" />
  <title>Account｜Zisty</title>
  <meta name="description" content="Zisty Accounts is a service that allows you to easily integrate with Zisty's services. Why not give it a try?">
  <meta name="copyright" content="Copyright &copy; 2024 Zisty. All rights reserved." />
  <meta property="og:title" content="Zisty Account" />
  <meta property="og:site_name" content="accounts.zisty.net">
  <meta property="og:image" content="https://accounts.zisty.net/images/header.jpg">
  <meta property="og:image:alt" content="バナー画像">
  <meta property="og:locale" content="ja_JP" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Zisty Account" />
  <meta name="twitter:description" content="Zisty Accounts is a service that allows you to easily integrate with Zisty's services. Why not give it a try?">
  <meta name="twitter:image:src" content />
  <meta name="twitter:site" content="accounts.zisty.net" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.png">
  <link rel="stylesheet" href="https://zisty.net/icon.css">
  <link rel="stylesheet" href="https://zisty.net/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .hello {
      margin-top: 0px;
    }

    .boxbox {
      width: 200px;
      height: 200px;
      margin: 10px;
      padding: 20px;
      background-color: #d3d3d37e;
      color: #d6d6d6;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border-radius: 10px 10px 10px 10px;
      transition: transform 0.3s;
    }

    .boxbox:hover {
      transform: scale(1.05);
    }

    .icon {
      font-size: 36px;
    }

    .bold-text {
      font-weight: bold;
      font-size: 20px;
    }

    .normal-text {
      font-size: 16px;
    }

    .container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      margin: 0 auto;
      font-size: 18px;
    }

    .item {
      flex: 0 1 calc(50% - 10px);
      margin-bottom: 20px;
      margin-top: 20px;
      padding: 20px;
      box-sizing: border-box;
      transition: transform 0.3s;
    }

    .item:hover {
      transform: scale(1.05);
    }

    .item .fa-solid {
      color: rgb(255, 255, 255);
    }

    @media screen and (max-width: 600px) {
      .item {
        flex: 0 1 100%;
      }
    }

    .container1 {
      max-width: 600px;
      margin: 50px auto;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
    }

    .icon-text {
      display: flex;
      align-items: center;
    }

    .icon {
      font-size: 24px;
      margin-right: 10px;
      color: #007bff;
    }

    .text {
      font-size: 18px;
      color: #ffffff;
    }

    .box {
      background-color: #582424c4;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 1px;
      max-width: 100%;
    }

    .box p {
      color: #e0e0e0;
      font-size: 20px;
      margin-left: 20px;
      display: flex;
      align-items: center;
    }

    .box p i {
      margin-right: 15px;
      color: #e0e0e0;
      font-size: 24px;
    }

    .user-background {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 70px;
      height: 70px;
      background-color: #2a2a2a;
      border-radius: 50%;
    }

    .icon-background {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 50px;
      height: 50px;
      background-color: #2a2a2a;
      border-radius: 5px;
    }

    .icon-background .fa-solid {
      font-size: 18px;
      color: #979797;
    }

    .notice {
      margin-bottom: 40px;
      margin-top: 30px;
    }

    .notice .close {
      background: none;
      border: none;
      color: #ffffff;
      font-size: 16px;
      cursor: pointer;
    }

    .notice .close:hover {
      color: #8d8d8d;
    }

    .notification {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #242829;
      border-left: 4px solid #007bff;
      color: #fff;
      border-radius: 5px;
      padding: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      font-family: 'Arial', sans-serif;
      position: relative;
      margin-bottom: 10px;
    }

    .notification-icon {
      font-size: 24px;
      margin-right: 10px;
      margin-left: 5px;
      color: #007bff;
    }

    .notification-text {
      flex: 1;
      margin: 0;
      font-size: 14px;
      color: #fff;
    }

    .notification-text a {
      text-decoration: underline;
      color: #fff;
    }

    .question {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #242829;
      border-left: 4px solid #e5ff00;
      color: #fff;
      border-radius: 5px;
      padding: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      font-family: 'Arial', sans-serif;
      position: relative;
      margin-bottom: 10px;
    }

    .question-icon {
      font-size: 24px;
      margin-right: 14px;
      color: #e5ff00;
      margin-left: 5px;
    }

    .question-text {
      flex: 1;
      margin: 0;
      font-size: 14px;
      color: #fff;
    }

    .question-text a {
      text-decoration: underline;
      color: #fff;
    }

    .warning {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #242829;
      border-left: 4px solid #ff0000;
      color: #fff;
      border-radius: 5px;
      padding: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      font-family: 'Arial', sans-serif;
      position: relative;
      margin-bottom: 10px;
    }

    .warning-icon {
      font-size: 24px;
      margin-right: 14px;
      color: #ff0000;
      margin-left: 5px;
    }

    .warning-text {
      flex: 1;
      margin: 0;
      font-size: 14px;
      color: #fff;
    }

    .warning-text a {
      text-decoration: underline;
      color: #fff;
    }
  </style>
</head>

<body>
  <noscript>
    <div class="noscript-overlay">
      <div class="message-box">
        <div class="emoji">⚠️</div>
        <h1>JavaScriptを有効にしてください</h1>
        <p>
          ダッシュボードを使用するにはJavaScriptを有効にしていただく必要があります。<br>
          JavaScriptを有効にして再読み込みをするか、JavaScriptに対応しているブラウザを使用していただく必要があります。
        </p>
      </div>
    </div>
  </noscript>

  <div class="header">
    <div class="left-links">
      <a class="header-a" href="https://zisty.net/"><i class="fa-solid fa-house"></i></a>
      <a class="header-a" href="https://zisty.net/blog/">Blog</a>
      <a class="header-a" href="https://accounts.zisty.net/" target="_blank">Accounts</a>
    </div>
    <div class="right-links">
      <a class="header-b" href=""></a>
      <a class="header-b" href="https://github.com/zisty-h"><i class="fa-solid fa-boxes-stacked"></i></a>
      <a class="header-bar">｜</a>
      <a class="header-b" id="header" onmouseover="showLanguageDropdown()" onmouseout="hideLanguageDropdown()"><i class="fa-solid fa-earth-americas"></i></a>
      <div id="languageDropdown" onmouseover="keepLanguageDropdownVisible()" onmouseout="hideLanguageDropdown()">
        <a onclick="setLanguage('other')">English</a>
        <a onclick="setLanguage('ja')">日本語</a>
      </div>
      <script>
        function setLanguage(language) {
          document.cookie = "Language=" + language + "; path=/; max-age=" + (30 * 24 * 60 * 60);
          window.location.reload();
        }
      </script>
    </div>
  </div>
  <main>
    <div class="hello">
      <div class="notice <?php if ($hasNotifications) echo 'show'; ?>">
        <?php if (!in_array(1, $confirmedNotifications)): ?>
          <div class="notification" data-id="2">
            <span class="notification-icon"><i class="fa-solid fa-circle-info"></i></span>
            <p class="notification-text"><a href="profile/">メールアドレス</a>を追加していざというときに備えよう！</p>
            <button class="close">✖</button>
          </div>
        <?php $hasNotifications = true;
        endif; ?>
        <?php if (!in_array(1, $confirmedNotifications)): ?>
          <div class="notification" data-id="1">
          <span class="notification-icon"><i class="fa-solid fa-circle-info"></i></span>
            <p class="notification-text">早期アクセスありがとうございます！バグなどがあればDiscordコミュニティまでお願いします！ <a href="https://zisty.net/blog/zisty-accounts/">詳細</a></p>
            <button class="close">✖</button>
          </div>
        <?php $hasNotifications = true;
        endif; ?>
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          document.querySelectorAll('.notification .close').forEach(button => {
            button.addEventListener('click', function() {
              const notificationElement = this.closest('.notification');
              const notificationId = notificationElement.getAttribute('data-id');

              fetch('index.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                  'notification_id': notificationId
                })
              }).then(response => {
                if (response.ok) {
                  notificationElement.remove();
                }
              });
            });
          });
        });
      </script>

      <div class="user-background">
        <img src="https://accounts.zisty.net/images/user.png" width="50px">
      </div>
      <p class="what">アカウント</p>
      <p>ようこそ！<?php echo $name; ?>さん</p>
      <p class="mizi">ここではZistyを便利にご利用いただけるよう、プロフィールや連携サービス、プライバシーのカスタマイズなどができます。</p>

      <div>
        <div class="container">
          <div class="item">
            <a style="color: #b4b4b4;" href="profile/">
              <div class="icon-background">
                <i class="fa-solid fa-user"></i>
              </div>
              <h3>プロフィール編集</h3>
              <p>みんなが見ることができる名前やアカウントの復旧に必要なメールアドレスの登録や編集ができます。</p>
            </a>
            </a>
          </div>
          <div class="item">
            <a style="color: #b4b4b4;" href="security/">
              <div class="icon-background">
                <i class="fa-solid fa-shield"></i>
              </div>
              <h3>セキュリティ</h3>
              <p>現在ログインしているデバイスの確認などを行うことができます。</p>
            </a>
            </a>
          </div>
          <div class="item">
            <a style="color: #b4b4b4;" href="link/">
              <div class="icon-background">
                <i class="fa-solid fa-handshake-angle"></i>
              </div>
              <h3>連携サービス</h3>
              <p>連携しているサービスの管理ができます。連携サービスの削除や連携サービスで共有する情報なども管理できます。</p>
            </a>
            </a>
          </div>
          <div class="item">
            <a style="color: #b4b4b4;" href="hazardous/">
              <div class="icon-background" style="background-color: #3f1f1f;">
                <i class="fa-solid fa-bomb"></i>
              </div>
              <h3 style="color: #b85a5a;">危険区域</h3>
              <p>アカウントの削除や一時的な停止などを行える危険な場所です。一様ここにログアウトがあるのでログアウトしたい場合はどうぞこちらへ・・・</p>
            </a>
            </a>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="/Warning.js"></script>
  <script>
    function showLanguageDropdown() {
      document.getElementById("languageDropdown").style.display = "block";
    }

    function hideLanguageDropdown() {
      document.getElementById("languageDropdown").style.display = "none";
    }

    function keepLanguageDropdownVisible() {
      document.getElementById("languageDropdown").style.display = "block";
    }
  </script>
</body>

</html>
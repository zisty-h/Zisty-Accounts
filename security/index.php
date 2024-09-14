<?php
session_start();

// データベースに接続
$mysqli = new mysqli("", "", "", "");

if ($mysqli->connect_errno) {
  echo "データベースの接続に失敗しました: " . $mysqli->connect_error;
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

// ユーザー名を取得
$user_id = $_SESSION["user_id"];
$query = "SELECT username, two_factor_enabled FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $two_factor_enabled);
$stmt->fetch();
$stmt->close();

// デバイス情報を取得
$query = "SELECT ip_address, last_login_at, created_at FROM users_session WHERE username = ? ORDER BY created_at DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($ip_address, $last_login_at, $created_at);
$devices = [];
while ($stmt->fetch()) {
  $devices[] = [
    'ip_address' => $ip_address,
    'last_login_at' => $last_login_at,
    'created_at' => $created_at
  ];
}
$stmt->close();

$mysqli->close();
?>

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

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>Security｜Zisty</title>
  <meta name="keywords" content=" Zisty,ジスティー">
  <meta name="description" content="Zistyはなんとなくで結成されたプログラミングチームです。そしてここは大事な規約が眠っています。">
  <meta name="copyright" content="Copyright &copy; 2023 Zisty. All rights reserved." />
  <meta property="og:title" content="Terms - Zisty" />
  <meta property="og:image" content="https://zisty.net/images/screenshot.785.jpg">
  <meta property="og:image:alt" content="バナー画像">
  <meta property="og:locale" content="ja_JP" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Terms - Zisty" />
  <meta name="twitter:description" content="Zistyはなんとなくで結成されたプログラミングチームです。そしてここは大事な規約が眠っています。">
  <meta name="twitter:image:src" content />
  <meta name="twitter:site" content="https://zisty.net/" />
  <meta name="twitter:creator" content="https://zisty.net/" />
  <meta name="twitter:title" content="Zisty" />
  <meta name="twitter:description" content="https://zisty.net/" />
  <meta name="twitter:image:src" content />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.png">
  <link rel="stylesheet" href="https://zisty.net/icon.css">
  <link rel="stylesheet" href="https://zisty.net/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <style>
    h3 {
      margin-top: 35px;
      margin-bottom: 0px;
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

    .icon-background {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 50px;
      height: 50px;
      background-color: #2a2a2a;
      border-radius: 50%;
      margin-top: 20px;
      margin-bottom: 30px;
      transition: transform 0.3s;
    }

    .icon-background:hover {
      transform: scale(1.10);
    }

    .icon-background .fa-solid {
      font-size: 18px;
      color: #979797;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-top: 10px;
      font-size: 20px;
      margin-bottom: 5px;
    }

    input,
    textarea,
    button {
      margin-top: 5px;
      padding: 10px;
      border: 1px solid #dcdcdc67;
      background-color: #181a1b;
      border-radius: 4px;
      margin-bottom: 15px;
      color: #979797;
    }

    button {
      background-color: #1fdf64;
      color: #181a1b;
      cursor: pointer;
      border: none;
      margin-top: 20px;
      transition: transform 0.3s;
    }

    button:hover {
      transform: scale(1.02);
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 30px;
      bottom: 20px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 23px;
      width: 23px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked+.slider {
      background-color: #2196F3;
    }

    input:checked+.slider:before {
      transform: translateX(29px);
    }


    .twoFAbox {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px;
      border: 1px solid #585858;
      border-radius: 5px;
      margin: 10px auto;
    }

    .twoFAbox i {
      padding: 10px;
      border-radius: 5px;
      margin-right: 15px;
      margin-left: 8px;
      font-size: 45px;
    }

    .content {
      flex-grow: 1;
    }

    .title {
      margin: 0;
      font-size: 18px;
    }

    .details {
      margin: 0;
      margin-top: 3px;
      font-size: 14px;
      color: #666;
      margin-bottom: 5px;
    }

    .settings-btn {
      font-size: 14px;
      width: 100px;
      margin-top: 10px;
      margin-right: 8px;
      margin-left: 15px;
      border: none;
      background-color: #007bff;
      color: white;
      border-radius: 3px;
      cursor: pointer;
    }

    .settings-btn:hover {
      background-color: #0056b3;
    }

    .release-btn {
      font-size: 14px;
      width: 100px;
      margin-top: 10px;
      margin-right: 8px;
      margin-left: 15px;
      border: none;
      background-color: #FF3333;
      color: white;
      border-radius: 3px;
      cursor: pointer;
    }

    .release-btn:hover {
      background-color: #FF3333;
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
      <a class="header-a" href="/"><i class="fa-solid fa-house"></i></a>
      <a class="header-a" href="https://zisty.net/blog/">Blog</a>
      <a class="header-a" href="https://teams.zisty.net/ja/">Safety</a>
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
      <a href="/" class="return">
        <div class="icon-background">
          <i class="fa-solid fa-left-long"></i>
        </div>
      </a>

      <h2>セキュリティ</h2>
      <p>現在ログインしているデバイスの確認などを行うことができます。</p>

      <h3>デバイス</h3>
      <p>現在このアカウントにログインしているアカウントの一覧です。覚えのないエントリーがある場合はすぐにパスワードを変更し、自分を守ることができます。</p>
      <?php if (!empty($devices)) : ?>
        <?php foreach ($devices as $device) : ?>
          <div class="twoFAbox">
            <i class="fa-solid fa-check"></i>
            <div class="content">
              <h2 class="title"><?php echo htmlspecialchars($device['ip_address']); ?></h2>
              <p class="details">作成日：<?php echo htmlspecialchars($device['created_at']); ?>・ラストログイン：<?php echo htmlspecialchars($device['last_login_at']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <p>現在、ログインしているデバイスはありません。</p>
      <?php endif; ?>
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
<?php
session_start();
$userId = $_SESSION['user_id'];

// データベース接続
$mysqli = new mysqli("", "", "", "");

// データベース接続のエラーハンドリング
if ($mysqli->connect_error) {
  die('データベースの接続に失敗しました: ' . $mysqli->connect_error);
  exit();
}

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


$mysqli->close();
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
  <meta charset="UTF-8">
  <title>Hazardous｜Zisty</title>
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
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

    .item-all {
      margin-bottom: 20px;
      margin-top: 20px;
      padding: 20px;
      box-sizing: border-box;
      transition: transform 0.3s;
    }

    .item-all:hover {
      transform: scale(1.03);
    }

    .item .fa-solid {
      color: rgb(255, 255, 255);
    }

    .item-all .fa-solid {
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
      background-color: #3f1f1f;
      border-radius: 50%;
      margin-top: 20px;
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

    /* ダイヤログ */
    .dialog {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .dialog-content {
      background-color: #1f1f1f;
      padding: 40px 20px;
      border-radius: 20px;
      text-align: center;
      max-width: 500px;
      margin: 50px;
    }

    .dialog-content h2 {
      margin-bottom: -5px;
      margin-top: 20px;
      font-size: 30px;
    }

    .dialog-content p {
      font-size: 18px;
      margin-left: 20px;
      margin-right: 20px;
    }

    .dialog-content button {
      border: none;
      border-radius: 8px;
      color: white;
      padding: 7px 20px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease, transform 0.3s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      margin-left: 5px;
      margin-right: 5px;
      margin-top: 20px;
    }

    .dialog-content button:hover {
      transform: translateY(-2px);
    }

    .dialog-content button:active {
      transform: translateY(0);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .yes {
      background-color: #a72828;
    }

    .no {
      background-color: #62636b;
    }

    .yes:hover {
      background-color: #df3838;
    }

    .no:hover {
      background-color: #6d6f78;
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
      <a href="/" class="return">
        <div style="background-color: #2a2a2a;" class="icon-background">
          <i class="fa-solid fa-left-long"></i>
        </div>
      </a>

      <h2 style="color: #b85a5a;">危険区域</h2>
      <p style="margin-bottom: 0;">アカウントの削除や一時的な停止などを行える危険な場所です。ログアウトもここでできます。</p>
      <div>
        <div class="container">
          <div class="item">
            <a style="color: #b4b4b4;" href="deactivate/">
              <div class="icon-background">
                <i class="fa-solid fa-trash"></i>
              </div>
              <h3 style="color: #b85a5a;">アカウントの削除</h3>
              <p>Zistyのアカウントを削除することができます。削除すると即座にデータベースから消え、連携サービスも利用できなくなります。それを理解した上で削除したい方はどうぞ...</p>
            </a>
            </a>
          </div>
          <div class="item">
            <a style="color: #b4b4b4;" href="password/">
              <div class="icon-background" style="background-color: #3f1f1f;">
                <i class="fa-solid fa-key"></i>
              </div>
              <h3 style="color: #b85a5a;">パスワード変更</h3>
              <p>パスワードを変更することができます。パスワードを変更すると全デバイスとの連携は解除され、再度ログインする必要があります。</p>
            </a>
            </a>
          </div>
          <div class="item">
            <a style="color: #b4b4b4;" href="#" id="all-link">
              <div class="icon-background" style="background-color: #3f1f1f;">
                <i class="fa-solid fa-eraser"></i>
              </div>
              <h3 style="color: #b85a5a;">セッションリセット</h3>
              <p>このアカウントにログインしている全てのアカウントからログアウトします。次回からはログインを求められることになります。
              </p>
            </a>
            </a>
          </div>
          <div class="item">
            <a style="color: #b4b4b4;" href="#" id="logout-link">
              <div class="icon-background" style="background-color: #3f1f1f;">
                <i class="fa-solid fa-door-open"></i>
              </div>
              <h3 style="color: #b85a5a;">ログアウト</h3>
              <p>このデバイスからアカウントとの連携を解除し、次回からはログインを求められることになります。
              </p>
            </a>
            </a>
          </div>
  </main>

  <div id="session-dialog" class="dialog">
    <div class="dialog-content">
      <i class="fa-solid fa-door-open"></i>
      <h2>セッションをリセットしますか？</h2>
      <p>セッションをリセットすると現在のデバイスを含めた全てのデバイスをログアウトすることができます。次ログインするまで連携の認証やダッシュボードへのアクセスができなくなってしまいます。</p>
      <button class="no" id="session-no">キャンセル</button>
      <button class="yes" id="session-yes">リセット</button>
    </div>
  </div>

  <div id="confirm-dialog" class="dialog">
    <div class="dialog-content">
      <i class="fa-solid fa-door-open"></i>
      <h2>本当にログアウトしますか？</h2>
      <p>ログアウトすると次ログインするまで連携の認証やダッシュボードへのアクセスができなくなってしまいます。</p>
      <button class="no" id="confirm-no">キャンセル</button>
      <button class="yes" id="confirm-yes">ログアウト</button>
    </div>
  </div>

  <script src="/Warning.js"></script>
  <script>
    document.getElementById('logout-link').addEventListener('click', function(event) {
      event.preventDefault();
      document.getElementById('confirm-dialog').style.display = 'flex';
    });

    document.getElementById('confirm-yes').addEventListener('click', function() {
      window.location.href = '/logout.php';
    });

    document.getElementById('confirm-no').addEventListener('click', function() {
      document.getElementById('confirm-dialog').style.display = 'none';
    });
    document.getElementById('confirm-dialog').addEventListener('click', function(event) {
      if (event.target === this) {
        this.style.display = 'none';
      }
    });

    document.getElementById('all-link').addEventListener('click', function(event) {
      event.preventDefault();
      document.getElementById('session-dialog').style.display = 'flex';
    });

    document.getElementById('session-yes').addEventListener('click', function() {
      window.location.href = '/all_logout.php';
    });

    document.getElementById('session-no').addEventListener('click', function() {
      document.getElementById('session-dialog').style.display = 'none';
    });
    document.getElementById('session-dialog').addEventListener('click', function(event) {
      if (event.target === this) {
        this.style.display = 'none';
      }
    });

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
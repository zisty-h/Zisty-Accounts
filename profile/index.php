<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache"); 
header("Expires: 0"); 

$mysqli = new mysqli("", "", "", "");

if ($mysqli->connect_errno) {
  echo "データベースの接続に失敗しました: " . $mysqli->connect_error;
  exit();
}

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

// データベースからユーザー情報を取得
$query = "SELECT username, email, name, icon_path FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $name, $icon_path);
$stmt->fetch();
$stmt->close();

// アイコンが設定されていない場合の代わりのアイコンのURLを設定
$default_icon = '/@/default.webp';
$icon_path = !empty($icon_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $icon_path) ? $icon_path : $default_icon;

// フォームが送信された場合の処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_message = "認証に失敗しました。";
  } else {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];

    // ユーザー名の検証
    if (empty($new_name)) {
      $error_message = "ユーザー名が入力されていません。";
    } elseif (strlen($new_name) > 50) {
      $error_message = "ユーザー名は50文字以内で入力してください。";
    } else {
      if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['icon']['tmp_name'];
        $file_name = $username . '.webp';
        $destination = $_SERVER['DOCUMENT_ROOT'] . '/@/icons/' . $file_name;

        if ($_FILES['icon']['size'] > 5 * 1024 * 1024) {
          $error_message = "ファイルサイズは5MBを超えてはいけません";
        } else {
          if (file_exists($destination)) {
            unlink($destination);
          }

          // 画像をWebP形式に変換して保存
          $image = imagecreatefromstring(file_get_contents($file_tmp));
          if ($image !== false) {
            if (imagewebp($image, $destination)) {
              imagedestroy($image);

              $icon_path = '/@/icons/' . $file_name;

              // データベースにアイコンの場所を更新
              $update_icon_query = "UPDATE users SET icon_path = ? WHERE id = ?";
              $update_icon_stmt = $mysqli->prepare($update_icon_query);
              $update_icon_stmt->bind_param("si", $icon_path, $user_id);
              if ($update_icon_stmt->execute()) {
                $message = "アイコンが更新されました";
                $success = true;
              } else {
                $error_message = "データベース更新に失敗しました: " . $update_icon_stmt->error;
              }
              $update_icon_stmt->close();
            } else {
              $error_message = "画像の保存に失敗しました";
            }
          } else {
            $error_message = "画像のアップロードに失敗しました";
          }
        }
      }

      // 名前とメールの更新処理
      $update_query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
      $update_stmt = $mysqli->prepare($update_query);
      $update_stmt->bind_param("ssi", $new_name, $new_email, $user_id);
      $update_stmt->execute();
      $update_stmt->close();

      // 更新された情報を取得し直す
      $query = "SELECT username, email, name FROM users WHERE id = ?";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->bind_result($username, $email, $name);
      $stmt->fetch();
      $stmt->close();

      if (!isset($error_message)) {
        $message = "情報が更新されました。";
        $success = true;
      }
    }
  }

  // 結果に応じてリダイレクト
  if (isset($success) && $success) {
    header("Location: ?success=1");
  } else {
    header("Location: ?error=" . urlencode($error_message));
  }
  exit();
}

// CSRFトークンの生成
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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
  <title>Profile｜Zisty</title>
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

    .input-button-group {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .input-button-group input {
      flex-grow: 1;
      margin-bottom: 0;
    }

    .input-button-group button {
      margin-left: 10px;
      margin-top: 0;
      height: 38px;
      width: 50px;
      margin-bottom: -5px;
      border: 1px solid #dcdcdc67;
      background-color: #181a1b;
      color: #979797;
      transition: 0.3s;
    }
    .input-button-group button:hover {
      transform: scale(1.00);
      background-color: #0e0f0f;
    }


    .eyes {
      font-size: 12px;
      margin-bottom: -7px;
      color: #dcdcdc67;
    }

    .eves i {
      margin-right: 4px;
    }

    .icon-container {
      position: relative;
      display: inline-block;
      margin-bottom: 10px;
    }

    .user_icon {
      width: 80px;
      border-radius: 50%;
      box-shadow: 0 0px 25px 0 rgba(58, 58, 58, 0.5);
      transition: opacity 0.3s ease;
      cursor: pointer;
    }

    .icon-container i {
      position: absolute;
      transform: translateX(-100%);
      font-size: 24px;
      color: #ffffff83;
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    .icon-container:hover .user_icon {
      opacity: 0.6;
    }

    .icon-container:hover i {
      opacity: 1;
    }

    /* ダイヤログ's */
    .dialog {
      position: fixed;
      top: -100px;
      left: 50%;
      transform: translateX(-50%);
      padding: 10px 20px;
      background-color: #333333f1;
      box-shadow: 0 0px 25px 0 rgba(58, 58, 58, 0.5);
      color: #fff;
      border-radius: 50px;
      transition: top 0.5s ease-in-out;
      z-index: 9999;
    }

    .dialog.show {
      top: 20px;
    }
  </style>
  <script>
    window.onload = function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('success') === '1') {
        showDialog("✅ 正常に保存されました！");
        const iconElement = document.getElementById('user-icon');
        if (iconElement) {
          const iconUrl = iconElement.src;
          iconElement.src = '';
          iconElement.src = iconUrl + '?v=' + new Date().getTime();
        }
      } else if (urlParams.get('error')) {
        showDialog("❌ " + decodeURIComponent(urlParams.get('error')));
      }
    };

    function showDialog(message) {
      alert(message);
    }
  </script>
</head>

<body>
  <div id="dialog" class="dialog"></div>
  <noscript>
    <div class="noscript-overlay">
      <div class="message-box">
        <div class="emoji">⚠️</div>
        <h1>JavaScriptを有効にしてください</h1>
        <p>ダッシュボードを使用するにはJavaScriptを有効にしていただく必要があります。<br>JavaScriptを有効にして再読み込みをするか、JavaScriptに対応しているブラウザを使用していただく必要があります。</p>
      </div>
    </div>
  </noscript>
  <div class="header">
    <div class="left-links"><a class="header-a" href="https://zisty.net/"><i class="fa-solid fa-house"></i></a><a class="header-a" href="https://zisty.net/blog/">Blog</a><a class="header-a" href="https://accounts.zisty.net/" target="_blank">Accounts</a></div>
    <div class="right-links"><a class="header-b" href=""></a><a class="header-b" href="https://github.com/zisty-h"><i class="fa-solid fa-boxes-stacked"></i></a><a class="header-bar">｜</a><a class="header-b" id="header" onmouseover="showLanguageDropdown()" onmouseout="hideLanguageDropdown()"><i class="fa-solid fa-earth-americas"></i></a>
      <div id="languageDropdown" onmouseover="keepLanguageDropdownVisible()" onmouseout="hideLanguageDropdown()"><a onclick="setLanguage('other')">English</a><a onclick="setLanguage('ja')">日本語</a></div>
      <script>
        function setLanguage(language) {
          document.cookie = "Language=" + language + "; path=/; max-age=" + (30 * 24 * 60 * 60);
          window.location.reload();
        }
      </script>
    </div>
  </div>
  <main>
    <div class="hello"><a href="/" class="return">
        <div class="icon-background"><i class="fa-solid fa-left-long"></i></div>
      </a>
      <h2>プロフィール</h2>
      <form method="post" action="" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="icon-container"><img src="<?php echo htmlspecialchars($icon_path) . '?v=' . time(); ?>" class="user_icon" id="userIcon" onclick="document.getElementById('icon').click();"><input type="file" id="icon" name="icon" style="display: none;" accept="image/*" onchange="previewIcon(event)"><i class="fa-regular fa-pen-to-square"></i></div>
        <script>
          function previewIcon(event) {
            const file = event.target.files[0];
            if (file) {
              if (file.size > 5 * 1024 * 1024) {
                showDialog("📦 容量が5MBを超えています！");
                event.target.value = '';
                return;
              }
              const reader = new FileReader();
              reader.onload = function(e) {
                document.getElementById('userIcon').src = e.target.result;
              };
              reader.readAsDataURL(file);
            }
          }
        </script>
        <label for="username">ユーザー名<br></label>
        <input type="text" id="username" name="username" style="pointer-events: none;" value="<?php echo htmlspecialchars($username); ?>" required><label for="name">名前</label><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        <label for="email">メール</label>
        <div class="input-button-group">
          <input type="" id="" style="pointer-events: none;" name="" value="<?php echo htmlspecialchars($email); ?>">
          <button type="button" onclick="window.open('email/', '_blank')"><i class="fa-solid fa-pen"></i></button>
        </div>
        <p class="eyes"><i class="fa-regular fa-eye"></i> これらの情報は他のユーザーから見られる可能性があります</p><button type="submit">送信</button>
      </form>
  </main>
  <script src="/Warning.js"></script>
  <script src="/showDialog.js"></script>
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
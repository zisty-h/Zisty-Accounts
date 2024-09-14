<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();
$mysqli = new mysqli("", "", "", "");
if ($mysqli->connect_error) {
    $error_message = ('Database connection error: ' . $mysqli->connect_error);
}

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

if (isset($_SESSION["user_id"])) {
    $redirect_url = isset($_GET['auth']) ? filter_var($_GET['auth'], FILTER_SANITIZE_URL) : '/';
    if (filter_var($redirect_url, FILTER_VALIDATE_URL)) {
        header("Location: $redirect_url");
    } else {
        header("Location: /");
    }
    exit();
}

function isValidUsername($username)
{
    return preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

function containsNonEnglishCharacters($str)
{
    return preg_match('/[^a-zA-Z0-9]/', $str);
}

function containsRestrictedWords($str, $restricted_words)
{
    foreach ($restricted_words as $word) {
        if (stripos($str, $word) !== false) {
            return true;
        }
    }
    return false;
}

function generateRandomString($length = 10)
{
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "認証に失敗しました。";
    } else {
        $username = $_POST["username"];
        $password = $_POST["password"];

        if (!preg_match('/^[A-Z0-9_]{3,}$/i', $username)) {
            $error_message = "ユーザー名は3文字以上、かつ1～9、A～Z、アンダースコアのみ使用できます。";
        } else {
            if (!preg_match('/^[A-Z0-9!@#$%^&*()_+-=]{6,}$/i', $password)) {
                $error_message = "パスワードは6文字以上、かつ1～9、A～Z、記号のみ使用できます。";
            }
        }

        if (empty($error_message)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $public_id = generateUUID();
            $private_id = generateUUID();

            $icon_files = [
                '/@/icons/default.webp',
                '/@/icons/default2.webp',
                '/@/icons/default3.webp',
                '/@/icons/default4.webp',
                '/@/icons/default5.webp',
                '/@/icons/default6.webp',
                '/@/icons/default7.webp',
                '/@/icons/default8.webp',
                '/@/icons/default9.webp',
                '/@/icons/default10.webp',
                '/@/icons/default11.webp'
            ];
            $selected_icon = $icon_files[array_rand($icon_files)];

            $check_stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $error_message = "このユーザー名は既に使用されています。";
            } else {
                $check_stmt->close();

                $insert_stmt = $mysqli->prepare("INSERT INTO users (username, name, password, public_id, private_id, last_login, icon_path) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                $insert_stmt->bind_param("ssssss", $username, $username, $hashed_password, $public_id, $private_id, $selected_icon);

                if ($insert_stmt->execute()) {
                    $new_user_id = $mysqli->insert_id;

                    session_regenerate_id(true);
                    $_SESSION["user_id"] = $new_user_id;

                    $expire = time() + (100 * 365 * 24 * 60 * 60);
                    setcookie(session_name(), session_id(), [
                        'expires' => $expire,
                        'path' => '/',
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);

                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $session_id = session_id();

                    // IPアドレスの取得
                    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
                    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        $x_forwarded_for = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                        $ip_address = trim($x_forwarded_for[0]);
                    } else {
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                    }
                    // テーブルにデータを挿入
                    $stmt_session_insert = $mysqli->prepare("INSERT INTO users_session (username, session_id, ip_address, created_at, last_login_at) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_login_at = NOW()");
                    if ($stmt_session_insert) {
                        $stmt_session_insert->bind_param("sss", $username, $session_id, $ip_address);
                        $stmt_session_insert->execute();
                        $stmt_session_insert->close();
                    }

                    $redirect_url = isset($_GET['auth']) ? filter_var($_GET['auth'], FILTER_SANITIZE_URL) : '/';
                    if (filter_var($redirect_url, FILTER_VALIDATE_URL)) {
                        header("Location: $redirect_url");
                    } else {
                        header("Location: /");
                    }
                    exit();
                } else {
                    error_log("アカウント作成中にエラーが発生しました: " . $insert_stmt->error);
                    $error_message = "アカウント作成中にエラーが発生しました。";
                }

                $insert_stmt->close();
            }
        }
    }
    $mysqli->close();

    // CSRFトークンの生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
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
    <title>Register｜Zisty</title>
    <meta name="description" content="Zisty Accounts is a service that allows you to easily integrate with Zisty's services. Why not give it a try?">
    <meta name="copyright" content="Copyright &copy; 2024 Zisty. All rights reserved." />
    <meta property="og:title" content="Zisty Account - Register" />
    <meta property="og:site_name" content="accounts.zisty.net">
    <meta property="og:image" content="https://accounts.zisty.net/images/header.jpg">
    <meta property="og:image:alt" content="バナー画像">
    <meta property="og:locale" content="ja_JP" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Zisty Account - Register" />
    <meta name="twitter:description" content="Zisty Accounts is a service that allows you to easily integrate with Zisty's services. Why not give it a try?">
    <meta name="twitter:image:src" content />
    <meta name="twitter:site" content="accounts.zisty.net" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.png">
    <link rel="stylesheet" href="https://zisty.net/icon.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div id="loading-screen">
        <img src="https://accounts.zisty.net/images/load.gif">
    </div>

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

    <div class="center">
        <form method="post" action="" onsubmit="return validateForm()">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <img src="https://zisty.net/favicon.png" width="50px">
            <h2>はじめまして！</h2>
            <label for="username">ユーザー名</label>
            <div class="TextBox">
                <input type="text" name="username" id="UserName" oninput="validateInput(this)" class="" placeholder=" " required>
                <div class="error-message" id="errorMessage"></div>
            </div>
            <label for="password">パスワード</label>
            <div class="TextBox">
                <input type="password" name="password" id="UserPassword" oninput="validateInputPass(this)" class="" placeholder=" " required>
                <div class="error-message" id="errorMessagePass"></div>
            </div>
            <?php if (!empty($error_message)) : ?>
                <p style="color: red;"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <input type="submit" name="signup" id="signupButton" value="新規作成">
            <p>既にアカウントをお持ちですか？<br><a href="/login/">Login</a></p>

            <p class="tp"><a href="https://zisty.net/terms/" target="_blank">Tos</a>｜<a
                    href="https://zisty.net/privacy/" target="_blank">Privacy</a></p>
        </form>
    </div>


    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="../Warning.js"></script>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                var loadingScreen = document.getElementById('loading-screen');
                loadingScreen.style.animation = 'fadeOut 1s ease forwards';
            }, 1000);
        });
    </script>
</body>

</html>
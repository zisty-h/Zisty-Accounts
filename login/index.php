<?php
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
    $user_id = $_SESSION["user_id"];
    $session_id = session_id();

    $stmt = $mysqli->prepare("SELECT last_login_at FROM users_session WHERE session_id = ? AND username = (SELECT username FROM users WHERE id = ?)");
    if ($stmt === false) {
        $error_message = ('Prepare statement failed: ' . $mysqli->error);
    }
    $stmt->bind_param("si", $session_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($last_login_at);
    $stmt->fetch();
    $stmt->close();

    if ($last_login_at) {
        $current_time = new DateTime();
        $last_login_time = new DateTime($last_login_at);
        $interval = $current_time->diff($last_login_time);

        if ($interval->days < 3) {
            header("Location: /");
            exit();
        } else {
            session_unset();
            session_destroy();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "認証に失敗しました。";
    } else {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        if (!preg_match('/^[A-Z0-9_]{3,}$/i', $username)) {
            $error_message = "ユーザー名は3文字以上、かつ1～9、A～Z、アンダースコアのみ使用できます。";
        }

        if (!preg_match('/^[A-Z0-9!@#$%^&*()_+-=]{6,}$/i', $password)) {
            $error_message = "パスワードは6文字以上、かつ1～9、A～Z、記号のみ使用できます。";
        }

        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        $username = filter_var($username, FILTER_SANITIZE_STRING);

        $stmt = $mysqli->prepare("SELECT id, password, last_session_id FROM users WHERE username = ?");
        if ($stmt === false) {
            $error_message = ('Prepare statement failed: ' . $mysqli->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $hashed_password, $last_session_id);
            $stmt->fetch();

            $stmt_login_date = $mysqli->prepare("SELECT last_login FROM users WHERE id = ?");
            $stmt_login_date->bind_param("i", $id);
            $stmt_login_date->execute();
            $stmt_login_date->bind_result($last_login);
            $stmt_login_date->fetch();
            $stmt_login_date->close();

            $current_time = new DateTime();

            $last_login_time = new DateTime($last_login);

            $interval = $current_time->diff($last_login_time);
            if ($interval->days >= 3) {
                session_regenerate_id(true);
            }

            if (password_verify($password, $hashed_password)) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = $id;

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

                $expire = time() + (100 * 365 * 24 * 60 * 60);
                setcookie(session_name(), session_id(), $expire, "/");

                $redirect_url = isset($_GET['auth']) ? filter_var($_GET['auth'], FILTER_SANITIZE_URL) : '/';
                if (filter_var($redirect_url, FILTER_VALIDATE_URL)) {
                    header("Location: $redirect_url");
                } else {
                    header("Location: /");
                }
                exit();
            } else {
                $error_message = "認証に失敗しました。";
            }
        }
        $stmt->close();
        $mysqli->close();
    }
}

// CSRFトークンの生成
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8" />
    <title>Login｜Zisty</title>
    <meta name="description" content="Zisty Accounts is a service that allows you to easily integrate with Zisty's services. Why not give it a try?">
    <meta name="copyright" content="Copyright &copy; 2024 Zisty. All rights reserved." />
    <meta property="og:title" content="Zisty Account - Login" />
    <meta property="og:site_name" content="accounts.zisty.net">
    <meta property="og:image" content="https://accounts.zisty.net/images/header.jpg">
    <meta property="og:image:alt" content="バナー画像">
    <meta property="og:locale" content="ja_JP" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Zisty Account - Login" />
    <meta name="twitter:description" content="Zisty Accounts is a service that allows you to easily integrate with Zisty's services. Why not give it a try?">
    <meta name="twitter:image:src" content />
    <meta name="twitter:site" content="accounts.zisty.net" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.png">
    <link rel="stylesheet" href="https://zisty.net/icon.css">
    <link rel="stylesheet" href="https://zisty.net/header.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div id="loading-screen"><noscript>
            <p><span style="font-size: 50px;">⚠</span><br>JavaScriptを有効にしてください。<br>Please enable JavaScript.</p>
            <style>
                .load {
                    width: 0;
                }
            </style>
        </noscript><img class="load" src="https://accounts.zisty.net/images/load.gif"></div>
    <div class="header">
    <div class="left-links">
            <a class="header-a" href="https://zisty.net/"><i class="fa-solid fa-house"></i></a>
            <a class="header-a" href="https://zisty.net/blog/">Blog</a>
            <a class="header-a" href="https://accounts.zisty.net/" target="_blank">Accounts</a>
        </div>
        <div class="right-links"><a class="header-b" href=""></a><a class="header-b" href="https://github.com/zisty-h"><i
                    class="fa-solid fa-boxes-stacked"></i></a><a class="header-bar">｜</a><a class="header-b" id="header"
                onmouseover="showLanguageDropdown()" onmouseout="hideLanguageDropdown()"><i
                    class="fa-solid fa-earth-americas"></i></a>
            <div id="languageDropdown" onmouseover="keepLanguageDropdownVisible()" onmouseout="hideLanguageDropdown()"><a
                    onclick="setLanguage('other')">English</a><a onclick="setLanguage('ja')">日本語</a></div>
            <script>
                function setLanguage(language) {
                    document.cookie = "Language=" + language + "; path=/; max-age=" + (30 * 24 * 60 * 60);
                    window.location.reload();
                }
            </script>
        </div>
    </div>
    <div class="center">
        <form method="post" action="" onsubmit="return validateForm()"><input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><img src="https://zisty.net/favicon.png"
                width="50px">
            <h2>おかえりなさい！</h2><label for="username">ユーザー名</label>
            <div class="TextBox"><input type="text" name="username" id="UserName" oninput="convertToLowercase(this)"
                    class="" placeholder="" required></div><label for="password">パスワード</label>
            <div class="TextBox"><input type="password" name="password" id="UserPassword"
                    oninput="convertToLowercase(this)" class="" placeholder="" required></div>
            <?php if (isset($error_message)) : ?>
                <p class="error-message">
                    <?php echo $error_message; ?>
                </p>
            <?php endif; ?><input type="submit" name="signup" value="ログイン">
            <p>アカウントがない方はアカウントを作りましょう！<br><a href="/register">Sign up</a></p>
            <?php if (!empty($error)): ?>
                <p class="error-message">
                    <?php echo $error; ?>
                </p>
            <?php endif; ?>
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
    <script>
        function onClick(e) {
            e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute('6LewliwpAAAAAItLoOTY1QQ_UJpntJZNmWUIiOPM', {
                    action: 'submit'
                }).then(function(token) {});
            });
        }
    </script>
</body>

</html>
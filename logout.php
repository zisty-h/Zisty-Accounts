<?php
session_start();

$_SESSION = array();
session_destroy();
if (isset($_COOKIE['user_token'])) {
    setcookie('user_token', '', time() - 3600, '/');
}
header('Location: /login/');
exit();
?>

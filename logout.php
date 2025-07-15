<?php
session_start();
require_once 'config.php';
require_once 'functions/log.php';

if (isset($_SESSION['user_id'])) {
    log_activity($conn, $_SESSION['user_id'], 'logout', "User $_SESSION[username] logged out");
    session_destroy();
}
header('Location: login.php');
exit;
?>
<?php

if (!function_exists('csrf_input')) {
    function csrf_input() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        echo '<input type="hidden" name="csrf_token" value="'.$_SESSION['csrf_token'].'">';
    }
}

if (!function_exists('csrf_verify_or_die')) {
    function csrf_verify_or_die() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                empty($_POST['csrf_token']) ||
                empty($_SESSION['csrf_token']) ||
                $_POST['csrf_token'] !== $_SESSION['csrf_token']
            ) {
                die("CSRF validation failed!");
            }
        }
    }
}

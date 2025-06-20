<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function isResidente() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'residente';
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header(header: 'Location: /public/login_signup.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /index.php');
        exit();
    }
}

function requireResidente() {
    requireLogin();
    if (!isResidente()) {
        header('Location: /index.php');
        exit();
    }
}

function generarTokenSeguro($longitud = 32) {
    return bin2hex(random_bytes($longitud));
}

function limpiarDatos($data) {
    if (is_array($data)) {
        return array_map('limpiarDatos', $data);
    }
    return htmlspecialchars(trim($data));
}
?>
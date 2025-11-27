<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    // Giriş yoksa login'e gönder
    header("Location: login.php?must_login=1");
    exit;
}

$userId = (int)$_SESSION['user']['id'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Geri dönülecek sayfa (yoksa index.php)
$redirect = $_GET['redirect'] ?? 'index.php';

if ($id <= 0) {
    header("Location: " . $redirect);
    exit;
}

if ($action === 'add') {
    // Aynı kaydı iki kere eklememek için IGNORE kullanıyoruz
    $stmt = $mysqli->prepare("
        INSERT IGNORE INTO favorites (user_id, product_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $userId, $id);
    $stmt->execute();
    $stmt->close();

} elseif ($action === 'remove') {
    $stmt = $mysqli->prepare("
        DELETE FROM favorites
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->bind_param("ii", $userId, $id);
    $stmt->execute();
    $stmt->close();
}

// Geldiği sayfaya geri dön
header("Location: " . $redirect);
exit;

<?php
session_start();

// Kullanıcı bilgisini sil
unset($_SESSION['user']);

// İstersen tüm session'ı yok et
// session_destroy();

header("Location: index.php");
exit;

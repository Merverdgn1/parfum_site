<?php
session_start();
require 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    // Basit kontroller
    if ($name === '' || $email === '' || $pass === '' || $pass2 === '') {
        $errors[] = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta girin.';
    } elseif ($pass !== $pass2) {
        $errors[] = 'Şifreler eşleşmiyor.';
    } elseif (strlen($pass) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalı.';
    }

    if (empty($errors)) {
        // Bu e-posta zaten var mı?
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Bu e-posta ile zaten kayıt olunmuş.';
        } else {
            // Şifreyi hashle
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $stmtInsert = $mysqli->prepare(
                "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)"
            );
            $stmtInsert->bind_param("sss", $name, $email, $hash);
            $ok = $stmtInsert->execute();

            if ($ok) {
                $success = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
            } else {
                $errors[] = 'Kayıt sırasında bir hata oluştu.';
            }

            $stmtInsert->close();
        }

        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Kayıt Ol - Sezer Parfüm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Sezer Parfüm</a>
    </div>
</nav>

<main class="py-5">
    <div class="container" style="max-width: 500px;">
        <h1 class="h3 fw-bold mb-4">Kayıt Ol</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <br>
                <a href="login.php" class="alert-link">Giriş sayfasına git</a>.
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input type="text" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Şifre (Tekrar)</label>
                <input type="password" name="password_confirm" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>

            <p class="mt-3 mb-0 text-center">
                Zaten hesabın var mı? 
                <a href="login.php">Giriş yap</a>
            </p>
        </form>
    </div>
</main>

<footer class="py-4 bg-dark text-light text-center mt-5">
    <div class="container">
        <small>© <?php echo date("Y"); ?> Sezer Parfüm - Tüm hakları saklıdır.</small>
    </div>
</footer>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>
</body>
</html>

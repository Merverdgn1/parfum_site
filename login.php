<?php
session_start();
require 'db.php';

$errors = [];
$info   = '';

// Sepete / satın almaya gitmek için yönlendirdiysek
if (isset($_GET['must_login'])) {
    $info = 'Satın alma işlemi için önce giriş yapmalısınız.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $errors[] = 'E-posta ve şifre gerekli.';
    } else {
        // Kullanıcıyı bul
        $stmt = $mysqli->prepare(
            "SELECT id, name, email, password_hash 
             FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password_hash'])) {
            // Giriş başarılı
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email']
            ];

            header("Location: index.php");
            exit;
        } else {
            $errors[] = 'E-posta veya şifre hatalı.';
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Giriş Yap - Sezer Parfüm</title>
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
        <h1 class="h3 fw-bold mb-4">Giriş Yap</h1>

        <?php if ($info): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($info); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>

            <p class="mt-3 mb-0 text-center">
                Henüz üye değil misin?
                <a href="register.php">Kayıt ol</a>
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

<?php
session_start();
require 'db.php';

// Sepetteki ürün sayısı (navbar için)
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}

// Admin kontrolü
$isAdmin = false;
if (isset($_SESSION['user']) && isset($mysqli)) {
    $uid = (int) $_SESSION['user']['id'];
    $stmtA = $mysqli->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmtA->bind_param("i", $uid);
    $stmtA->execute();
    $resA = $stmtA->get_result()->fetch_assoc();
    $stmtA->close();
    if ($resA && (int)$resA['is_admin'] === 1) {
        $isAdmin = true;
    }
}

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $errorMsg = 'Lütfen ad, e-posta ve mesaj alanlarını doldurunuz.';
    } else {
        $stmt = $mysqli->prepare(
            "INSERT INTO contact_messages (name, email, subject, message)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $successMsg = 'Mesajınız başarıyla iletildi. En kısa sürede dönüş yapacağız.';
        } else {
            $errorMsg = 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
        }

        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>İletişim - Sezer Parfüm</title>
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Ana Sayfa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php#products">Parfümler</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">Hakkımızda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="contact.php">İletişim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        Sepet<?php if ($cartCount > 0) echo ' ('.$cartCount.')'; ?>
                    </a>
                </li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_orders.php">Siparişlerim</a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_products.php">Admin</a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user'])): ?>
                  <li class="nav-item d-flex align-items-center">
    <span class="navbar-text me-2 mb-0">
        Merhaba, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
    </span>
</li>


                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Giriş</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Kayıt Ol</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="py-5">
    <div class="container">
        <h1 class="h3 fw-bold mb-4">İletişim</h1>

        <div class="row g-4">
            <div class="col-md-6">

                <?php if ($successMsg): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errorMsg); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="contact.php">
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konu (opsiyonel)</label>
                        <input type="text" name="subject" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mesajınız</label>
                        <textarea name="message" rows="4" class="form-control" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Gönder
                    </button>
                </form>
            </div>

            <div class="col-md-6">
                <h2 class="h5 fw-bold mb-3">İletişim Bilgileri</h2>
                <p class="mb-1"><strong>E-posta:</strong> destek@merveparfum.test</p>
                <p class="mb-1"><strong>Instagram:</strong> @merveparfum</p>
                <p class="text-muted mt-2">
                    
                </p>
            </div>
        </div>
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

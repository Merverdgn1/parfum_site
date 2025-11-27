<?php
session_start();
require 'db.php';

$errors  = [];
$success = '';

// Sepetteki ürün sayısı (navbar için)
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $errors[] = 'Lütfen ad, e-posta ve mesaj alanlarını doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare(
            "INSERT INTO contact_messages (name, email, subject, message)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $success = 'Mesajınız başarıyla gönderildi. En kısa sürede dönüş yapılacaktır.';
            // Formu temizleyelim
            $_POST = [];
        } else {
            $errors[] = 'Mesaj kaydedilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
        }
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
    <div class="container" style="max-width: 900px;">
        <h1 class="h3 fw-bold mb-4">İletişim</h1>

        <div class="row g-4">
            <div class="col-md-6">
                <h2 class="h5 fw-bold mb-3">Bize Ulaşın</h2>

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
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input 
                            type="text" 
                            name="name" 
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konu (opsiyonel)</label>
                        <input 
                            type="text" 
                            name="subject" 
                            class="form-control"
                            value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mesajınız</label>
                        <textarea 
                            name="message" 
                            class="form-control" 
                            rows="5"
                        ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Mesaj Gönder
                    </button>
                </form>
            </div>

            <div class="col-md-6">
                <h2 class="h5 fw-bold mb-3">İletişim Bilgileri</h2>
                <p>
                    Her türlü soru, öneri veya iş birliği talebi için bize bu formdan veya aşağıdaki kanallardan ulaşabilirsiniz.
                </p>
                <ul class="list-unstyled">
                    <li><strong>E-posta:</strong> info@merveparfum.com (örnek)</li>
                    <li><strong>Çalışma Saatleri:</strong> Hafta içi 09:00 - 18:00</li>
                </ul>

                <p class="mt-3 text-muted">
                    * Bu proje şu an geliştirme aşamasında örnek bir e-ticaret uygulamasıdır.
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

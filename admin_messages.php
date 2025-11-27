<?php
session_start();
require 'db.php';

// Giriş yapılmamışsa
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Bu kullanıcı admin mi?
$isAdmin = false;
if (isset($mysqli)) {
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

// Admin değilse ana sayfaya yolla
if (!$isAdmin) {
    header("Location: index.php");
    exit;
}

// Sepet sayısı (navbar için)
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}

// Mesajları çek
$result = $mysqli->query(
    "SELECT id, name, email, subject, message, created_at
     FROM contact_messages
     ORDER BY created_at DESC"
);
$messages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>İletişim Mesajları - Admin - Sezer Parfüm</title>
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
                    <a class="nav-link" href="contact.php">İletişim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        Sepet<?php if ($cartCount > 0) echo ' ('.$cartCount.')'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin_messages.php">Admin</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_orders.php">Siparişlerim</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item d-flex align-items-center">
                    <span class="navbar-text me-2 mb-0">
                        Merhaba, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Çıkış</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold mb-0">İletişim Mesajları (Admin)</h1>

            <!-- Admin içinde küçük menü -->
            <div class="d-flex gap-2">
                <a href="admin_products.php" class="btn btn-outline-secondary btn-sm">
                    Ürünleri Yönet
                </a>
                <a href="admin_messages.php" class="btn btn-primary btn-sm">
                    Mesajlar
                </a>
            </div>
        </div>

        <?php if (empty($messages)): ?>
            <div class="alert alert-info">
                Henüz iletilmiş bir iletişim mesajı bulunmuyor.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Konu</th>
                            <th>Mesaj</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $m): ?>
                            <tr>
                                <td><?php echo $m['id']; ?></td>
                                <td><?php echo htmlspecialchars($m['name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>">
                                        <?php echo htmlspecialchars($m['email']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($m['subject'] ?? ''); ?></td>
                                <td style="max-width: 350px;">
                                    <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                </td>
                                <td><?php echo $m['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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

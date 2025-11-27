<?php
session_start();
require 'db.php';

// Giriş yapılmamışsa login'e gönder
if (!isset($_SESSION['user'])) {
    header("Location: login.php?must_login=1");
    exit;
}

$userId = (int) $_SESSION['user']['id'];

// Kullanıcının siparişlerini çek
$stmt = $mysqli->prepare(
    "SELECT 
        o.id, 
        o.total_amount, 
        o.created_at,
        o.status,
        o.payment_method,
        o.shipping_company,
        COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY 
        o.id, 
        o.total_amount, 
        o.created_at,
        o.status,
        o.payment_method,
        o.shipping_company
     ORDER BY o.created_at DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sepetteki ürün sayısı (navbar için)
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}

// Admin kontrolü (navbar için)
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
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Siparişlerim - Sezer Parfüm</title>
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
                    <a class="nav-link active" href="my_orders.php">Siparişlerim</a>
                </li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_favorites.php">Favorilerim</a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_products.php">Admin</a>
                    </li>
                <?php endif; ?>

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
        <h1 class="h3 fw-bold mb-4">Siparişlerim</h1>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                Henüz hiç siparişiniz yok. 
                <a href="index.php#products" class="alert-link">Alışverişe başlayın</a>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Tarih</th>
                            <th>Ürün Sayısı</th>
                            <th>Toplam Tutar</th>
                            <th>Durum</th>
                            <th>Detay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?php echo (int)$o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                                <td><?php echo (int)$o['item_count']; ?></td>
                                <td>₺<?php echo number_format($o['total_amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php
                                        $status = $o['status'] ?? 'Bilinmiyor';
                                        $badgeClass = 'bg-secondary';
                                        if ($status === 'Hazırlanıyor')      $badgeClass = 'bg-warning';
                                        elseif ($status === 'Kargoya Verildi') $badgeClass = 'bg-info';
                                        elseif ($status === 'Tamamlandı')    $badgeClass = 'bg-success';
                                        elseif ($status === 'İptal Edildi')  $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo (int)$o['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Detay
                                    </a>
                                </td>
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

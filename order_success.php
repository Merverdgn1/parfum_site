<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId  = (int) $_SESSION['user']['id'];
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

// Sipariş bilgisi
$stmt = $mysqli->prepare(
    "SELECT id, total_amount, created_at, user_id 
     FROM orders WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "Bu siparişi görüntüleme yetkiniz yok veya sipariş bulunamadı.";
    exit;
}

// Sipariş ürünleri
$stmtItems = $mysqli->prepare(
    "SELECT oi.quantity, oi.unit_price, p.name
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?"
);
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();

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
    <title>Sipariş Tamamlandı - Sezer Parfüm</title>
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
                    <a class="nav-link" href="my_orders.php">Siparişlerim</a>
                </li>
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
        <h1 class="h3 fw-bold mb-3">Siparişiniz Alındı 🎉</h1>

        <p>
            Sipariş numaranız: <strong>#<?php echo $order['id']; ?></strong><br>
            Toplam tutar: <strong>₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></strong><br>
            Tarih: <?php echo $order['created_at']; ?>
        </p>

        <h2 class="h5 fw-bold mt-4 mb-3">Sipariş Detayı</h2>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Adet</th>
                        <th>Birim Fiyat</th>
                        <th>Ara Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($it['name']); ?></td>
                            <td><?php echo $it['quantity']; ?></td>
                            <td>₺<?php echo number_format($it['unit_price'], 2, ',', '.'); ?></td>
                            <td>₺<?php echo number_format($it['unit_price'] * $it['quantity'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php#products" class="btn btn-primary mt-3">Alışverişe devam et</a>
        <a href="my_orders.php" class="btn btn-outline-secondary mt-3">Siparişlerimi Gör</a>
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

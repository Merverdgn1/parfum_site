<?php
session_start();
require 'db.php';

// Giriş kontrolü
if (!isset($_SESSION['user'])) {
    header("Location: login.php?must_login=1");
    exit;
}

$userId   = (int)$_SESSION['user']['id'];
$orderId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header("Location: my_orders.php");
    exit;
}

// Sipariş gerçekten bu kullanıcıya mı ait kontrol et
$stmt = $mysqli->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    // Başka birine ait ya da yok
    header("Location: my_orders.php");
    exit;
}

// Sepet sayısı (navbar)
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}

// Admin kontrolü (navbar)
$isAdmin = false;
if (isset($mysqli)) {
    $uid = $userId;
    $stmtA = $mysqli->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmtA->bind_param("i", $uid);
    $stmtA->execute();
    $resA = $stmtA->get_result()->fetch_assoc();
    $stmtA->close();
    if ($resA && (int)$resA['is_admin'] === 1) {
        $isAdmin = true;
    }
}

// Sipariş kalemleri (order_items + products)
$stmtItems = $mysqli->prepare("
    SELECT oi.quantity, oi.unit_price, p.name, p.image
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$resItems = $stmtItems->get_result();
$items = $resItems->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Sipariş Detayı #<?php echo (int)$order['id']; ?> - Sezer Parfüm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<!-- NAVBAR -->
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
                    <a class="nav-link active" href="my_orders.php">Siparişlerim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_favorites.php">Favorilerim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        Sepet<?php if ($cartCount > 0) echo ' ('.$cartCount.')'; ?>
                    </a>
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

        <div class="d-flex mb-4">
            <a href="my_orders.php" class="btn btn-outline-secondary btn-sm">
                <span class="me-1">&larr;</span> Siparişlerime geri dön
            </a>
        </div>

        <h1 class="h4 fw-bold mb-3">
            Sipariş #<?php echo (int)$order['id']; ?>
        </h1>

        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="h6 fw-bold">Sipariş Bilgileri</h2>
                <p class="mb-1"><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                <p class="mb-1"><strong>Adres:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                <p class="mb-1">
                    <strong>Şehir / İlçe:</strong> 
                    <?php echo htmlspecialchars($order['city']); ?> / 
                    <?php echo htmlspecialchars($order['district']); ?>
                </p>
                <p class="mb-1"><strong>Telefon:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            </div>
            <div class="col-md-6">
                <h2 class="h6 fw-bold">Ödeme & Kargo</h2>
                <p class="mb-1"><strong>Ödeme Yöntemi:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                <p class="mb-1"><strong>Kargo Firması:</strong> <?php echo htmlspecialchars($order['shipping_company']); ?></p>
                <p class="mb-1">
                    <strong>Durum:</strong> 
                    <?php
                        $status = $order['status'] ?? 'Bilinmiyor';
                        $badgeClass = 'bg-secondary';
                        if ($status === 'Hazırlanıyor')      $badgeClass = 'bg-warning';
                        elseif ($status === 'Kargoya Verildi') $badgeClass = 'bg-info';
                        elseif ($status === 'Tamamlandı')    $badgeClass = 'bg-success';
                        elseif ($status === 'İptal Edildi')  $badgeClass = 'bg-danger';
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </p>
                <p class="mb-1">
                    <strong>Toplam Tutar:</strong> 
                    ₺<?php echo number_format($order['total_amount'], 2, ',', '.'); ?>
                </p>
            </div>
        </div>

        <h2 class="h6 fw-bold mb-3">Sipariş Ürünleri</h2>

        <?php if (empty($items)): ?>
            <div class="alert alert-warning">
                Bu sipariş için ürün kaydı bulunamadı.
            </div>
        <?php else: ?>
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
                            <?php
                                $imgFile = trim($it['image'] ?? '');
                                if ($imgFile === '') {
                                    $imgPath = 'assets/img/placeholder.jpg';
                                } elseif (preg_match('#^https?://#i', $imgFile)) {
                                    $imgPath = $imgFile;
                                } else {
                                    $imgPath = 'assets/img/' . $imgFile;
                                }
                                $subtotal = $it['quantity'] * $it['unit_price'];
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($imgPath); ?>"
                                             alt="<?php echo htmlspecialchars($it['name']); ?>"
                                             style="width:50px;height:50px;object-fit:cover;"
                                             class="me-2">
                                        <?php echo htmlspecialchars($it['name']); ?>
                                    </div>
                                </td>
                                <td><?php echo (int)$it['quantity']; ?></td>
                                <td>₺<?php echo number_format($it['unit_price'], 2, ',', '.'); ?></td>
                                <td>₺<?php echo number_format($subtotal, 2, ',', '.'); ?></td>
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

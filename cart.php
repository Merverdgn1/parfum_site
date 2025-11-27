<?php
session_start();
require 'db.php';

// Sepet dizisini başlat
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? null;
$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ÜRÜN EKLEME
if ($action === 'add' && $id > 0) {

    // Ürünü veritabanından çek
    $productToAdd = getProductById($id);

    if ($productToAdd) {
        // Sepette varsa adedi artır, yoksa yeni ekle
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty']++;
        } else {
            $_SESSION['cart'][$id] = [
                'id'    => $productToAdd['id'],
                'name'  => $productToAdd['name'],
                'price' => $productToAdd['price'],
                'qty'   => 1
            ];
        }
    }

    header("Location: cart.php");
    exit;
}

// ÜRÜN KALDIRMA
if ($action === 'remove' && $id > 0) {
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: cart.php");
    exit;
}

// SEPETİ TEMİZLE
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

/*
 * DİKKAT:
 * Daha önce burada $action === 'checkout' ile
 * siparişi direkt orders / order_items tablosuna kaydeden
 * bir blok vardı. Artık SİLİNDİ.
 * Sipariş oluşturma işlemi sadece checkout.php içinde yapılacak.
 */

// Sayfa için sepet verilerini hazırla
$cartItems = $_SESSION['cart'];
$total     = 0;
$cartCount = 0;

foreach ($cartItems as $item) {
    $total     += $item['price'] * $item['qty'];
    $cartCount += $item['qty'];
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
    <title>Sepetim - Sezer Parfüm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
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
            <!-- SOL TARAF -->
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
                    <a class="nav-link active" href="cart.php">
                        Sepet<?php if ($cartCount > 0) echo ' ('.$cartCount.')'; ?>
                    </a>
                </li>

                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_orders.php">Siparişlerim</a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- SAĞ TARAF -->
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_products.php">Admin</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_messages.php">Mesajlar</a>
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
        <h1 class="h3 fw-bold mb-4">Sepetim</h1>

        <?php if (empty($cartItems)): ?>
            <div class="alert alert-info">
                Sepetiniz boş. <a href="index.php#products" class="alert-link">Alışverişe devam et</a>.
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
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['qty']; ?></td>
                                <td>₺<?php echo number_format($item['price'], 2, ',', '.'); ?></td>
                                <td>₺<?php echo number_format($item['price'] * $item['qty'], 2, ',', '.'); ?></td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger">
                                        Kaldır
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Toplam:</th>
                            <th colspan="2">
                                ₺<?php echo number_format($total, 2, ',', '.'); ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a href="index.php#products" class="btn btn-outline-secondary">
                    Alışverişe Devam Et
                </a>
                <div class="d-flex gap-2">
                    <a href="cart.php?action=clear" class="btn btn-outline-danger">
                        Sepeti Temizle
                    </a>
                    <!-- ÖNEMLİ: Artık direkt checkout action yok, checkout.php'ye gidiyoruz -->
                    <a href="checkout.php" class="btn btn-success">
                        Satın Al
                    </a>
                </div>
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

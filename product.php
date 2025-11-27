<?php
session_start();
require 'db.php';

// URL'den id parametresini al
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ürünü DB'den çek
$selectedProduct = getProductById($productId);

// Ürün bulunamazsa
if (!$selectedProduct) {
    echo "Ürün bulunamadı.";
    exit;
}

// Sepet sayısı
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
    <title><?php echo htmlspecialchars($selectedProduct['name']); ?> - Sezer Parfüm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
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

                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_orders.php">Siparişlerim</a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user'])): ?>
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

<!-- ÜRÜN DETAY -->
<main class="py-5">
    <div class="container">
        <a href="index.php#products" class="btn btn-link mb-3">&laquo; Geri dön</a>

        <div class="row g-4">
            <div class="col-md-5">
                <img 
                    src="<?php echo $selectedProduct['image']; ?>" 
                    alt="<?php echo htmlspecialchars($selectedProduct['name']); ?>" 
                    class="img-fluid rounded shadow-sm"
                >
            </div>

            <div class="col-md-7">
                <h1 class="h3 fw-bold mb-3">
                    <?php echo htmlspecialchars($selectedProduct['name']); ?>
                </h1>

                <?php if (!empty($selectedProduct['category'])): ?>
                    <span class="badge bg-secondary mb-3">
                        <?php echo htmlspecialchars($selectedProduct['category']); ?>
                    </span>
                <?php endif; ?>

                <p class="lead text-muted">
                    <?php echo htmlspecialchars($selectedProduct['description']); ?>
                </p>

                <p class="fs-4 fw-bold mt-3">
                    ₺<?php echo number_format($selectedProduct['price'], 2, ',', '.'); ?>
                </p>

                <div class="d-flex gap-2 mt-4">
                    <a href="cart.php?action=add&id=<?php echo $selectedProduct['id']; ?>" 
                       class="btn btn-primary btn-lg">
                        Sepete Ekle
                    </a>
                    <button class="btn btn-outline-secondary btn-lg" disabled>
                        Favorilere Ekle (şimdilik çalışmıyor)
                    </button>
                </div>

                <hr class="my-4">

                <h2 class="h5 fw-bold mb-2">Koku Notları (örnek)</h2>
                <ul>
                    <li>Üst notalar: Bergamot, mandalina</li>
                    <li>Orta notalar: Yasemin, gül</li>
                    <li>Alt notalar: Vanilya, misk</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<!-- FOOTER -->
<footer class="py-4 bg-dark text-light text-center mt-5">
    <div class="container">
        <small>© <?php echo date("Y"); ?> Sezer Parfüm - Tüm hakları saklıdır.</small>
    </div>
</footer>

<!-- Bootstrap JS -->
<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>
</body>
</html>

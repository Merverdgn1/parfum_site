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

/* ===== FAVORİLER İÇİN EK KISIM ===== */

// Şu anki URL (favoriden geri dönebilmek için)
$currentUrl = $_SERVER['REQUEST_URI'] ?? 'index.php';

// Kullanıcı giriş yaptıysa, bu ürün favorilerde mi kontrol et
$inFavorites = false;
if (isset($_SESSION['user']) && isset($mysqli)) {
    $userId = (int)$_SESSION['user']['id'];

    $stmtFav = $mysqli->prepare("
        SELECT 1 FROM favorites
        WHERE user_id = ? AND product_id = ?
        LIMIT 1
    ");
    $stmtFav->bind_param("ii", $userId, $productId);
    $stmtFav->execute();
    $resFav = $stmtFav->get_result()->fetch_assoc();
    $stmtFav->close();

    if ($resFav) {
        $inFavorites = true;
    }
}

/* Resim yolu (index.php ile aynı mantıkta olsun) */
$imageFile = trim($selectedProduct['image'] ?? '');
if ($imageFile === '') {
    $imagePath = 'assets/img/placeholder.jpg';
} elseif (preg_match('#^https?://#i', $imageFile)) {
    $imagePath = $imageFile;
} else {
    $imagePath = 'assets/img/' . $imageFile;
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
                    <li class="nav-item">
                        <a class="nav-link" href="my_favorites.php">Favorilerim</a>
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

        <!-- Şık geri butonu -->
        <div class="d-flex mb-4">
            <a href="index.php#products" class="btn btn-outline-secondary btn-sm">
                <span class="me-1">&larr;</span> Ürünlere geri dön
            </a>
        </div>

        <div class="row g-4">
            <div class="col-md-5">
                <img 
                    src="<?php echo htmlspecialchars($imagePath); ?>" 
                    alt="<?php echo htmlspecialchars($selectedProduct['name']); ?>" 
                    class="img-fluid rounded shadow-sm"
                    onerror="this.onerror=null; this.src='assets/img/placeholder.jpg';"
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
                    <!-- Sepete ekle -->
                    <a href="cart.php?action=add&id=<?php echo (int)$selectedProduct['id']; ?>" 
                       class="btn btn-primary btn-lg">
                        Sepete Ekle
                    </a>

                    <!-- Favori butonu -->
                    <?php if (isset($_SESSION['user'])): ?>
                        <?php if ($inFavorites): ?>
                            <a href="favorites.php?action=remove&id=<?php echo (int)$selectedProduct['id']; ?>&redirect=<?php echo urlencode($currentUrl); ?>"
                               class="btn btn-outline-danger btn-lg">
                                Favorilerden Çıkar
                            </a>
                        <?php else: ?>
                            <a href="favorites.php?action=add&id=<?php echo (int)$selectedProduct['id']; ?>&redirect=<?php echo urlencode($currentUrl); ?>"
                               class="btn btn-outline-warning btn-lg">
                                ★ Favorilere Ekle
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php?must_login=1"
                           class="btn btn-outline-warning btn-lg">
                            ★ Favorilere Ekle (Giriş Yap)
                        </a>
                    <?php endif; ?>
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

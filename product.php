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

/* ===== YORUM & PUAN KISMI ===== */

$reviewErrors = [];
$userReview   = null;
$avgRating    = null;
$reviewCount  = 0;
$canReview    = false; // Ürünü gerçekten almış mı?

if (isset($_SESSION['user']) && isset($mysqli)) {
    $userId = (int)$_SESSION['user']['id'];

    // 1) Bu kullanıcı bu ürünü "Tamamlandı" durumlu siparişlerinde almış mı?
    $stmtCR = $mysqli->prepare("
        SELECT 1
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ?
          AND oi.product_id = ?
          AND o.status = 'Tamamlandı'
        LIMIT 1
    ");
    $stmtCR->bind_param("ii", $userId, $productId);
    $stmtCR->execute();
    $hasCompletedOrder = $stmtCR->get_result()->fetch_assoc();
    $stmtCR->close();

    if ($hasCompletedOrder) {
        $canReview = true;
    }

    // 2) Yorum formu POST edildi mi?
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {

        if (!$canReview) {
            // Bu kullanıcı ürünü almamışsa / tamamlanmış siparişi yoksa
            $reviewErrors[] = 'Bu ürün için yorum yapabilmek için ürünü satın alıp teslim almış olmanız gerekir.';
        } else {
            $rating  = (int)($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            if ($rating < 1 || $rating > 5) {
                $reviewErrors[] = 'Lütfen 1 ile 5 arasında bir puan seçin.';
            }
            if ($comment === '') {
                $reviewErrors[] = 'Lütfen yorum alanını boş bırakmayın.';
            }

            if (empty($reviewErrors)) {
                // Aynı kullanıcı aynı ürüne bir kez yorum yazsın: INSERT ... ON DUPLICATE KEY UPDATE
                $stmtR = $mysqli->prepare("
                    INSERT INTO product_reviews (user_id, product_id, rating, comment, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        rating = VALUES(rating),
                        comment = VALUES(comment),
                        created_at = VALUES(created_at)
                ");
                $stmtR->bind_param("iiis", $userId, $productId, $rating, $comment);
                $stmtR->execute();
                $stmtR->close();

                // Form tekrar gönderilmesin diye redirect
                header("Location: product.php?id=" . $productId . "#reviews");
                exit;
            }
        }
    }

    // 3) Kullanıcının mevcut yorumu (varsa)
    $stmtUR = $mysqli->prepare("
        SELECT rating, comment 
        FROM product_reviews 
        WHERE user_id = ? AND product_id = ?
        LIMIT 1
    ");
    $stmtUR->bind_param("ii", $userId, $productId);
    $stmtUR->execute();
    $userReview = $stmtUR->get_result()->fetch_assoc();
    $stmtUR->close();
}

// Ortalama puan & yorum sayısı
$stmtAvg = $mysqli->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count
    FROM product_reviews
    WHERE product_id = ?
");
$stmtAvg->bind_param("i", $productId);
$stmtAvg->execute();
$ratingRow = $stmtAvg->get_result()->fetch_assoc();
$stmtAvg->close();

if ($ratingRow && $ratingRow['review_count'] > 0) {
    $avgRating   = round((float)$ratingRow['avg_rating'], 1);
    $reviewCount = (int)$ratingRow['review_count'];
}

// Yorum listesi
$stmtList = $mysqli->prepare("
    SELECT pr.rating, pr.comment, pr.created_at, u.name
    FROM product_reviews pr
    JOIN users u ON u.id = pr.user_id
    WHERE pr.product_id = ?
    ORDER BY pr.created_at DESC
");
$stmtList->bind_param("i", $productId);
$stmtList->execute();
$reviews = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtList->close();

/* ===== FAVORİLER İÇİN KISIM ===== */

// Şu anki URL (favoriden geri dönebilmek için)
$currentUrl = $_SERVER['REQUEST_URI'] ?? 'index.php';

// Kullanıcı giriş yaptıysa, bu ürün favorilerde mi kontrol et
$inFavorites = false;
if (isset($_SESSION['user']) && isset($mysqli)) {
    $userIdFav = (int)$_SESSION['user']['id'];

    $stmtFav = $mysqli->prepare("
        SELECT 1 FROM favorites
        WHERE user_id = ? AND product_id = ?
        LIMIT 1
    ");
    $stmtFav->bind_param("ii", $userIdFav, $productId);
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

        <!-- Geri butonu -->
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
                <h1 class="h3 fw-bold mb-2">
                    <?php echo htmlspecialchars($selectedProduct['name']); ?>
                </h1>

                <?php if (!empty($selectedProduct['category'])): ?>
                    <span class="badge bg-secondary mb-2">
                        <?php echo htmlspecialchars($selectedProduct['category']); ?>
                    </span>
                <?php endif; ?>

                <!-- Ortalama puan -->
                <?php if ($avgRating !== null): ?>
                    <p class="mb-2">
                        <strong>Puan:</strong> 
                        <?php echo $avgRating; ?>/5 
                        (<?php echo $reviewCount; ?> yorum)
                    </p>
                <?php else: ?>
                    <p class="mb-2 text-muted">Bu ürün için henüz yorum yapılmamış.</p>
                <?php endif; ?>

                <p class="lead text-muted mt-2">
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

                <!-- YORUM & PUAN BÖLÜMÜ -->
                <hr class="my-4">

                <h2 id="reviews" class="h5 fw-bold mb-3">Ürün Yorumları</h2>

                <!-- Yorum hataları -->
                <?php if (!empty($reviewErrors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($reviewErrors as $e): ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Yorum formu / uyarı -->
                <?php if (!isset($_SESSION['user'])): ?>

                    <div class="alert alert-warning">
                        Yorum yapabilmek için önce 
                        <a href="login.php?must_login=1" class="alert-link">giriş yapmalısınız</a>.
                    </div>

                <?php else: ?>

                    <?php if ($canReview): ?>
                        <?php
                            // Formda gösterilecek değerler
                            $formRating  = $_POST['rating']  ?? ($userReview['rating']  ?? '');
                            $formComment = $_POST['comment'] ?? ($userReview['comment'] ?? '');
                        ?>
                        <div class="mb-4">
                            <h3 class="h6 fw-bold mb-2">
                                <?php echo $userReview ? 'Yorumunu Güncelle' : 'Bu ürün için yorum yap'; ?>
                            </h3>
                            <form method="post" class="border rounded p-3 bg-light">
                                <div class="mb-3">
                                    <label class="form-label">Puan (1–5)</label>
                                    <select name="rating" class="form-select" required>
                                        <option value="">Seçiniz...</option>
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($formRating == $i ? 'selected' : ''); ?>>
                                                <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Yorumunuz</label>
                                    <textarea name="comment" class="form-control" rows="3" required><?php 
                                        echo htmlspecialchars($formComment);
                                    ?></textarea>
                                </div>
                                <button type="submit" name="review_submit" class="btn btn-success">
                                    Kaydet
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Bu ürün için yorum yapabilmek ve puan verebilmek için,
                            ürünü **satın almış ve sipariş durumunuzun "Tamamlandı"** olması gerekir.
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

                <!-- Yorum listesi -->
                <?php if (!empty($reviews)): ?>
                    <div class="mt-3">
                        <?php foreach ($reviews as $r): ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                                    <span class="text-muted small">
                                        <?php echo htmlspecialchars($r['created_at']); ?>
                                    </span>
                                </div>
                                <div class="mb-1">
                                    <span class="badge bg-warning text-dark">
                                        Puan: <?php echo (int)$r['rating']; ?>/5
                                    </span>
                                </div>
                                <p class="mb-0">
                                    <?php echo nl2br(htmlspecialchars($r['comment'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Bu ürün için henüz yapılmış bir yorum yok.</p>
                <?php endif; ?>

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

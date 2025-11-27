<?php
session_start();

// Sepetteki ürün sayısı (navbar için)
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Hakkımızda - Sezer Parfüm</title>
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
                    <a class="nav-link active" href="about.php">Hakkımızda</a>
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
        <h1 class="h3 fw-bold mb-4">Hakkımızda</h1>

        <p class="lead">
            Sezer Parfüm, farklı tarzlara ve ruh hâllerine hitap eden seçili parfümleri 
            bir araya getiren, küçük ama özenli bir online parfüm mağazasıdır.
        </p>

        <p>
            Amacımız; herkesin kendini iyi hissettiren, karakterine ve tarzına uyan bir 
            kokuya ulaşabilmesini sağlamak. Ürünlerimizi seçerken kokunun kalıcılığına, 
            notalarının uyumuna ve günlük kullanımda verdiği hissiyata dikkat ediyoruz.
        </p>

        <h2 class="h5 fw-bold mt-4">Neden Sezer Parfüm?</h2>
        <ul>
            <li>Özenle seçilmiş koku portföyü</li>
            <li>Hızlı ve güvenilir alışveriş deneyimi</li>
            <li>Şeffaf fiyat politikası</li>
            <li>Sürekli güncellenen ürün listesi</li>
        </ul>

        <h2 class="h5 fw-bold mt-4">Vizyonumuz</h2>
        <p>
            Hem günlük kullanım hem de özel günler için, “favori parfüm” dendiğinde 
            akla gelen ilk adreslerden biri olmak.
        </p>

        <h2 class="h5 fw-bold mt-4">Misyonumuz</h2>
        <p>
            Kullanıcıya sade, anlaşılır ve güven veren bir alışveriş deneyimi sunarken, 
            kokularla insanların kendini ifade etmesini kolaylaştırmak.
        </p>
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

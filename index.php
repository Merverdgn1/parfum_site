<?php
    session_start();
    require 'db.php';

    // Arama ve kategori parametreleri
    $searchQuery    = trim($_GET['q']   ?? '');
    $categoryFilter = trim($_GET['cat'] ?? '');

    if ($searchQuery !== '') {
        // Arama varsa onu önceliklendirelim
        $products = searchProducts($searchQuery);
    } elseif ($categoryFilter !== '') {
        // Sadece kategori seçilmişse
        $products = getProductsByCategory($categoryFilter);
    } else {
        // Hiçbiri yoksa tüm ürünler
        $products = getAllProducts();
    }

    // Sepetteki toplam adet
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
    <title>Sezer Parfüm - Online Parfüm Mağazası</title>
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
                        <a class="nav-link active" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Parfümler</a>
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
                    <?php
                    $isAdmin = false;
                    if (isset($_SESSION['user'])) {
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
                    }
                    ?>

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

    <!-- HERO -->
    <header class="py-5 bg-light text-center hero-section">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">Kendine Yakışan Kokuyu Keşfet</h1>
            <p class="lead mb-4">
                Özenle seçilmiş kadın ve erkek parfümleri, hızlı kargo ve güvenli alışveriş ile kapında.
            </p>
            <a href="#products" class="btn btn-primary btn-lg">Parfümleri Gör</a>
        </div>
    </header>

    <!-- ÜRÜNLER + ARAMA + KATEGORİ FİLTRE -->
    <main class="py-5">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                <h2 id="products" class="mb-3 mb-md-0 fw-bold">Öne Çıkan Parfümler</h2>

                <!-- ARAMA FORMU -->
                <form class="d-flex" method="get" action="index.php">
                    <input 
                        type="text" 
                        name="q" 
                        class="form-control me-2" 
                        placeholder="Parfüm ara..." 
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                    >
                    <button class="btn btn-outline-primary" type="submit">Ara</button>
                </form>
            </div>

            <!-- KATEGORİ BUTONLARI -->
            <div class="mb-4">
                <?php
                    $cat = $categoryFilter;
                    // butonların aktiflik durumunu belirleyelim
                    $btnClass = function($value) use ($cat, $searchQuery) {
                        // arama aktifken kategori filtresini göstermiyoruz (sadece görsel olarak)
                        if ($searchQuery !== '') return 'btn btn-sm btn-outline-secondary me-2';
                        if ($value === '' && $cat === '') return 'btn btn-sm btn-secondary me-2';
                        if ($value !== '' && $cat === $value) return 'btn btn-sm btn-secondary me-2';
                        return 'btn btn-sm btn-outline-secondary me-2';
                    };
                ?>

                <a href="index.php" class="<?php echo $btnClass(''); ?>">
                    Tümü
                </a>
                <a href="index.php?cat=Kadın" class="<?php echo $btnClass('Kadın'); ?>">
                    Kadın
                </a>
                <a href="index.php?cat=Erkek" class="<?php echo $btnClass('Erkek'); ?>">
                    Erkek
                </a>
                <a href="index.php?cat=Unisex" class="<?php echo $btnClass('Unisex'); ?>">
                    Unisex
                </a>
            </div>

            <?php if ($searchQuery !== ''): ?>
                <p class="text-muted mb-4">
                    “<strong><?php echo htmlspecialchars($searchQuery); ?></strong>” için 
                    <?php echo count($products); ?> sonuç bulundu.
                </p>
            <?php elseif ($categoryFilter !== ''): ?>
                <p class="text-muted mb-4">
                    Kategori: <strong><?php echo htmlspecialchars($categoryFilter); ?></strong>
                    (<?php echo count($products); ?> ürün)
                </p>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    Şu anda görüntülenecek ürün bulunamadı.
                    <?php if ($searchQuery !== ''): ?>
                        <br>Arama kriterinizi değiştirerek tekrar deneyebilirsiniz.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>

                        <?php
                            // Resim yolu – veritabanında sadece dosya adı var
                            $imageFile = trim($product['image'] ?? '');

                            if ($imageFile === '') {
                                // Boşsa placeholder
                                $imagePath = 'assets/img/placeholder.jpg';
                            } else {
                                // Normal ürün resmi
                                $imagePath = 'assets/img/' . $imageFile;
                            }
                        ?>

                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm">
                                <img
                                    src="<?php echo htmlspecialchars($imagePath); ?>" 
                                    class="card-img-top" 
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    onerror="this.onerror=null; this.src='assets/img/placeholder.jpg';"
                                >
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h5>
                                    <?php if (!empty($product['category'])): ?>
                                        <span class="badge bg-secondary mb-2">
                                            <?php echo htmlspecialchars($product['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <p class="card-text text-muted mb-2">
                                        <?php echo htmlspecialchars($product['description']); ?>
                                    </p>
                                    <p class="fw-bold mb-3">
                                        ₺<?php echo number_format($product['price'], 2, ',', '.'); ?>
                                    </p>

                                    <div class="d-flex gap-2 mt-auto">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            Detay
                                        </a>
                                        <a href="cart.php?action=add&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Sepete Ekle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="py-4 bg-dark text-light text-center">
        <div class="container">
            <small>© <?php echo date("Y"); ?> Sezer Parfüm - Tüm hakları saklıdır.</small>
        </div>
    </footer>

    <script 
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
</body>
</html>

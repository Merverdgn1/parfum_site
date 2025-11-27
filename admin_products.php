<?php
session_start();
require 'db.php';

/**
 * Kullanıcının admin olup olmadığını kontrol eden yardımcı fonksiyon
 */
function isAdmin(int $userId, mysqli $mysqli): bool {
    $stmt = $mysqli->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res && (int)$res['is_admin'] === 1;
}

// Admin kontrolü
if (!isset($_SESSION['user']) || !isAdmin((int)$_SESSION['user']['id'], $mysqli)) {
    echo "Bu sayfayı görüntüleme yetkiniz yok.";
    exit;
}

// Ürün ekleme işlemi
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $image       = trim($_POST['image'] ?? '');
    $category    = trim($_POST['category'] ?? '');

    if ($name === '' || $price === '') {
        $errors[] = 'Ürün adı ve fiyat zorunludur.';
    } elseif (!is_numeric($price)) {
        $errors[] = 'Fiyat sayısal olmalıdır.';
    }

    if ($category === '') {
        $errors[] = 'Lütfen bir kategori seçin.';
    }

    if (empty($errors)) {
        $p = (float) $price;

        $stmt = $mysqli->prepare(
            "INSERT INTO products (name, description, price, image, category)
             VALUES (?, ?, ?, ?, ?)"
        );
        // s (name), s (desc), d (price), s (image), s (category)
        $stmt->bind_param("ssdss", $name, $description, $p, $image, $category);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $success = 'Ürün başarıyla eklendi.';
            // formu temizlemek için POST değerlerini sıfırlayalım
            $_POST = [];
        } else {
            $errors[] = 'Ürün eklenirken bir hata oluştu.';
        }
    }
}

// Silme işlemi
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];

    $stmtDel = $mysqli->prepare("DELETE FROM products WHERE id = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    $stmtDel->close();

    header("Location: admin_products.php");
    exit;
}

// Tüm ürünleri çek
$result = $mysqli->query(
    "SELECT id, name, price, image, category 
     FROM products 
     ORDER BY id DESC"
);
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Navbar için sepet sayısı (çok önemli değil ama dursun)
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
    <title>Admin - Ürün Yönetimi</title>
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <!-- Sol taraf -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Siteye Git</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin_products.php">Ürün Yönetimi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_orders.php">Siparişlerim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_messages.php">Mesajlar</a>
                </li>
            </ul>

            <!-- Sağ taraf -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item d-flex align-items-center">
                    <span class="navbar-text me-2 mb-0">
                        Admin: <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
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
        <h1 class="h3 fw-bold mb-4">Ürün Yönetimi</h1>

        <div class="row">
            <!-- ÜRÜN EKLEME FORMU -->
            <div class="col-md-5 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-3">Yeni Ürün Ekle</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?php echo htmlspecialchars($e); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Ürün Adı</label>
                                <input type="text" name="name" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" class="form-control" rows="3"><?php 
                                    echo htmlspecialchars($_POST['description'] ?? '');
                                ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Fiyat (örn: 499.90)</label>
                                <input type="text" name="price" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="category" class="form-control">
                                    <option value="">Seçiniz...</option>
                                    <?php 
                                        $selectedCat = $_POST['category'] ?? '';
                                    ?>
                                    <option value="Kadın"   <?php if ($selectedCat === 'Kadın')   echo 'selected'; ?>>Kadın</option>
                                    <option value="Erkek"   <?php if ($selectedCat === 'Erkek')   echo 'selected'; ?>>Erkek</option>
                                    <option value="Unisex"  <?php if ($selectedCat === 'Unisex')  echo 'selected'; ?>>Unisex</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Görsel (dosya adı veya URL)</label>
                                <input type="text" name="image" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['image'] ?? ''); ?>"
                                       placeholder="Örn: citrus_morning.jpg veya https://...">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Ürünü Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ÜRÜN LİSTESİ -->
            <div class="col-md-7">
                <h2 class="h5 fw-bold mb-3">Mevcut Ürünler</h2>

                <?php if (empty($products)): ?>
                    <div class="alert alert-info">
                        Henüz ürün yok. Soldan ürün ekleyebilirsiniz.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ad</th>
                                    <th>Kategori</th>
                                    <th>Fiyat</th>
                                    <th>Görsel</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                    <?php
                                        // Görsel yolu: boş ise placeholder, http(s) ise direkt URL, değilse assets/img klasöründen
                                        $imageFile = trim($p['image'] ?? '');
                                        if ($imageFile === '') {
                                            $imagePath = 'assets/img/placeholder.jpg';
                                        } elseif (preg_match('#^https?://#i', $imageFile)) {
                                            $imagePath = $imageFile;
                                        } else {
                                            $imagePath = 'assets/img/' . $imageFile;
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $p['id']; ?></td>
                                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['category'] ?? ''); ?></td>
                                        <td>₺<?php echo number_format($p['price'], 2, ',', '.'); ?></td>
                                        <td>
                                            <img
                                                src="<?php echo htmlspecialchars($imagePath); ?>" 
                                                alt="<?php echo htmlspecialchars($p['name']); ?>"
                                                style="width: 60px; height: 60px; object-fit: cover;"
                                                onerror="this.onerror=null; this.src='assets/img/placeholder.jpg';"
                                            >
                                        </td>
                                        <td>
                                            <a href="admin_products.php?action=delete&id=<?php echo $p['id']; ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Bu ürünü silmek istediğine emin misin?');">
                                                Sil
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<footer class="py-4 bg-dark text-light text-center mt-5">
    <div class="container">
        <small>© <?php echo date("Y"); ?> Sezer Parfüm - Admin Paneli</small>
    </div>
</footer>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>
</body>
</html>

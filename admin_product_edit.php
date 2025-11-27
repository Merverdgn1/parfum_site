<?php
session_start();
require 'db.php';

/**
 * Aynı admin kontrol fonksiyonunu burada da kullanalım
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

// Düzenlenecek ürün id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: admin_products.php");
    exit;
}

// Ürünü getir
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: admin_products.php?notfound=1");
    exit;
}

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
        $p = (float)$price;

        $stmt = $mysqli->prepare("
            UPDATE products
            SET name = ?, description = ?, price = ?, image = ?, category = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssdssi", $name, $description, $p, $image, $category, $id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $success = 'Ürün başarıyla güncellendi.';

            // Son halini tekrar çek
            $stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $errors[] = 'Ürün güncellenirken bir hata oluştu.';
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Ürün Düzenle - Admin</title>
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
        <div class="ms-auto">
            <a href="admin_products.php" class="btn btn-outline-light btn-sm">Ürün Yönetimine Dön</a>
        </div>
    </div>
</nav>

<main class="py-5">
    <div class="container">
        <h1 class="h4 fw-bold mb-4">Ürün Düzenle (#<?php echo $product['id']; ?>)</h1>

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
                       value="<?php echo htmlspecialchars($_POST['name'] ?? $product['name']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Açıklama</label>
                <textarea name="description" class="form-control" rows="4"><?php
                    echo htmlspecialchars($_POST['description'] ?? ($product['description'] ?? ''));
                ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Fiyat (örn: 499.90)</label>
                <input type="text" name="price" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['price'] ?? $product['price']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <?php $selectedCat = $_POST['category'] ?? $product['category'] ?? ''; ?>
                <select name="category" class="form-control">
                    <option value="">Seçiniz...</option>
                    <option value="Kadın"  <?php if ($selectedCat === 'Kadın')  echo 'selected'; ?>>Kadın</option>
                    <option value="Erkek"  <?php if ($selectedCat === 'Erkek')  echo 'selected'; ?>>Erkek</option>
                    <option value="Unisex" <?php if ($selectedCat === 'Unisex') echo 'selected'; ?>>Unisex</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Görsel (dosya adı veya URL)</label>
                <input type="text" name="image" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['image'] ?? $product['image']); ?>">
            </div>

            <button type="submit" class="btn btn-primary">
                Kaydet
            </button>
            <a href="admin_products.php" class="btn btn-outline-secondary ms-2">
                İptal
            </a>
        </form>
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

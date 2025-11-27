<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php?must_login=1");
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// Kullanıcının favori ürünlerini çek
$stmt = $mysqli->prepare("
    SELECT p.*
    FROM favorites f
    JOIN products p ON p.id = f.product_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$favorites = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Favorilerim - Sezer Parfüm</title>
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

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="my_favorites.php">Favorilerim</a>
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
        <h1 class="h4 fw-bold mb-4">Favorilerim</h1>

        <?php if (empty($favorites)): ?>
            <div class="alert alert-info">
                Henüz favorilere eklediğiniz bir ürün yok.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($favorites as $p): ?>
                    <?php
                        $imageFile = trim($p['image'] ?? '');
                        if ($imageFile === '') {
                            $imagePath = 'assets/img/placeholder.jpg';
                        } elseif (preg_match('#^https?://#i', $imageFile)) {
                            $imagePath = $imageFile;
                        } else {
                            $imagePath = 'assets/img/' . $imageFile;
                        }
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                 class="card-img-top"
                                 alt="<?php echo htmlspecialchars($p['name']); ?>"
                                 style="height:200px; object-fit:cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </h5>
                                <p class="card-text mb-1">
                                    ₺<?php echo number_format($p['price'], 2, ',', '.'); ?>
                                </p>
                                <div class="mt-auto d-flex justify-content-between">
                                    <a href="cart.php?action=add&id=<?php echo (int)$p['id']; ?>" 
                                       class="btn btn-sm btn-success">
                                        Sepete Ekle
                                    </a>
                                    <a href="favorites.php?action=remove&id=<?php echo (int)$p['id']; ?>&redirect=my_favorites.php"
                                       class="btn btn-sm btn-outline-danger">
                                        Favoriden Çıkar
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

<footer class="py-4 bg-dark text-light text-center mt-5">
    <div class="container">
        <small>© <?php echo date("Y"); ?> Sezer Parfüm</small>
    </div>
</footer>

<script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>
</body>
</html>

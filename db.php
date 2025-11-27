<?php
// Veritabanı bağlantı ayarları
$DB_HOST = '127.0.0.1';
$DB_PORT = 3307;          // XAMPP MySQL portun
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'parfum_db';

// MySQLi ile bağlantı
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

if ($mysqli->connect_errno) {
    die('Veritabanı bağlantı hatası: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

// Tüm ürünleri çeken fonksiyon
function getAllProducts(): array {
    global $mysqli;

    $result = $mysqli->query(
        "SELECT id, name, description, price, image, category 
         FROM products
         ORDER BY id DESC"
    );

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// ID ile tek ürün çeken fonksiyon
function getProductById(int $id): ?array {
    global $mysqli;

    $stmt = $mysqli->prepare(
        "SELECT id, name, description, price, image, category 
         FROM products 
         WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res ?: null;
}

// Arama fonksiyonu: isim veya açıklamada geçen ürünleri getirir
function searchProducts(string $keyword): array {
    global $mysqli;

    $like = '%' . $keyword . '%';

    $stmt = $mysqli->prepare(
        "SELECT id, name, description, price, image, category
         FROM products
         WHERE name LIKE ? OR description LIKE ?
         ORDER BY id DESC"
    );
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $products;
}

// Kategoriye göre ürünleri getir
function getProductsByCategory(string $category): array {
    global $mysqli;

    $stmt = $mysqli->prepare(
        "SELECT id, name, description, price, image, category
         FROM products
         WHERE category = ?
         ORDER BY id DESC"
    );
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $products;
}

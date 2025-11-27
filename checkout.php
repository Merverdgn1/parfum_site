<?php
session_start();
require 'db.php'; // Burada $mysqli bağlantınız olmalı (db.php içinde)

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user'])) {
    header("Location: login.php?must_login=1");
    exit;
}

// Sepet kontrolü
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Sepet içeriği ve toplam tutar
$cartItems = $_SESSION['cart'];

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['qty'];
}

$errors  = [];
$orderId = null;

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formdan gelen verileri al
    $full_name        = trim($_POST['full_name'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $city             = trim($_POST['city'] ?? '');
    $district         = trim($_POST['district'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $payment_method   = trim($_POST['payment_method'] ?? '');
    $shipping_company = trim($_POST['shipping_company'] ?? '');

    // Basit doğrulamalar
    if ($full_name === '' || $address === '' || $phone === '') {
        $errors[] = 'İsim, adres ve telefon alanları zorunludur.';
    }

    if ($payment_method === '') {
        $errors[] = 'Lütfen bir ödeme yöntemi seçin.';
    }

    if ($shipping_company === '') {
        $errors[] = 'Lütfen bir kargo firması seçin.';
    }

    // (İstersen buraya city/district için de zorunlu kontrol ekleyebilirsin)

    // Hata yoksa veritabanına kaydet
    if (empty($errors)) {

        // Güvenlik için sepeti tekrar kontrol etmek mantıklı
        if (empty($_SESSION['cart'])) {
            header("Location: cart.php");
            exit;
        }

        $userId = (int) $_SESSION['user']['id'];

        // Eğer toplamı PHP tarafında kesin float olsun istiyorsan:
        $totalAmount = (float) $total;

        // TRANSACTION
        $mysqli->begin_transaction();

        try {
            // orders tablosuna kaydet
            $stmtOrder = $mysqli->prepare(
                "INSERT INTO orders 
                 (user_id, total_amount, full_name, address, city, district, phone, 
                  payment_method, shipping_company, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hazırlanıyor')"
            );

            // i: int, d: double, s: string
            $stmtOrder->bind_param(
                "idsssssss",
                $userId,
                $totalAmount,
                $full_name,
                $address,
                $city,
                $district,
                $phone,
                $payment_method,
                $shipping_company
            );

            $stmtOrder->execute();
            $orderId = $stmtOrder->insert_id;
            $stmtOrder->close();

            // order_items tablosuna satırları ekle
            $stmtItem = $mysqli->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($cartItems as $item) {
                $productId = (int) $item['id'];
                $qty       = (int) $item['qty'];
                $price     = (float) $item['price'];

                $stmtItem->bind_param("iiid", $orderId, $productId, $qty, $price);
                $stmtItem->execute();
            }

            $stmtItem->close();

            // Her şey başarılı ise commit
            $mysqli->commit();

            // Sepeti temizle
            $_SESSION['cart'] = [];

            // Başarı sayfasına gönder
            header("Location: order_success.php?order_id=" . $orderId);
            exit;

        } catch (Exception $e) {
            // Hata olursa geri al
            $mysqli->rollback();
            $errors[] = "Sipariş kaydedilirken bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Ödeme ve Teslimat - Sezer Parfüm</title>
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
    </div>
</nav>

<main class="py-5">
    <div class="container">
        <h1 class="h4 fw-bold mb-4">Ödeme ve Teslimat Bilgileri</h1>

        <div class="row">
            <!-- Sol: Form -->
            <div class="col-md-7">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" 
                               name="full_name" 
                               class="form-control"
                               required
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? $_SESSION['user']['name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea name="address" 
                                  class="form-control" 
                                  rows="3"
                                  required><?php 
                            echo htmlspecialchars($_POST['address'] ?? '');
                        ?></textarea>
                    </div>

                    <!-- Şehir / İlçe / Telefon -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Şehir</label>
                            <select name="city" id="city" class="form-select" required>
                                <option value="">Şehir seçiniz...</option>
                                <!-- JS dolduracak -->
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">İlçe</label>
                            <select name="district" id="district" class="form-select" required>
                                <option value="">Önce şehir seçiniz...</option>
                                <!-- JS dolduracak -->
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" 
                                   name="phone" 
                                   class="form-control"
                                   required
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ödeme Yöntemi</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <?php $pm = $_POST['payment_method'] ?? ''; ?>
                            <option value="Kredi Kartı"  <?php if ($pm === 'Kredi Kartı')  echo 'selected'; ?>>Kredi Kartı (Sanal)</option>
                            <option value="Kapıda Ödeme" <?php if ($pm === 'Kapıda Ödeme') echo 'selected'; ?>>Kapıda Ödeme</option>
                            <option value="Havale/EFT"   <?php if ($pm === 'Havale/EFT')   echo 'selected'; ?>>Havale / EFT</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kargo Firması</label>
                        <select name="shipping_company" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <?php $sc = $_POST['shipping_company'] ?? ''; ?>
                            <option value="Yurtiçi Kargo" <?php if ($sc === 'Yurtiçi Kargo') echo 'selected'; ?>>Yurtiçi Kargo</option>
                            <option value="MNG Kargo"    <?php if ($sc === 'MNG Kargo')    echo 'selected'; ?>>MNG Kargo</option>
                            <option value="Aras Kargo"   <?php if ($sc === 'Aras Kargo')   echo 'selected'; ?>>Aras Kargo</option>
                            <option value="PTT Kargo"    <?php if ($sc === 'PTT Kargo')    echo 'selected'; ?>>PTT Kargo</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">
                        Siparişi Onayla
                    </button>
                    <a href="cart.php" class="btn btn-outline-secondary ms-2">
                        Sepete Geri Dön
                    </a>
                </form>
            </div>

            <!-- Sağ: Sipariş Özeti -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Sipariş Özeti</h2>

                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <div class="text-muted small">Adet: <?php echo (int)$item['qty']; ?></div>
                                </div>
                                <div>
                                    ₺<?php echo number_format($item['price'] * $item['qty'], 2, ',', '.'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Toplam:</span>
                            <span>₺<?php echo number_format($total, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

<!-- Şehir / İlçe JS -->
<script>
    // Bazı iller için örnek ilçe listesi (diğer illeri sen ekleyebilirsin)
    const cityDistricts = {
        "Adana": [
            "Çukurova","Seyhan","Yüreğir","Sarıçam","Ceyhan","İmamoğlu","Karaisalı",
            "Karataş","Kozan","Pozantı","Saimbeyli","Tufanbeyli","Yumurtalık"
        ],
        "Ankara": [
            "Çankaya","Keçiören","Yenimahalle","Altındağ","Mamak","Sincan","Etimesgut",
            "Pursaklar","Gölbaşı","Polatlı","Beypazarı","Ayaş","Çubuk","Kızılcahamam"
        ],
        "İstanbul": [
            "Adalar","Arnavutköy","Ataşehir","Avcılar","Bağcılar","Bahçelievler","Bakırköy",
            "Başakşehir","Bayrampaşa","Beşiktaş","Beykoz","Beylikdüzü","Beyoğlu","Büyükçekmece",
            "Çatalca","Çekmeköy","Esenler","Esenyurt","Eyüpsultan","Fatih","Gaziosmanpaşa",
            "Güngören","Kadıköy","Kağıthane","Kartal","Küçükçekmece","Maltepe","Pendik",
            "Sancaktepe","Sarıyer","Silivri","Sultanbeyli","Sultangazi","Şile","Şişli",
            "Tuzla","Ümraniye","Üsküdar","Zeytinburnu"
        ],
        "İzmir": [
            "Balçova","Bayındır","Bayraklı","Bergama","Beydağ","Bornova","Buca","Çeşme",
            "Çiğli","Dikili","Foça","Gaziemir","Güzelbahçe","Karabağlar","Karaburun",
            "Karşıyaka","Kemalpaşa","Kınık","Kiraz","Konak","Menderes","Menemen",
            "Narlıdere","Ödemiş","Seferihisar","Selçuk","Tire","Torbalı","Urla"
        ]
        // Diğer illerin ilçelerini de bu objeye aynı şekilde ekleyebilirsin
    };

    // PHP'den gelen önceki seçimler (validasyon hatasında seçili kalsın)
    const selectedCity     = <?php echo json_encode($_POST['city'] ?? ''); ?>;
    const selectedDistrict = <?php echo json_encode($_POST['district'] ?? ''); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const citySelect     = document.getElementById('city');
        const districtSelect = document.getElementById('district');

        // 1) 81 ilin listesi
        const cityNames = [
            "Adana","Adıyaman","Afyonkarahisar","Ağrı","Amasya","Ankara","Antalya","Artvin",
            "Aydın","Balıkesir","Bilecik","Bingöl","Bitlis","Bolu","Burdur","Bursa",
            "Çanakkale","Çankırı","Çorum","Denizli","Diyarbakır","Edirne","Elazığ","Erzincan",
            "Erzurum","Eskişehir","Gaziantep","Giresun","Gümüşhane","Hakkari","Hatay","Isparta",
            "Mersin","İstanbul","İzmir","Kars","Kastamonu","Kayseri","Kırklareli","Kırşehir",
            "Kocaeli","Konya","Kütahya","Malatya","Manisa","Kahramanmaraş","Mardin","Muğla",
            "Muş","Nevşehir","Niğde","Ordu","Rize","Sakarya","Samsun","Siirt",
            "Sinop","Sivas","Tekirdağ","Tokat","Trabzon","Tunceli","Şanlıurfa","Uşak",
            "Van","Yozgat","Zonguldak","Aksaray","Bayburt","Karaman","Kırıkkale","Batman",
            "Şırnak","Bartın","Ardahan","Iğdır","Yalova","Karabük","Kilis","Osmaniye",
            "Düzce"
        ];

        // Şehir select'ini doldur
        cityNames.forEach(function (city) {
            const opt = document.createElement('option');
            opt.value = city;
            opt.textContent = city;
            if (city === selectedCity) {
                opt.selected = true;
            }
            citySelect.appendChild(opt);
        });

        // İlçe doldurma fonksiyonu
        function fillDistricts(city, selectedDist) {
            districtSelect.innerHTML = '<option value="">İlçe seçiniz...</option>';

            if (!cityDistricts[city]) {
                // Bu şehir için henüz ilçe listesi eklenmediyse boş bırak
                return;
            }

            cityDistricts[city].forEach(function (dist) {
                const opt = document.createElement('option');
                opt.value = dist;
                opt.textContent = dist;
                if (dist === selectedDist) {
                    opt.selected = true;
                }
                districtSelect.appendChild(opt);
            });
        }

        // Sayfa ilk açıldığında eski seçim varsa doldur
        if (selectedCity) {
            fillDistricts(selectedCity, selectedDistrict);
        }

        // Şehir değişince ilçe listesini yenile
        citySelect.addEventListener('change', function () {
            fillDistricts(this.value, '');
        });
    });
</script>

</body>
</html>

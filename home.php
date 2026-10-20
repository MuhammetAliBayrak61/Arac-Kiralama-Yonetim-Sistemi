<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmamışsa login sayfasına at
if (!isset($_SESSION['id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Prosedürü çağır (Artık hepsi geliyor + tarih bilgisiyle)
$sql = $db->query("CALL Sp_AraclariListele()");
$araclar = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Araç Kiralama - Ana Sayfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .vehicle-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: #fff;
        }
        .vehicle-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .card-img-wrap {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .vehicle-card:hover .card-img-wrap img {
            transform: scale(1.1);
        }
        .price-badge {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        /* Pasif (Kirada) Araç Stili */
        .rented-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.6);
            z-index: 1;
        }
        .rented-badge {
            position: absolute;
            top: 15px; left: 15px;
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 2;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 shadow">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4" href="index.php">
        <i class="fa fa-car-side text-warning"></i> RentACar
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
            <span class="nav-link text-white">
                <i class="fa fa-user"></i> Hoşgeldin, <strong><?= $_SESSION['ad'] ?></strong>
            </span>
        </li>
        <li class="nav-item me-2">
            <a href="kiralanan_araclar.php" class="btn btn-outline-warning btn-sm px-3 rounded-pill">
                <i class="fa fa-history"></i> Kiralanan Araçlar
            </a>
        </li>
        <li class="nav-item">
            <a href="logout.php" class="btn btn-danger btn-sm px-3 rounded-pill">
                <i class="fa fa-sign-out-alt"></i> Çıkış
            </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold display-6">Araç Filomuz</h2>
        <p class="text-muted">Size en uygun aracı seçin ve yolculuğun tadını çıkarın.</p>
        <hr class="w-25 mx-auto text-primary opacity-100">
    </div>

    <div class="row">
        <?php if (count($araclar) > 0): ?>
            <?php foreach($araclar as $arac): 
                $resimLink = !empty($arac['ResimUrl']) ? $arac['ResimUrl'] : 'https://placehold.co/600x400?text=Resim+Yok';
                $musaitMi = ($arac['Durum'] == 1);
                
                // Tarihi güzelleştir (2025-12-25 -> 25.12.2025)
                $tarihYazisi = "";
                if (!$musaitMi && !empty($arac['TahminiTeslim'])) {
                    $tarih = date("d.m.Y", strtotime($arac['TahminiTeslim']));
                    $tarihYazisi = "<i class='fa fa-calendar-alt'></i> $tarih Tarihinden Sonra";
                } elseif (!$musaitMi) {
                    $tarihYazisi = "Yakında Müsait";
                }
            ?>
            
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 vehicle-card shadow-sm position-relative">
                    
                    <?php if(!$musaitMi): ?>
                        <div class="rented-badge">KİRADA</div>
                    <?php endif; ?>

                    <div class="card-img-wrap">
                        <img src="<?= $resimLink ?>" 
                             alt="<?= $arac['MarkaAdi'] ?>"
                             onerror="this.onerror=null; this.src='https://placehold.co/600x400?text=Resim+Yok';">
                        
                        <div class="price-badge">
                            <?= number_format($arac['GunlukUcret'], 0, ',', '.') ?> TL <small>/ Gün</small>
                        </div>
                    </div>

                    <div class="card-body">
                        <h4 class="card-title fw-bold mb-1"><?= $arac['MarkaAdi'] ?></h4>
                        <h6 class="text-muted mb-3"><?= $arac['Model'] ?></h6>
                        
                        <div class="d-flex justify-content-between text-secondary small mb-4 px-2">
                            <span><i class="fa fa-gas-pump text-primary"></i> Yakıt</span>
                            <span><i class="fa fa-user text-primary"></i> 5 Kişilik</span>
                        </div>

                        <?php if($musaitMi): ?>
                            <a href="arac_detay.php?id=<?= $arac['AracId'] ?>" class="btn btn-dark w-100 py-2 rounded-pill fw-bold">
                                Hemen Kirala <i class="fa fa-arrow-right ms-1"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 py-2 rounded-pill fw-bold" disabled style="opacity: 0.7; cursor: not-allowed;">
                                <?= $tarihYazisi ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <div class="alert alert-info py-4">Araç bulunamadı.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="bg-dark text-white text-center py-3 mt-5">
    <p class="mb-0">&copy; 2023 RentACar. Tüm hakları saklıdır.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
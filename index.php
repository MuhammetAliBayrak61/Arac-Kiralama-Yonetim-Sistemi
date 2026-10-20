<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Araç Kiralama - Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .card-img-top { height: 250px; object-fit: cover; width: 100%; }
        /* Index'e özel pasif buton stili */
        .btn-disabled-custom {
            background-color: #e9ecef;
            color: #6c757d;
            border: 1px solid #ced4da;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow" style="background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🚗 RentPro</a>
    <div class="ms-auto">
        <a href="login.php" class="btn btn-outline-light me-2">Giriş Yap</a>
        <a href="register.php" class="btn btn-warning fw-bold">Kayıt Ol</a>
    </div>
  </div>
</nav>

<div class="hero shadow text-white text-center py-5" style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1950&q=80'); background-size: cover; background-position: center; padding: 100px 0;">
    <h1 class="display-4 fw-bold">Yolculuğun Burada Başlıyor</h1>
    <p class="lead mb-4">Güvenilir, Konforlu ve Uygun Fiyatlı Araç Kiralama</p>
    <a href="#araclar" class="btn btn-lg btn-primary px-5">Araçları İncele</a>
</div>

<div class="container my-5" id="araclar">
    <h2 class="text-center mb-5 fw-bold text-secondary">✨ Öne Çıkan Araçlar</h2>
    <div class="row">
        <?php
        $sorgu = $db->query("CALL Sp_RastgeleAracGetir()");
        
        if($sorgu->rowCount() > 0) {
            while($arac = $sorgu->fetch(PDO::FETCH_ASSOC)) {
                $resim = !empty($arac['ResimUrl']) ? $arac['ResimUrl'] : 'https://placehold.co/600x400?text=Resim+Yok';
                $musaitMi = ($arac['Durum'] == 1);
                
                $tarihYazisi = "Yakında";
                if (!$musaitMi && !empty($arac['TahminiTeslim'])) {
                    $tarihYazisi = date("d.m.Y", strtotime($arac['TahminiTeslim'])) . "'den Sonra";
                }
        ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 position-relative border-0">
                <span class="position-absolute top-0 end-0 bg-dark text-white px-3 py-1 m-2 rounded-pill fw-bold" style="opacity: 0.9;">
                    <?= $arac['GunlukUcret'] ?> ₺ / Gün
                </span>
                
                <img src="<?= $resim ?>" 
                     class="card-img-top" 
                     alt="<?= $arac['MarkaAdi'] ?>"
                     onerror="this.onerror=null; this.src='https://placehold.co/600x400?text=Resim+Yok';">
                     
                <div class="card-body text-center">
                    <h5 class="fw-bold text-dark"><?= $arac['MarkaAdi'] . ' ' . $arac['Model'] ?></h5>
                    <p class="text-muted"><?= $arac['ModelYili'] ?> Model</p>
                    
                    <?php if($musaitMi): ?>
                        <a href="login.php" class="btn btn-outline-primary w-100 rounded-pill fw-bold">
                            Kiralamak İçin Giriş Yap
                        </a>
                    <?php else: ?>
                        <button class="btn btn-disabled-custom w-100 rounded-pill fw-bold" disabled>
                            <i class="fa fa-clock"></i> <?= $tarihYazisi ?> Müsait
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php 
            }
        } else {
            echo '<div class="col-12 text-center alert alert-warning">Araç bulunamadı.</div>';
        }
        $sorgu->closeCursor();
        ?>
    </div>
</div>

</body>
</html>
<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['id'])) { 
    header("Location: login.php"); 
    exit; 
}

$arac_id = $_GET['id'];
$alertScript = ""; // JavaScript uyarısı için değişken

// FORM GÖNDERİLDİĞİNDE ÇALIŞACAK KISIM
if ($_POST) {
    try {
        // 1. Önce giriş yapan kullanıcının Müşteri ID'sini buluyoruz
        $stmt = $db->prepare("CALL Sp_MusteriBul(?)");
        $stmt->execute([$_SESSION['id']]);
        $musteri = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($musteri) {
            // 2. Kiralama Prosedürünü Çağırıyoruz
            // SQL tarafındaki tarih ve fiyat kontrolleri burada devreye girer.
            // Eğer bir kural ihlali varsa SQL hata fırlatır ve kod 'catch' bloğuna atlar.
            
            $kiralama = $db->prepare("CALL Sp_KiralamaYap(?, ?, ?, ?, ?)");
            $kiralama->execute([
                $arac_id, 
                $musteri['MusteriId'], 
                $_POST['alis'], 
                $_POST['teslim'], 
                $_POST['toplam_tutar']
            ]);
            
            // Eğer hata yoksa başarılı mesajı göster ve anasayfaya yönlendir
            $alertScript = "
            Swal.fire({
                icon: 'success',
                title: 'İşlem Başarılı!',
                text: 'Araç başarıyla kiralandı. Keyifli sürüşler!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => { window.location = 'home.php'; });";
        } else {
            // Müşteri kaydı bulunamazsa (Örn: Admin hesabı ile kiralama denenirse)
            $alertScript = "Swal.fire({ icon: 'error', title: 'Hata', text: 'Müşteri kaydınız bulunamadı.' });";
        }

    } catch (PDOException $e) {
        // SQL'den gelen hatayı yakalıyoruz (SIGNAL MESSAGE_TEXT)
        $hataMesaji = $e->getMessage();
        
        // SQLSTATE kodlarını temizleyip sadece kullanıcı mesajını alıyoruz
        if (strpos($hataMesaji, 'SQLSTATE') !== false) {
            $parcalar = explode(':', $hataMesaji);
            $hataMesaji = end($parcalar); // Mesajın en sonundaki temiz kısmı al
        }

        // Temizlenen hatayı ekrana bas
        $alertScript = "Swal.fire({ 
            icon: 'error', 
            title: 'İşlem Başarısız!', 
            text: '" . addslashes(trim($hataMesaji)) . "' 
        });";
    }
}

// SAYFA YÜKLENİRKEN ARAÇ BİLGİLERİNİ ÇEK
$stmt = $db->prepare("CALL Sp_AracDetay(?)");
$stmt->execute([$arac_id]);
$arac = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= $arac['Model'] ?> - Kiralama Detayı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    
    <div class="container mt-5 mb-5">
        <a href="home.php" class="btn btn-secondary mb-3">← Listeye Dön</a>
        
        <div class="row bg-white p-4 rounded shadow">
            <div class="col-md-7">
                <img src="<?= $arac['ResimUrl'] ?>" 
                     class="img-fluid rounded w-100 shadow-sm" 
                     alt="<?= $arac['Model'] ?>"
                     onerror="this.onerror=null; this.src='https://placehold.co/600x400?text=Resim+Yok';">
                
                <div class="mt-4">
                    <h4 class="fw-bold">Araç Özellikleri</h4>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge bg-primary fs-6 p-2"><?= $arac['MarkaAdi'] ?></span>
                        <span class="badge bg-secondary fs-6 p-2"><?= $arac['ModelYili'] ?> Model</span>
                        <span class="badge bg-dark fs-6 p-2"><?= $arac['Plaka'] ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card border-0 bg-light p-4 rounded-3 h-100">
                    <h2 class="fw-bold mb-0"><?= $arac['MarkaAdi'] ?> <?= $arac['Model'] ?></h2>
                    <h3 class="text-success mt-2 mb-3" id="gunlukFiyat">
                        <?= number_format($arac['GunlukUcret'], 0, ',', '.') ?> ₺ 
                        <small class="text-muted fs-6">/ Günlük</small>
                    </h3>
                    <hr>
                    
                    <form method="post">
                        <input type="hidden" name="toplam_tutar" id="inputToplamTutar">
                        
                        <div class="mb-3">
                            <label class="fw-bold mb-1">Alış Tarihi</label>
                            <input type="date" name="alis" id="alis" class="form-control form-control-lg" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold mb-1">Teslim Tarihi</label>
                            <input type="date" name="teslim" id="teslim" class="form-control form-control-lg" required>
                        </div>

                        <div class="alert alert-primary text-center py-3">
                            <span class="d-block small text-uppercase fw-bold text-muted">Toplam Ödenecek Tutar</span>
                            <span id="gosterToplam" class="display-6 fw-bold">0</span> <span class="fs-4">₺</span>
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-lg shadow fw-bold py-3">
                            ✅ Kiralamayı Onayla
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
    // PHP'den gelen verileri JS değişkenlerine aktar
    const gunlukUcret = <?= $arac['GunlukUcret'] ?>;
    const alertScript = `<?= $alertScript ?>`;

    // Elemanları seç
    const alisInput = document.getElementById('alis');
    const teslimInput = document.getElementById('teslim');
    const gosterToplam = document.getElementById('gosterToplam');
    const inputToplamTutar = document.getElementById('inputToplamTutar');

    // Fiyat Hesaplama Fonksiyonu
    function hesapla() {
        const d1 = new Date(alisInput.value);
        const d2 = new Date(teslimInput.value);

        // Tarihler geçerli mi ve Teslim tarihi Alış tarihinden büyük mü?
        if (alisInput.value && teslimInput.value && d2 >= d1) {
            const diffTime = Math.abs(d2 - d1);
            // Gün farkını bul (En az 1 gün sayılması için Math.ceil kullanıyoruz)
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            
            // Eğer aynı gün seçildiyse en az 1 gün ücreti alalım
            if(diffDays === 0) diffDays = 1;

            const toplam = diffDays * gunlukUcret;
            
            // Ekrana yazdır (Binlik ayracı ile)
            gosterToplam.innerText = new Intl.NumberFormat('tr-TR').format(toplam);
            
            // Form ile gönderilecek inputa ham değeri yaz
            inputToplamTutar.value = toplam;
        } else {
            gosterToplam.innerText = "0";
            inputToplamTutar.value = 0;
        }
    }

    // Tarih değişince hesapla fonksiyonunu çalıştır
    alisInput.addEventListener('change', hesapla);
    teslimInput.addEventListener('change', hesapla);

    // SweetAlert uyarısı varsa çalıştır
    if (alertScript.trim() !== "") {
        eval(alertScript); // PHP'den gelen JS kodunu çalıştırır
    }
</script>

</body>
</html>
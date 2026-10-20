<?php
include 'db.php';
$alertScript = ""; // Uyarı mesajı için değişken

if ($_POST) {
    try {
        // ARTIK TEK BİR GÜVENLİ PROSEDÜR ÇAĞIRIYORUZ (TRANSACTION)
        // Bu prosedür hem kullanıcıyı hem müşteriyi ekler.
        // Hata olursa (örn: TC aynıysa) kullanıcıyı da eklemez (Rollback yapar).
        
        $stmt = $db->prepare("CALL Sp_TamKayit(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['kadi'], 
            $_POST['sifre'], 
            $_POST['email'], 
            $_POST['tel'],
            $_POST['ad'], 
            $_POST['soyad'], 
            $_POST['tc'], 
            $_POST['ehliyet']
        ]);
        
        // Başarılı ise SweetAlert
        $alertScript = "
        Swal.fire({
            icon: 'success',
            title: 'Kayıt Başarılı!',
            text: 'Hesabınız güvenle oluşturuldu. Giriş sayfasına yönlendiriliyorsunuz...',
            timer: 2000,
            showConfirmButton: false
        }).then(() => { window.location = 'login.php'; });";
        
    } catch (PDOException $e) {
        // SQL'den gelen hatayı yakala
        $hataMesaji = $e->getMessage();

        // Teknik SQL mesajını temizle (Kullanıcıya 'Duplicate entry' yerine düzgün mesaj göstermek için)
        if (strpos($hataMesaji, 'Duplicate entry') !== false) {
            if (strpos($hataMesaji, 'TcNo') !== false) {
                $hataMesaji = "Bu TC Kimlik Numarası zaten kayıtlı!";
            } elseif (strpos($hataMesaji, 'KullaniciAdi') !== false) {
                $hataMesaji = "Bu Kullanıcı Adı zaten kullanılıyor!";
            } else {
                $hataMesaji = "Bu bilgilerle daha önce kayıt olunmuş.";
            }
        } else {
            // SQLSTATE kodlarını temizle
            if (strpos($hataMesaji, 'SQLSTATE') !== false) {
                $parcalar = explode(':', $hataMesaji);
                $hataMesaji = end($parcalar);
            }
        }

        $msg = addslashes(trim($hataMesaji));
        $alertScript = "Swal.fire({ icon: 'error', title: 'Kayıt Başarısız!', text: '$msg' });";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light d-flex align-items-center py-5" style="min-height: 100vh">

<div class="container" style="max-width: 700px;">
    <div class="card shadow-lg border-0">
        <div class="card-body p-5">
            <h3 class="text-center fw-bold text-primary mb-4">Yeni Hesap Oluştur</h3>
            
            <form method="post" class="row g-3 needs-validation">
                <div class="col-md-6">
                    <label class="form-label">Ad</label>
                    <input type="text" name="ad" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Soyad</label>
                    <input type="text" name="soyad" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">TC Kimlik No</label>
                    <input type="text" name="tc" class="form-control" maxlength="11" minlength="11" pattern="\d*" title="11 Haneli TC giriniz" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ehliyet No</label>
                    <input type="text" name="ehliyet" class="form-control" required>
                </div>

                <hr class="my-4">

                <div class="col-md-6">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" name="kadi" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-Posta</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="tel" class="form-control" placeholder="0555..." required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="sifre" class="form-control" minlength="4" required>
                </div>

                <div class="col-12 mt-4">
                    <button class="btn btn-success w-100 py-2 fw-bold fs-5">Kaydı Tamamla</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">Zaten hesabın var mı? Giriş Yap</a>
            </div>
        </div>
    </div>
</div>

<script>
    <?php echo $alertScript; ?>
</script>

</body>
</html>
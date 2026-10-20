<?php
session_start();
include 'db.php';

if ($_POST) {
    // Sadece Prosedürü Çağırıyoruz
    $sql = $db->prepare("CALL Sp_GirisYap(?, ?)");
    $sql->execute([$_POST['kadi'], $_POST['sifre']]);
    $user = $sql->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['id'] = $user['KullaniciId'];
        $_SESSION['rol'] = $user['Rol'];
        $_SESSION['ad'] = $user['KullaniciAdi'];
        
        if($user['Rol'] == 'admin') { header("Location: admin.php"); }
        else { header("Location: home.php"); }
        exit;
    } else {
        $hata = "Hatalı giriş!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow p-4" style="width: 400px;">
        <h3 class="text-center mb-3">Giriş Yap</h3>
        <?php if(isset($hata)) echo "<div class='alert alert-danger'>$hata</div>"; ?>
        <form method="post">
            <input type="text" name="kadi" class="form-control mb-3" placeholder="Kullanıcı Adı" required>
            <input type="password" name="sifre" class="form-control mb-3" placeholder="Şifre" required>
            <button class="btn btn-primary w-100">Giriş</button>
        </form>
        <div class="text-center mt-3"><a href="register.php">Kayıt Ol</a></div>
        <div class="text-center mt-2"><a href="index.php">Ana Sayfaya Dön</a></div>
    </div>
</body>
</html>
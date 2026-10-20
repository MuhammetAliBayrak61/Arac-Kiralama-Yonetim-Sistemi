<?php 
// Oturum durumunu PHP session'dan kontrol ediyoruz
$girisYapildi = isset($_SESSION['id']); 
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 shadow">
  <div class="container">
    <a class="navbar-brand fw-bold" href="home.php">
        <i class="fa fa-car-side text-warning"></i> RentACar
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        
        <?php if(!$girisYapildi): ?>
            <li class="nav-item"><a class="nav-link" href="home.php">Araçlar</a></li>
            <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
            <li class="nav-item"><a class="btn btn-primary btn-sm ms-2" href="register.php">Kayıt Ol</a></li>
        
        <?php else: ?>
            <li class="nav-item me-3">
                <span class="nav-link text-white">
                    <i class="fa fa-user"></i> Hoşgeldin, <strong><?php echo $_SESSION['ad']; ?></strong>
                </span>
            </li>
            <li class="nav-item me-2">
                <a class="btn btn-outline-warning btn-sm rounded-pill px-3" href="kiralanan_araclar.php">
                    <i class="fa fa-history"></i> Kiralanan Araçlar
                </a>
            </li>
            <li class="nav-item">
                <a class="btn btn-danger btn-sm rounded-pill px-3" href="logout.php">
                    <i class="fa fa-sign-out-alt"></i> Çıkış
                </a>
            </li>
        <?php endif; ?>
        
      </ul>
    </div>
  </div>
</nav>
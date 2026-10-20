<?php
session_start();
include 'db.php';

// Güvenlik: Admin değilse at
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') { 
    header("Location: login.php"); 
    exit; 
}

// --- İŞLEMLER (SADECE CALL) ---

// 1. ARAÇ İŞLEMLERİ
if (isset($_POST['arac_ekle'])) {
    try {
        $stmt = $db->prepare("CALL Sp_AracEkle(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['plaka'], $_POST['marka_id'], $_POST['model'], $_POST['yil'], $_POST['fiyat'], $_POST['resim']]);
        
        session_write_close(); 
        header("Location: admin.php?tab=araclar&status=ok");
        exit;
    } catch (Exception $e) { die("Hata: " . $e->getMessage()); }
}

if (isset($_POST['arac_guncelle'])) {
    try {
        $stmt = $db->prepare("CALL Sp_AracGuncelle(?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['arac_id'], $_POST['plaka'], $_POST['marka_id'], $_POST['model'], $_POST['yil'], $_POST['fiyat'], $_POST['resim']]);
        
        session_write_close();
        header("Location: admin.php?tab=araclar&status=updated");
        exit;
    } catch (Exception $e) { die("Hata: " . $e->getMessage()); }
}

if (isset($_GET['sil_arac'])) {
    $db->prepare("CALL Sp_AracSil(?)")->execute([$_GET['sil_arac']]);
    session_write_close();
    header("Location: admin.php?tab=araclar");
    exit;
}

// 2. KİRALAMA İŞLEMLERİ
if (isset($_GET['iptal_kira'])) {
    $db->prepare("CALL Sp_KiralamaIptal(?, ?)")->execute([$_GET['iptal_kira'], $_GET['arac_id']]);
    session_write_close();
    header("Location: admin.php?tab=kiralamalar");
    exit;
}

// 3. KULLANICI İŞLEMLERİ
if (isset($_GET['sil_user'])) {
    $db->prepare("CALL Sp_KullaniciSil(?)")->execute([$_GET['sil_user']]);
    session_write_close();
    header("Location: admin.php?tab=kullanicilar");
    exit;
}

// 4. MARKA İŞLEMLERİ
if (isset($_POST['marka_ekle'])) {
    $stmt = $db->prepare("CALL Sp_MarkaEkle(?)");
    $stmt->execute([$_POST['marka_adi']]);
    session_write_close();
    header("Location: admin.php?tab=markalar");
    exit;
}

if (isset($_GET['sil_marka'])) {
    $stmt = $db->prepare("CALL Sp_MarkaSil(?)");
    $stmt->execute([$_GET['sil_marka']]);
    session_write_close();
    header("Location: admin.php?tab=markalar");
    exit;
}

// 5. ADMİN EKLEME
if (isset($_POST['admin_ekle'])) {
    $stmt = $db->prepare("CALL Sp_AdminEkle(?, ?, ?, ?)");
    $stmt->execute([$_POST['ad'], $_POST['email'], $_POST['sifre'], $_POST['telefon']]);
    session_write_close();
    header("Location: admin.php?tab=kullanicilar");
    exit;
}

// --- VERİ ÇEKME (SADECE CALL) ---

// Araçlar
$stmt = $db->query("CALL Sp_AdminAraclar()");
$araclar = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); // Prosedürden sonra imleci kapatmak şarttır

// Kullanıcılar
$stmt = $db->query("CALL Sp_AdminKullanicilar()");
$kullanicilar = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// Kiralamalar
$stmt = $db->query("CALL Sp_AdminKiralamalar()");
$kiralamalar = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// Markalar (DÜZELTİLDİ: Artık burası da prosedür)
$stmt = $db->query("CALL Sp_MarkalariListele()");
$markalar = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-sidebar .nav-link { color: #333; font-weight: 500; }
        .admin-sidebar .nav-link.active { background-color: #0d6efd; color: white !important; }
        .admin-sidebar .nav-link:hover:not(.active) { background-color: #e9ecef; }
        
        /* Dönen yükleme işaretlerini zorla gizle */
        .spinner, .loader, .preloader { display: none !important; opacity: 0 !important; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="admin-sidebar p-3 d-flex flex-column bg-white shadow-sm" style="width: 260px; min-height: 100vh;">
        <h3 class="text-center mb-4 fw-bold text-primary"><i class="fas fa-user-shield"></i> Admin</h3>
        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
            <button class="nav-link active text-start py-3" data-bs-toggle="pill" data-bs-target="#tab-ozet"><i class="fas fa-home me-2"></i> Özet</button>
            <button class="nav-link text-start py-3" data-bs-toggle="pill" data-bs-target="#tab-araclar"><i class="fas fa-car me-2"></i> Araç Yönetimi</button>
            <button class="nav-link text-start py-3" data-bs-toggle="pill" data-bs-target="#tab-markalar"><i class="fas fa-tags me-2"></i> Marka Yönetimi</button>
            <button class="nav-link text-start py-3" data-bs-toggle="pill" data-bs-target="#tab-kullanicilar"><i class="fas fa-users me-2"></i> Kullanıcılar & Admin</button>
            <button class="nav-link text-start py-3" data-bs-toggle="pill" data-bs-target="#tab-kiralamalar"><i class="fas fa-file-invoice-dollar me-2"></i> Kiralamalar</button>
            <a href="logout.php" class="nav-link text-danger mt-5 text-start"><i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap</a>
        </div>
    </div>

    <div class="flex-fill p-4 bg-light overflow-auto" style="height: 100vh;">
        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="tab-ozet">
                <h2 class="mb-4">Panel Özeti</h2>
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white p-3 mb-3">
                            <h4><i class="fas fa-car"></i> Araçlar</h4>
                            <h1><?= count($araclar) ?></h1>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white p-3 mb-3">
                            <h4><i class="fas fa-users"></i> Kullanıcılar</h4>
                            <h1><?= count($kullanicilar) ?></h1>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark p-3 mb-3">
                            <h4><i class="fas fa-key"></i> Kiralamalar</h4>
                            <h1><?= count($kiralamalar) ?></h1>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white p-3 mb-3">
                            <h4><i class="fas fa-tags"></i> Markalar</h4>
                            <h1><?= count($markalar) ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-araclar">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Araç Listesi</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAracEkle"><i class="fas fa-plus"></i> Yeni Araç</button>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-dark"><tr><th>Resim</th><th>Model</th><th>Plaka</th><th>Fiyat</th><th>Durum</th><th>İşlem</th></tr></thead>
                            <tbody>
                                <?php foreach($araclar as $a): ?>
                                <tr>
                                    <td><img src="<?= $a['ResimUrl'] ?>" width="60" class="rounded" onerror="this.src='https://placehold.co/600x400?text=Resim+Yok'"></td>
                                    <td><?= $a['MarkaAdi'] . " " . $a['Model'] ?></td>
                                    <td><?= $a['Plaka'] ?></td>
                                    <td><?= $a['GunlukUcret'] ?> ₺</td>
                                    <td><?= $a['Durum']==1 ? '<span class="badge bg-success">Müsait</span>' : '<span class="badge bg-danger">Kirada</span>' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning me-1" onclick="duzenleModal('<?= $a['AracId'] ?>','<?= $a['Plaka'] ?>','<?= $a['MarkaId'] ?>','<?= $a['Model'] ?>','<?= $a['ModelYili'] ?>','<?= $a['GunlukUcret'] ?>','<?= $a['ResimUrl'] ?>')"><i class="fas fa-edit"></i></button>
                                        <a href="?sil_arac=<?= $a['AracId'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-markalar">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-dark text-white"><i class="fas fa-plus"></i> Yeni Marka Ekle</div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label>Marka Adı</label>
                                        <input type="text" name="marka_adi" class="form-control" placeholder="Örn: Tesla" required>
                                    </div>
                                    <button type="submit" name="marka_ekle" class="btn btn-success w-100">Kaydet</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white fw-bold">Mevcut Markalar</div>
                            <div class="card-body p-0">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light"><tr><th>ID</th><th>Marka Adı</th><th>İşlem</th></tr></thead>
                                    <tbody>
                                        <?php foreach($markalar as $m): ?>
                                        <tr>
                                            <td><?= $m['MarkaId'] ?></td>
                                            <td class="fw-bold"><?= $m['MarkaAdi'] ?></td>
                                            <td>
                                                <a href="?sil_marka=<?= $m['MarkaId'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Markayı silmek istediğine emin misin?')"><i class="fas fa-trash"></i> Sil</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-kullanicilar">
                <div class="row mb-4">
                    <div class="col-12">
                        <button class="btn btn-dark float-end" type="button" data-bs-toggle="collapse" data-bs-target="#adminEkleForm">
                            <i class="fas fa-user-plus"></i> Yeni Yönetici (Admin) Ekle
                        </button>
                        <h3 class="d-inline-block">Kullanıcı Listesi</h3>
                    </div>
                </div>

                <div class="collapse mb-4" id="adminEkleForm">
                    <div class="card card-body bg-light border-warning">
                        <h5 class="text-warning"><i class="fas fa-user-shield"></i> Yeni Admin Kaydı</h5>
                        <form method="POST" class="row g-3">
                            <div class="col-md-3"><input type="text" name="ad" class="form-control" placeholder="Ad Soyad" required></div>
                            <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="E-Posta" required></div>
                            <div class="col-md-2"><input type="text" name="telefon" class="form-control" placeholder="Telefon"></div>
                            <div class="col-md-2"><input type="text" name="sifre" class="form-control" placeholder="Şifre" required></div>
                            <div class="col-md-2"><button type="submit" name="admin_ekle" class="btn btn-warning w-100 fw-bold">Admin Yap</button></div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead class="table-dark"><tr><th>ID</th><th>Ad Soyad</th><th>Email</th><th>Rol</th><th>Kayıt</th><th>İşlem</th></tr></thead>
                            <tbody>
                                <?php foreach($kullanicilar as $k): ?>
                                <tr>
                                    <td><?= $k['KullaniciId'] ?></td>
                                    <td><?= $k['KullaniciAdi'] ?></td>
                                    <td><?= $k['Email'] ?></td>
                                    <td>
                                        <?php if($k['Rol']=='admin'): ?>
                                            <span class="badge bg-danger"><i class="fas fa-shield-alt"></i> ADMIN</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">ÜYE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $k['KayitTarihi'] ?></td>
                                    <td>
                                        <?php if($k['Rol'] != 'admin'): ?>
                                        <a href="?sil_user=<?= $k['KullaniciId'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Kullanıcıyı silmek istiyor musun?')"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-kiralamalar">
                <h3>Kiralama Geçmişi</h3>
                <div class="card shadow-sm mt-3">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark"><tr><th>Müşteri</th><th>Araç</th><th>Tarihler</th><th>Tutar</th><th>İşlem</th></tr></thead>
                            <tbody>
                                <?php foreach($kiralamalar as $kira): ?>
                                <tr>
                                    <td><?= $kira['Ad'] . " " . $kira['Soyad'] ?></td>
                                    <td><?= $kira['Plaka'] ?> - <?= $kira['Model'] ?></td>
                                    <td><small><?= date("d.m.Y", strtotime($kira['AlisTarihi'])) ?> <br> <?= date("d.m.Y", strtotime($kira['TeslimTarihi'])) ?></small></td>
                                    <td class="fw-bold text-success"><?= $kira['ToplamTutar'] ?> ₺</td>
                                    <td>
                                        <a href="?iptal_kira=<?= $kira['KiralamaId'] ?>&arac_id=<?= $kira['AracId'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('İptal edilsin mi?')"><i class="fas fa-times"></i> İptal</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalAracEkle" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Yeni Araç Ekle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="post">
            <div class="mb-2">
                <label>Marka</label>
                <select name="marka_id" class="form-select">
                    <?php foreach($markalar as $m): ?>
                        <option value="<?= $m['MarkaId'] ?>"><?= $m['MarkaAdi'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="text" name="plaka" class="form-control mb-2" placeholder="Plaka (34 AB 123)" required>
            <input type="text" name="model" class="form-control mb-2" placeholder="Model (Örn: A3 Sedan)" required>
            <input type="number" name="yil" class="form-control mb-2" placeholder="Yıl" required>
            <input type="number" name="fiyat" class="form-control mb-2" placeholder="Günlük Fiyat" required>
            <input type="text" name="resim" class="form-control mb-2" placeholder="Resim URL" required>
            <button type="submit" name="arac_ekle" class="btn btn-success w-100 mt-3">Kaydet</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAracDuzenle" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Araç Düzenle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="post">
            <input type="hidden" name="arac_id" id="edit_id">
            <label>Marka</label>
            <select name="marka_id" id="edit_marka" class="form-select mb-2">
                <?php foreach($markalar as $m): ?>
                    <option value="<?= $m['MarkaId'] ?>"><?= $m['MarkaAdi'] ?></option>
                <?php endforeach; ?>
            </select>
            <label>Plaka</label>
            <input type="text" name="plaka" id="edit_plaka" class="form-control mb-2" required>
            <label>Model</label>
            <input type="text" name="model" id="edit_model" class="form-control mb-2" required>
            <label>Yıl</label>
            <input type="number" name="yil" id="edit_yil" class="form-control mb-2" required>
            <label>Fiyat</label>
            <input type="number" name="fiyat" id="edit_fiyat" class="form-control mb-2" required>
            <label>Resim URL</label>
            <input type="text" name="resim" id="edit_resim" class="form-control mb-2" required>
            <button type="submit" name="arac_guncelle" class="btn btn-primary w-100 mt-2">Güncelle</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Tab Kontrolü
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if(tab){
        const triggerEl = document.querySelector(`button[data-bs-target="#tab-${tab}"]`);
        if(triggerEl) {
            const tabInstance = new bootstrap.Tab(triggerEl);
            tabInstance.show();
        }
    }

    // Modal Doldurma
    function duzenleModal(id, plaka, markaId, model, yil, fiyat, resim) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_plaka').value = plaka;
        document.getElementById('edit_marka').value = markaId;
        document.getElementById('edit_model').value = model;
        document.getElementById('edit_yil').value = yil;
        document.getElementById('edit_fiyat').value = fiyat;
        document.getElementById('edit_resim').value = resim;
        
        var myModal = new bootstrap.Modal(document.getElementById('modalAracDuzenle'));
        myModal.show();
    }
</script>

</body>
</html>
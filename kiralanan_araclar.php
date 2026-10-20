<?php
session_start();
include 'db.php'; 

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$kullanici_id = $_SESSION['id'];

// --- KULLANICI -> MÜŞTERİ EŞLEŞTİRMESİ ---
// Veritabanı şemana göre (image_346574.png)
$sqlMusteri = "SELECT MusteriId FROM musteriler WHERE KullaniciId = ?";
$stmtMusteri = $db->prepare($sqlMusteri);
$stmtMusteri->execute([$kullanici_id]);
$musteri = $stmtMusteri->fetch(PDO::FETCH_ASSOC);

// Eğer müşteri kaydı yoksa (örn: admin girişi), hata vermesin diye id'yi aynen kullan
$gercek_musteri_id = $musteri ? $musteri['MusteriId'] : $kullanici_id;

$msg = "";

// --- İPTAL İŞLEMİ ---
if (isset($_POST['cancel_booking_id'])) {
    $kiralama_id = $_POST['cancel_booking_id'];

    try {
        // ARTIK SADECE SİLİYORUZ!
        // SQL Trigger (Tetikleyici) devreye girecek ve aracı otomatik boşa çıkaracak.
        $sqlDelete = "DELETE FROM kiralamalar WHERE KiralamaId = ? AND MusteriId = ?";
        $stmtDelete = $db->prepare($sqlDelete);
        $sonuc = $stmtDelete->execute([$kiralama_id, $gercek_musteri_id]);

        if ($sonuc) {
            $msg = "<div class='alert alert-success'>✅ Rezervasyon silindi. (Trigger aracı otomatik aktif etti.)</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Silme işlemi başarısız.</div>";
        }
    } catch (PDOException $e) {
        $msg = "<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kiralanan Araçlarım</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Kiralanan Araçlarım</h2>
        <?php echo $msg; ?>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Araç</th>
                            <th>Alış Tarihi</th>
                            <th>İade Tarihi</th>
                            <th>Toplam Tutar</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // --- LİSTELEME SORGUSU ---
                        // LEFT JOIN ile Marka tablosunu bağlıyoruz
                        $sql = "SELECT 
                                    k.KiralamaId, 
                                    k.AlisTarihi, 
                                    k.TeslimTarihi, 
                                    k.ToplamTutar,
                                    m.MarkaAdi, 
                                    a.Model 
                                FROM kiralamalar k 
                                JOIN araclar a ON k.AracId = a.AracId 
                                LEFT JOIN markalar m ON a.MarkaId = m.MarkaId
                                WHERE k.MusteriId = ? 
                                ORDER BY k.KiralamaId DESC";
                                
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$gercek_musteri_id]);
                        $kiralamalar = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($kiralamalar) > 0):
                            foreach($kiralamalar as $row):
                                $iade_tutari = $row['ToplamTutar'] / 2;
                                $marka = !empty($row['MarkaAdi']) ? $row['MarkaAdi'] : 'Marka Yok';
                        ?>
                        <tr>
                            <td>
                                <span class="fw-bold"><?php echo $marka; ?></span> 
                                <small class="text-muted"><?php echo $row['Model']; ?></small>
                            </td>
                            <td><?php echo date("d.m.Y", strtotime($row['AlisTarihi'])); ?></td>
                            <td><?php echo date("d.m.Y", strtotime($row['TeslimTarihi'])); ?></td>
                            
                            <td class="fw-bold text-primary"><?php echo number_format($row['ToplamTutar'], 0, ',', '.'); ?> ₺</td>
                            
                            <td>
                                <form method="POST" onsubmit="return confirmCancel(<?php echo $iade_tutari; ?>);">
                                    <input type="hidden" name="cancel_booking_id" value="<?php echo $row['KiralamaId']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i> İptal Et
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fa fa-info-circle fa-2x mb-3 d-block"></i>
                                    Aktif kiralama bulunamadı.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmCancel(refundAmount) {
        return confirm("İşlem geri alınamaz.\nİade Tutarı: " + refundAmount + " TL\nOnaylıyor musunuz?");
    }
    </script>
</body>
</html>
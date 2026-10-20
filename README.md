# 🚗 Araç Kiralama Yönetim Sistemi

PHP ve MySQL ile geliştirilen, kullanıcıların araçları inceleyip kiralama talebi oluşturabildiği ve yöneticilerin araç, marka, kullanıcı ve kiralama kayıtlarını yönetebildiği web uygulaması.

## Özellikler

- Kullanıcı kaydı, oturum açma ve kapatma
- Araçların listelenmesi, detaylarının görüntülenmesi
- Alış/teslim tarihleriyle araç kiralama işlemi
- Kullanıcının kiralamalarını görüntülemesi ve iptal etmesi
- Yönetici panelinden araç ekleme, güncelleme ve silme
- Marka, kullanıcı ve kiralama yönetimi
- MySQL saklı yordamları (*stored procedures*) ve tetikleyici (*trigger*) kullanımı

## Teknolojiler

- **Backend:** PHP, PDO
- **Veritabanı:** MySQL
- **Arayüz:** HTML, CSS, Bootstrap 5, JavaScript
- **Veritabanı programlama:** Stored Procedures, Trigger, Transaction

## Proje yapısı

```text
Arac-Kiralama-Sistemi/
├── index.php              # Karşılama sayfası
├── register.php           # Üyelik
├── login.php              # Giriş
├── logout.php             # Çıkış
├── home.php               # Araç listesi
├── arac_detay.php         # Araç detayları ve kiralama
├── kiralanan_araclar.php  # Kullanıcının kiralamaları
├── admin.php              # Yönetim paneli
├── navbar.php             # Ortak gezinme menüsü
├── style.css              # Stil dosyası
├── db.php                 # MySQL PDO bağlantısı
└── arac_kiralama.sql      # Veritabanı şeması ve yordamlar
```

## Yerel kurulum

1. PHP, MySQL ve bir web sunucusu içeren bir ortam kurun (ör. XAMPP). PHP için **pdo_mysql** eklentisinin etkin olduğundan emin olun.
2. Dosyaları web sunucusunun proje dizinine yerleştirin (ör. `htdocs/Arac-Kiralama-Sistemi/`).
3. phpMyAdmin üzerinden **`arac_kiralama`** adlı bir veritabanı oluşturup `arac_kiralama.sql` dosyasını içe aktarın. SQL yedeği saklı yordam ve tetikleyici içerdiğinden uyumlu MySQL sürümü ve uygun yetkiler gerektirir.
4. `db.php` içindeki `host`, `dbname`, `username` ve `password` değerlerini **yerel ortamınıza** göre düzenleyin. Repoya gerçek parola eklemeyin.
5. Web sunucunuzu ve MySQL'i başlatarak `http://localhost/Arac-Kiralama-Sistemi/index.php` adresini ziyaret edin (klasör adınıza göre URL değişebilir).

> **Veri gizliliği:** Bu depodaki SQL dosyasından orijinal kullanıcı, müşteri ve kiralama kayıtları çıkarılmıştır. Demo hesabı içermez; yeni hesap oluşturabilirsiniz. Yönetici rolünü vermek için ayrı, yetkili bir veritabanı işlemi gerekir.

## Güvenlik ve bilinen sınırlamalar

- Bu sürüm eğitim/portföy amaçlıdır; üretim kullanımına hazır değildir.
- Mevcut kayıt ve giriş akışı parolaları güvenli parola hashleme yaklaşımıyla işlemiyor. Canlı kullanıma geçmeden önce `password_hash()` / `password_verify()` ile PHP tarafında güvenli kimlik doğrulama ve buna uyumlu veritabanı akışı uygulanmalıdır.
- Formlar için CSRF koruması, ayrıntılı giriş doğrulaması, yetkilendirme denetimleri ve güvenli hata yönetimi gözden geçirilmelidir.
- Veritabanı bağlantı bilgileri geliştirme ortamına göre yapılandırılmalıdır.
- Proje otomatik testlerden geçirilmiş veya canlı sunucuda doğrulanmış değildir.

## Geliştirme fikirleri

- Güvenli parola saklama ve rol tabanlı yetkilendirme
- Rezervasyonlarda çakışma kontrolünü güçlendirme
- Araç arama, filtreleme ve sıralama
- Mobil uyum ve arayüz iyileştirmeleri
- Otomatik testler ve kurulumun konteynerleştirilmesi

## Geliştirici

[Muhammet Ali Bayrak](https://github.com/MuhammetAliBayrak61)

Bu proje ekip çalışması ise diğer katkı sağlayan geliştiriciler de eklenebilir.

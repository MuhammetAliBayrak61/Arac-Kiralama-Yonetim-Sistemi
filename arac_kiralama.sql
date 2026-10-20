-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 29 Ara 2025, 17:32:05
-- Sunucu sürümü: 9.1.0
-- PHP Sürümü: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `arac_kiralama`
--

DELIMITER $$
--
-- Yordamlar
--
DROP PROCEDURE IF EXISTS `Sp_AdminAraclar`$$
CREATE PROCEDURE `Sp_AdminAraclar` ()   BEGIN
    SELECT Araclar.*, Markalar.MarkaAdi FROM Araclar LEFT JOIN Markalar ON Araclar.MarkaId = Markalar.MarkaId;
END$$

DROP PROCEDURE IF EXISTS `Sp_AdminEkle`$$
CREATE PROCEDURE `Sp_AdminEkle` (IN `p_ad` VARCHAR(50), IN `p_email` VARCHAR(50), IN `p_sifre` VARCHAR(50), IN `p_tel` VARCHAR(15))   BEGIN
    INSERT INTO kullanicilar (KullaniciAdi, Email, Sifre, Telefon, Rol, KayitTarihi) 
    VALUES (p_ad, p_email, p_sifre, p_tel, 'admin', NOW());
END$$

DROP PROCEDURE IF EXISTS `Sp_AdminKiralamalar`$$
CREATE PROCEDURE `Sp_AdminKiralamalar` ()   BEGIN
    SELECT k.*, a.Plaka, a.Model, m.Ad, m.Soyad 
    FROM Kiralamalar k 
    JOIN Araclar a ON k.AracId = a.AracId 
    JOIN Musteriler m ON k.MusteriId = m.MusteriId
    ORDER BY k.AlisTarihi DESC;
END$$

DROP PROCEDURE IF EXISTS `Sp_AdminKullanicilar`$$
CREATE PROCEDURE `Sp_AdminKullanicilar` ()   BEGIN
    SELECT * FROM Kullanicilar;
END$$

DROP PROCEDURE IF EXISTS `Sp_AracDetay`$$
CREATE PROCEDURE `Sp_AracDetay` (IN `p_id` INT)   BEGIN
    SELECT Araclar.*, Markalar.MarkaAdi FROM Araclar JOIN Markalar ON Araclar.MarkaId = Markalar.MarkaId WHERE AracId = p_id;
END$$

DROP PROCEDURE IF EXISTS `Sp_AracEkle`$$
CREATE PROCEDURE `Sp_AracEkle` (IN `p_plaka` VARCHAR(20), IN `p_markaId` INT, IN `p_model` VARCHAR(50), IN `p_yil` INT, IN `p_ucret` DECIMAL(10,2), IN `p_resim` VARCHAR(255))   BEGIN
    INSERT INTO Araclar (Plaka, MarkaId, Model, ModelYili, GunlukUcret, ResimUrl) VALUES (p_plaka, p_markaId, p_model, p_yil, p_ucret, p_resim);
END$$

DROP PROCEDURE IF EXISTS `Sp_AracGuncelle`$$
CREATE PROCEDURE `Sp_AracGuncelle` (IN `p_id` INT, IN `p_plaka` VARCHAR(20), IN `p_markaId` INT, IN `p_model` VARCHAR(50), IN `p_yil` INT, IN `p_ucret` DECIMAL(10,2), IN `p_resim` VARCHAR(255))   BEGIN
    UPDATE Araclar 
    SET Plaka = p_plaka, MarkaId = p_markaId, Model = p_model, ModelYili = p_yil, GunlukUcret = p_ucret, ResimUrl = p_resim
    WHERE AracId = p_id;
END$$

DROP PROCEDURE IF EXISTS `Sp_AraclariListele`$$
CREATE PROCEDURE `Sp_AraclariListele` ()   BEGIN
    SELECT 
        a.*, 
        m.MarkaAdi,
        -- Araca ait en ileri tarihli aktif kiralama bitiş tarihini buluyoruz
        (SELECT MAX(TeslimTarihi) FROM kiralamalar k WHERE k.AracId = a.AracId AND k.TeslimTarihi >= CURDATE()) as TahminiTeslim
    FROM araclar a 
    JOIN markalar m ON a.MarkaId = m.MarkaId 
    -- Artık Durum=1 şartını kaldırdık, hepsi gelsin.
    -- Sıralama: Önce müsaitler (1), sonra kiradakiler (0)
    ORDER BY a.Durum DESC, a.AracId DESC;
END$$

DROP PROCEDURE IF EXISTS `Sp_AracSil`$$
CREATE PROCEDURE `Sp_AracSil` (IN `p_id` INT)   BEGIN
    DELETE FROM Araclar WHERE AracId = p_id;
END$$

DROP PROCEDURE IF EXISTS `Sp_GirisYap`$$
CREATE PROCEDURE `Sp_GirisYap` (IN `p_kadi` VARCHAR(50), IN `p_sifre` VARCHAR(50))   BEGIN
    SELECT * FROM Kullanicilar WHERE KullaniciAdi = p_kadi AND Sifre = p_sifre;
END$$

DROP PROCEDURE IF EXISTS `Sp_KiralamaIptal`$$
CREATE PROCEDURE `Sp_KiralamaIptal` (IN `p_kiralamaId` INT, IN `p_aracId` INT)   BEGIN
    DELETE FROM Kiralamalar WHERE KiralamaId = p_kiralamaId;
    UPDATE Araclar SET Durum = 1 WHERE AracId = p_aracId;
END$$

DROP PROCEDURE IF EXISTS `Sp_KiralamaYap`$$
CREATE PROCEDURE `Sp_KiralamaYap` (IN `p_aracId` INT, IN `p_musteriId` INT, IN `p_alis` DATE, IN `p_teslim` DATE, IN `p_tutar` DECIMAL(10,2))   BEGIN
    -- 1. KURAL: Geçmiş tarihe kiralama yapılamaz
    IF p_alis < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Hata: Geçmiş bir tarihe rezervasyon yapamazsınız!';
    END IF;

    -- 2. KURAL: Teslim tarihi, alış tarihinden önce olamaz
    IF p_teslim < p_alis THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Hata: Teslim tarihi, alış tarihinden önce olamaz!';
    END IF;

    -- 3. KURAL: Tutar 0 veya negatif olamaz
    IF p_tutar <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Hata: Geçersiz tutar! Lütfen tarihleri kontrol ediniz.';
    END IF;

    -- KONTROLLERDEN GEÇERSE KAYIT YAP
    INSERT INTO Kiralamalar (AracId, MusteriId, AlisTarihi, TeslimTarihi, ToplamTutar) 
    VALUES (p_aracId, p_musteriId, p_alis, p_teslim, p_tutar);
    
    -- Aracı 'Kirada' (0) durumuna çek
    UPDATE Araclar SET Durum = 0 WHERE AracId = p_aracId;
    
END$$

DROP PROCEDURE IF EXISTS `Sp_KullaniciEkle`$$
CREATE PROCEDURE `Sp_KullaniciEkle` (IN `p_kadi` VARCHAR(50), IN `p_sifre` VARCHAR(50), IN `p_email` VARCHAR(50), IN `p_tel` VARCHAR(15))   BEGIN
    INSERT INTO Kullanicilar (KullaniciAdi, Sifre, Email, Telefon, Rol) VALUES (p_kadi, p_sifre, p_email, p_tel, 'uye');
    SELECT LAST_INSERT_ID() as YeniId;
END$$

DROP PROCEDURE IF EXISTS `Sp_KullaniciSil`$$
CREATE PROCEDURE `Sp_KullaniciSil` (IN `p_kullaniciId` INT)   BEGIN
    DELETE FROM Kullanicilar WHERE KullaniciId = p_kullaniciId;
END$$

DROP PROCEDURE IF EXISTS `Sp_MarkaEkle`$$
CREATE PROCEDURE `Sp_MarkaEkle` (IN `p_markaAdi` VARCHAR(50))   BEGIN
    INSERT INTO markalar (MarkaAdi) VALUES (p_markaAdi);
END$$

DROP PROCEDURE IF EXISTS `Sp_MarkalariListele`$$
CREATE PROCEDURE `Sp_MarkalariListele` ()   BEGIN
    SELECT * FROM markalar ORDER BY MarkaAdi ASC;
END$$

DROP PROCEDURE IF EXISTS `Sp_MarkaSil`$$
CREATE PROCEDURE `Sp_MarkaSil` (IN `p_markaId` INT)   BEGIN
    DELETE FROM markalar WHERE MarkaId = p_markaId;
END$$

DROP PROCEDURE IF EXISTS `Sp_MusteriBul`$$
CREATE PROCEDURE `Sp_MusteriBul` (IN `p_kullaniciId` INT)   BEGIN
    SELECT MusteriId FROM Musteriler WHERE KullaniciId = p_kullaniciId;
END$$

DROP PROCEDURE IF EXISTS `Sp_MusteriEkle`$$
CREATE PROCEDURE `Sp_MusteriEkle` (IN `p_uid` INT, IN `p_ad` VARCHAR(50), IN `p_soyad` VARCHAR(50), IN `p_tc` VARCHAR(11), IN `p_tel` VARCHAR(15), IN `p_ehliyet` VARCHAR(20))   BEGIN
    INSERT INTO Musteriler (KullaniciId, Ad, Soyad, TcNo, Telefon, EhliyetNo) VALUES (p_uid, p_ad, p_soyad, p_tc, p_tel, p_ehliyet);
END$$

DROP PROCEDURE IF EXISTS `Sp_RastgeleAracGetir`$$
CREATE PROCEDURE `Sp_RastgeleAracGetir` ()   BEGIN
    SELECT 
        a.*, 
        m.MarkaAdi,
        (SELECT MAX(TeslimTarihi) FROM kiralamalar k WHERE k.AracId = a.AracId AND k.TeslimTarihi >= CURDATE()) as TahminiTeslim
    FROM araclar a 
    JOIN markalar m ON a.MarkaId = m.MarkaId 
    ORDER BY RAND() 
    LIMIT 6;
END$$

DROP PROCEDURE IF EXISTS `Sp_TamKayit`$$
CREATE PROCEDURE `Sp_TamKayit` (IN `p_kadi` VARCHAR(50), IN `p_sifre` VARCHAR(50), IN `p_email` VARCHAR(50), IN `p_tel` VARCHAR(15), IN `p_ad` VARCHAR(50), IN `p_soyad` VARCHAR(50), IN `p_tc` VARCHAR(11), IN `p_ehliyet` VARCHAR(20))   BEGIN
    -- Hata Yakalayıcı: Herhangi bir hata olursa her şeyi geri al (ROLLBACK)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- İşlemi Başlat
    START TRANSACTION;
        -- 1. Kullanıcıyı Ekle
        INSERT INTO kullanicilar (KullaniciAdi, Sifre, Email, Telefon, Rol) 
        VALUES (p_kadi, p_sifre, p_email, p_tel, 'uye');
        
        -- Yeni Eklenen ID'yi Al
        SET @yeniId = LAST_INSERT_ID();

        -- 2. Müşteriyi Ekle
        INSERT INTO musteriler (KullaniciId, Ad, Soyad, TcNo, Telefon, EhliyetNo) 
        VALUES (@yeniId, p_ad, p_soyad, p_tc, p_tel, p_ehliyet);
    
    COMMIT; -- Her şey başarılıysa onayla
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `araclar`
--

DROP TABLE IF EXISTS `araclar`;
CREATE TABLE IF NOT EXISTS `araclar` (
  `AracId` int NOT NULL AUTO_INCREMENT,
  `Plaka` varchar(20) DEFAULT NULL,
  `MarkaId` int DEFAULT NULL,
  `Model` varchar(50) DEFAULT NULL,
  `ModelYili` int DEFAULT NULL,
  `GunlukUcret` decimal(10,2) DEFAULT NULL,
  `Durum` tinyint(1) DEFAULT '1',
  `ResimUrl` varchar(255) DEFAULT 'https://via.placeholder.com/400x250',
  PRIMARY KEY (`AracId`),
  UNIQUE KEY `Plaka` (`Plaka`),
  KEY `fk_arac_marka` (`MarkaId`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `araclar`
--

INSERT INTO `araclar` (`AracId`, `Plaka`, `MarkaId`, `Model`, `ModelYili`, `GunlukUcret`, `Durum`, `ResimUrl`) VALUES
(1, '34 FER 488', 23, '488 GTB', 2023, 25000.00, 0, 'https://images.unsplash.com/photo-1592198084033-aade902d1aae?auto=format&fit=crop&w=800&q=80'),
(2, '34 POR 911', 22, '911 Carrera', 2023, 15000.00, 1, 'https://images.unsplash.com/photo-1503376763036-066120622c74?auto=format&fit=crop&w=800&q=80'),
(3, '34 MER 200', 2, 'C200 AMG', 2023, 4700.00, 1, 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?auto=format&fit=crop&w=800&q=80'),
(4, '34 BMW 320', 1, '3.20i M Sport', 2023, 4500.00, 1, 'https://images.unsplash.com/photo-1555215695-3004980adade?auto=format&fit=crop&w=800&q=80'),
(5, '06 TES 01', 19, 'Model Y', 2024, 6000.00, 1, 'https://images.unsplash.com/photo-1619682817481-e994891cd1f5?auto=format&fit=crop&w=800&q=80'),
(6, '34 RNG 01', 21, 'Range Rover Sport', 2023, 12000.00, 1, 'https://images.unsplash.com/photo-1606220838315-056192d5e927?auto=format&fit=crop&w=800&q=80'),
(7, '34 TOG 10', 20, 'T10X V2', 2024, 3500.00, 1, 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/10/Togg_C-SUV_Concept.jpg/800px-Togg_C-SUV_Concept.jpg'),
(8, '34 EGE 01', 6, 'Egea Sedan', 2023, 1200.00, 1, 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=800&q=80'),
(9, '35 CLIO 35', 5, 'Clio 5', 2022, 1100.00, 1, 'https://images.unsplash.com/photo-1620591295669-b73e51296715?auto=format&fit=crop&w=800&q=80'),
(10, '06 COR 01', 8, 'Corolla Hybrid', 2023, 1800.00, 1, 'https://images.unsplash.com/photo-1623869675785-3e2843e6205b?auto=format&fit=crop&w=800&q=80'),
(11, '34 CIV 01', 9, 'Civic', 2022, 1900.00, 1, 'https://images.unsplash.com/photo-1605634568910-c40d99042b08?auto=format&fit=crop&w=800&q=80'),
(12, '16 FOC 16', 7, 'Focus', 2023, 1850.00, 1, 'https://images.unsplash.com/photo-1625832007804-9582d909287c?auto=format&fit=crop&w=800&q=80'),
(13, '34 GOLF 8', 4, 'Golf 8', 2023, 2000.00, 1, 'https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?auto=format&fit=crop&w=800&q=80'),
(14, '34 PAS 01', 4, 'Passat', 2021, 2800.00, 1, 'https://images.unsplash.com/photo-1588636254707-1bc548d7c438?auto=format&fit=crop&w=800&q=80'),
(15, '34 PGT 3008', 11, '3008 GT', 2023, 3200.00, 1, 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80'),
(16, '35 QAS 01', 13, 'Qashqai', 2023, 2900.00, 1, 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=800&q=80'),
(17, '06 TUC 01', 10, 'Tucson', 2023, 3100.00, 1, 'https://images.unsplash.com/photo-1622353347493-2703876d21e0?auto=format&fit=crop&w=800&q=80'),
(18, '34 VOL 90', 12, 'XC90', 2023, 8000.00, 1, 'https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=800&q=80'),
(19, '34 JEP 01', 24, 'Wrangler', 2022, 9000.00, 1, 'https://images.unsplash.com/photo-1533558231718-d731db597d26?auto=format&fit=crop&w=800&q=80'),
(20, '34 COR 01', 16, 'Corsa', 2023, 1300.00, 1, 'https://images.unsplash.com/photo-1542362567-b07e54358753?auto=format&fit=crop&w=800&q=80'),
(21, '34 OCT 01', 17, 'Octavia', 2023, 2100.00, 1, 'https://images.unsplash.com/photo-1541443131876-44b03de101c5?auto=format&fit=crop&w=800&q=80'),
(22, '34 LEO 01', 25, 'Leon FR', 2022, 2200.00, 1, 'https://images.unsplash.com/photo-1629896208422-99c585a06519?auto=format&fit=crop&w=800&q=80'),
(23, '34 MIN 01', 23, 'Cooper S', 2023, 2600.00, 1, 'https://images.unsplash.com/photo-1583121274602-3e2820c698d2?auto=format&fit=crop&w=800&q=80');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kiralamalar`
--

DROP TABLE IF EXISTS `kiralamalar`;
CREATE TABLE IF NOT EXISTS `kiralamalar` (
  `KiralamaId` int NOT NULL AUTO_INCREMENT,
  `AracId` int DEFAULT NULL,
  `MusteriId` int DEFAULT NULL,
  `AlisTarihi` datetime NOT NULL,
  `TeslimTarihi` datetime NOT NULL,
  `ToplamTutar` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`KiralamaId`),
  KEY `fk_kiralama_arac` (`AracId`),
  KEY `fk_kiralama_musteri` (`MusteriId`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `kiralamalar`
--

-- kiralamalar: orijinal kayitlar gizlilik nedeniyle cikartildi.

--
-- Tetikleyiciler `kiralamalar`
--
DROP TRIGGER IF EXISTS `trg_KiralamaSilininceAraciAc`;
DELIMITER $$
CREATE TRIGGER `trg_KiralamaSilininceAraciAc` AFTER DELETE ON `kiralamalar` FOR EACH ROW BEGIN
    -- Silinen kiralama kaydındaki AracId'yi al (OLD.AracId)
    -- Ve o aracın durumunu 1 (Aktif/Müsait) yap
    UPDATE araclar SET Durum = 1 WHERE AracId = OLD.AracId;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `KullaniciId` int NOT NULL AUTO_INCREMENT,
  `KullaniciAdi` varchar(50) NOT NULL,
  `Sifre` varchar(50) NOT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `Telefon` varchar(15) DEFAULT NULL,
  `Rol` varchar(10) DEFAULT 'uye',
  `KayitTarihi` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`KullaniciId`),
  UNIQUE KEY `KullaniciAdi` (`KullaniciAdi`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

-- kullanicilar: orijinal kayitlar gizlilik nedeniyle cikartildi.

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `markalar`
--

DROP TABLE IF EXISTS `markalar`;
CREATE TABLE IF NOT EXISTS `markalar` (
  `MarkaId` int NOT NULL AUTO_INCREMENT,
  `MarkaAdi` varchar(50) NOT NULL,
  PRIMARY KEY (`MarkaId`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `markalar`
--

INSERT INTO `markalar` (`MarkaId`, `MarkaAdi`) VALUES
(1, 'BMW'),
(2, 'Mercedes-Benz'),
(3, 'Audi'),
(4, 'Volkswagen'),
(5, 'Renault'),
(6, 'Fiat'),
(7, 'Ford'),
(8, 'Toyota'),
(9, 'Honda'),
(10, 'Hyundai'),
(11, 'Peugeot'),
(12, 'Volvo'),
(13, 'Nissan'),
(14, 'Kia'),
(15, 'Citroen'),
(16, 'Opel'),
(17, 'Skoda'),
(18, 'Dacia'),
(19, 'Tesla'),
(20, 'Togg'),
(21, 'Land Rover'),
(22, 'Porsche'),
(23, 'Ferrari'),
(24, 'Jeep'),
(25, 'Seat');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `musteriler`
--

DROP TABLE IF EXISTS `musteriler`;
CREATE TABLE IF NOT EXISTS `musteriler` (
  `MusteriId` int NOT NULL AUTO_INCREMENT,
  `KullaniciId` int DEFAULT NULL,
  `TcNo` varchar(11) DEFAULT NULL,
  `Ad` varchar(50) DEFAULT NULL,
  `Soyad` varchar(50) DEFAULT NULL,
  `Telefon` varchar(15) DEFAULT NULL,
  `EhliyetNo` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`MusteriId`),
  UNIQUE KEY `TcNo` (`TcNo`),
  KEY `fk_musteri_kullanici` (`KullaniciId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `musteriler`
--

-- musteriler: orijinal kayitlar gizlilik nedeniyle cikartildi.

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `araclar`
--
ALTER TABLE `araclar`
  ADD CONSTRAINT `fk_arac_marka` FOREIGN KEY (`MarkaId`) REFERENCES `markalar` (`MarkaId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `kiralamalar`
--
ALTER TABLE `kiralamalar`
  ADD CONSTRAINT `fk_kiralama_arac` FOREIGN KEY (`AracId`) REFERENCES `araclar` (`AracId`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kiralama_musteri` FOREIGN KEY (`MusteriId`) REFERENCES `musteriler` (`MusteriId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `musteriler`
--
ALTER TABLE `musteriler`
  ADD CONSTRAINT `fk_musteri_kullanici` FOREIGN KEY (`KullaniciId`) REFERENCES `kullanicilar` (`KullaniciId`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database.php';

class Menu
{
    public function yemekhaneleriGetir(): array
    {
        return Database::okuma()->query(
            'SELECT id, ad, kapasite FROM yemekhaneler ORDER BY id'
        )->fetchAll();
    }

    public function ogunleriGetir(): array
    {
        return Database::okuma()->query(
            'SELECT id, ad, son_saat FROM ogunler ORDER BY sira'
        )->fetchAll();
    }

    public function gununMenuDetayiniGetir(
        string $tarih,
        int $yemekhaneId,
        int $ogunId
    ): array {
        $db = Database::okuma();
        $sorgu = $db->prepare(
            'SELECT menu_id, tarih, yemekhane_id, yemekhane, ogun_id, ogun,
                    rezervasyon_son, ogrenci_fiyati, personel_fiyati,
                    yemek_id, yemek, kategori, kategori_sira, kalori,
                    vejetaryen, alerjenler
             FROM v_menu_detay
             WHERE tarih = :tarih
               AND yemekhane_id = :yemekhane_id
               AND ogun_id = :ogun_id
             ORDER BY kategori_sira, yemek'
        );

        $sorgu->execute([
            'tarih' => $tarih,
            'yemekhane_id' => $yemekhaneId,
            'ogun_id' => $ogunId,
        ]);

        return $sorgu->fetchAll();
    }

    /**
     * Haftalık menüyü, kapasite durumunu ve giriş yapan kullanıcının
     * rezervasyon durumunu replika üzerinden getirir.
     */
    public function haftalikMenuGetir(
        string $baslangicTarihi,
        int $yemekhaneId,
        int $kullaniciId
    ): array {
        $db = Database::okuma();

        $sorgu = $db->prepare(
            'SELECT
                d.menu_id,
                d.tarih,
                d.yemekhane_id,
                d.yemekhane,
                d.ogun_id,
                d.ogun,
                d.ogun_sira,
                d.rezervasyon_son,
                d.ogrenci_fiyati,
                d.personel_fiyati,
                d.yemek_id,
                d.yemek,
                d.kategori,
                d.kategori_sira,
                d.kalori,
                d.vejetaryen,
                d.alerjenler,
                dol.kapasite,
                dol.rezerve,
                dol.bos_yer,
                CASE WHEN d.rezervasyon_son > NOW() THEN 1 ELSE 0 END AS rezervasyon_acik,
                r.durum AS kullanici_rezervasyon_durumu
             FROM v_menu_detay d
             INNER JOIN v_menu_doluluk dol ON dol.menu_id = d.menu_id
             LEFT JOIN rezervasyonlar r
                    ON r.menu_id = d.menu_id
                   AND r.kullanici_id = :kullanici_id
             WHERE d.tarih BETWEEN :baslangic1
                               AND DATE_ADD(:baslangic2, INTERVAL 6 DAY)
               AND d.yemekhane_id = :yemekhane_id
             ORDER BY d.tarih, d.ogun_sira, d.kategori_sira, d.yemek'
        );

        $sorgu->execute([
            'kullanici_id' => $kullaniciId,
            'baslangic1' => $baslangicTarihi,
            'baslangic2' => $baslangicTarihi,
            'yemekhane_id' => $yemekhaneId,
        ]);

        return $sorgu->fetchAll();
    }
}

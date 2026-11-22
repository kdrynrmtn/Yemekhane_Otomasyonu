<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../RedisOnbellek.php';

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

    /**
     * Günlük menü Redis önbelleğini kullanır.
     * Önbellek anahtarı: menu:{yemekhane_id}:{tarih}:{ogun_id}
     */
    public function gununMenuDetayiniGetir(
        string $tarih,
        int $yemekhaneId,
        int $ogunId
    ): array {
        $anahtar = RedisOnbellek::menuAnahtariOlustur(
            $yemekhaneId,
            $tarih,
            $ogunId
        );

        $onbellekVerisi = RedisOnbellek::menuGetir($anahtar);
        if ($onbellekVerisi !== null) {
            return $onbellekVerisi;
        }

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

        $sonuc = $sorgu->fetchAll();
        RedisOnbellek::menuKaydet($anahtar, $sonuc, $tarih);

        return $sonuc;
    }

    /**
     * Haftalık menü satırlarını, menü içeriğini Redis'ten;
     * kapasite ve kullanıcının rezervasyon durumunu replikadan getirir.
     * Böylece sık değişen doluluk bilgileri önbellekte eski kalmaz.
     */
    public function haftalikMenuGetir(
        string $baslangicTarihi,
        int $yemekhaneId,
        int $kullaniciId
    ): array {
        $db = Database::okuma();
        $ogunler = $this->ogunleriGetir();

        $dinamikBilgiSorgu = $db->prepare(
            'SELECT
                dol.kapasite,
                dol.rezerve,
                dol.bos_yer,
                CASE
                    WHEN dol.rezervasyon_son > CURRENT_TIMESTAMP THEN 1
                    ELSE 0
                END AS rezervasyon_acik,
                r.durum AS kullanici_rezervasyon_durumu
             FROM v_menu_doluluk dol
             LEFT JOIN rezervasyonlar r
                    ON r.menu_id = dol.menu_id
                   AND r.kullanici_id = :kullanici_id
             WHERE dol.menu_id = :menu_id'
        );

        $baslangic = new DateTimeImmutable($baslangicTarihi, new DateTimeZone('Europe/Istanbul'));
        $sonuc = [];

        for ($gunNo = 0; $gunNo < 7; $gunNo++) {
            $tarih = $baslangic->modify('+' . $gunNo . ' days')->format('Y-m-d');

            foreach ($ogunler as $ogun) {
                $ogunId = (int)$ogun['id'];
                $menuSatirlari = $this->gununMenuDetayiniGetir(
                    $tarih,
                    $yemekhaneId,
                    $ogunId
                );

                if ($menuSatirlari === []) {
                    continue;
                }

                $menuId = (int)$menuSatirlari[0]['menu_id'];

                $dinamikBilgiSorgu->execute([
                    'kullanici_id' => $kullaniciId,
                    'menu_id' => $menuId,
                ]);
                $dinamikBilgi = $dinamikBilgiSorgu->fetch();

                if (!$dinamikBilgi) {
                    continue;
                }

                foreach ($menuSatirlari as $satir) {
                    $sonuc[] = array_merge($satir, $dinamikBilgi);
                }
            }
        }

        return $sonuc;
    }
}

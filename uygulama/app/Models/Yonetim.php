<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../RedisOnbellek.php';

class Yonetim
{
    /** Form seçeneklerini ve menü raporlarını replikadan getirir. */
    public function formVerileriniGetir(): array
    {
        $db = Database::okuma();

        return [
            'yemekhaneler' => $db->query(
                'SELECT id, ad, kapasite FROM yemekhaneler ORDER BY ad'
            )->fetchAll(),
            'ogunler' => $db->query(
                'SELECT id, ad, son_saat FROM ogunler ORDER BY sira'
            )->fetchAll(),
            'yemekler' => $db->query(
                'SELECT y.id, y.ad, k.ad AS kategori, k.sira AS kategori_sira
                 FROM yemekler y
                 INNER JOIN yemek_kategorileri k ON k.id = y.kategori_id
                 ORDER BY k.sira, y.ad'
            )->fetchAll(),
        ];
    }

    /** Doluluk ve ortalama puan raporları replikadan alınır. */
    public function menuRaporlariniGetir(): array
    {
        $db = Database::okuma();
        $sorgu = $db->query(
            'SELECT
                m.id AS menu_id,
                m.tarih,
                m.yemekhane_id,
                yh.ad AS yemekhane,
                m.ogun_id,
                o.ad AS ogun,
                m.ogrenci_fiyati,
                m.personel_fiyati,
                dol.kapasite,
                dol.rezerve,
                dol.bos_yer,
                dol.doluluk_yuzde,
                puan.oy_sayisi,
                puan.ortalama_puan,
                (SELECT GROUP_CONCAT(y.ad ORDER BY k.sira, y.ad SEPARATOR ", ")
                   FROM menu_yemekleri my
                   INNER JOIN yemekler y ON y.id = my.yemek_id
                   INNER JOIN yemek_kategorileri k ON k.id = y.kategori_id
                  WHERE my.menu_id = m.id) AS yemekler
             FROM menuler m
             INNER JOIN yemekhaneler yh ON yh.id = m.yemekhane_id
             INNER JOIN ogunler o ON o.id = m.ogun_id
             LEFT JOIN v_menu_doluluk dol ON dol.menu_id = m.id
             LEFT JOIN v_menu_puan puan ON puan.menu_id = m.id
             ORDER BY m.tarih DESC, o.sira, yh.ad'
        );

        return $sorgu->fetchAll();
    }

    /** Düzenleme formu için menüyü ve seçili yemek ID'lerini replikadan getirir. */
    public function menuDuzenlemeBilgisiGetir(int $menuId): ?array
    {
        $db = Database::okuma();
        $menuSorgu = $db->prepare(
            'SELECT id, yemekhane_id, tarih, ogun_id, ogrenci_fiyati, personel_fiyati
             FROM menuler
             WHERE id = :id'
        );
        $menuSorgu->execute(['id' => $menuId]);
        $menu = $menuSorgu->fetch();

        if (!$menu) {
            return null;
        }

        $yemekSorgu = $db->prepare(
            'SELECT yemek_id FROM menu_yemekleri WHERE menu_id = :menu_id'
        );
        $yemekSorgu->execute(['menu_id' => $menuId]);
        $menu['yemek_ids'] = array_map(
            'intval',
            array_column($yemekSorgu->fetchAll(), 'yemek_id')
        );

        return $menu;
    }

    /** Menü oluşturma/düzenleme işlemlerini kaynak MySQL üzerinde yapar. */
    public function menuKaydet(
        ?int $menuId,
        int $yemekhaneId,
        string $tarih,
        int $ogunId,
        string $ogrenciFiyati,
        string $personelFiyati,
        array $yemekIds
    ): int {
        $tarihNesnesi = DateTime::createFromFormat('!Y-m-d', $tarih);
        if (!$tarihNesnesi || $tarihNesnesi->format('Y-m-d') !== $tarih) {
            throw new InvalidArgumentException('Geçerli bir menü tarihi seçin.');
        }

        $ogrenciFiyati = $this->fiyatDogrula($ogrenciFiyati, 'Öğrenci fiyatı');
        $personelFiyati = $this->fiyatDogrula($personelFiyati, 'Personel fiyatı');

        if ($yemekhaneId <= 0 || $ogunId <= 0) {
            throw new InvalidArgumentException('Yemekhane ve öğün seçmelisiniz.');
        }

        if (count($yemekIds) === 0) {
            throw new InvalidArgumentException('Menüye en az bir yemek eklemelisiniz.');
        }

        $temizYemekIds = [];
        foreach ($yemekIds as $yemekId) {
            $dogrulanmisId = filter_var($yemekId, FILTER_VALIDATE_INT);
            if ($dogrulanmisId === false || $dogrulanmisId <= 0) {
                throw new InvalidArgumentException('Yemek seçimlerinden biri geçersiz.');
            }
            $temizYemekIds[] = (int)$dogrulanmisId;
        }
        $temizYemekIds = array_values(array_unique($temizYemekIds));

        $db = Database::yazma();
        $eskiMenu = null;

        try {
            $db->beginTransaction();

            // Seçilen yemeklerin kaynak veritabanında gerçekten bulunduğunu doğrula.
            $yerTutucular = implode(',', array_fill(0, count($temizYemekIds), '?'));
            $yemekKontrol = $db->prepare(
                "SELECT COUNT(*) FROM yemekler WHERE id IN ($yerTutucular)"
            );
            $yemekKontrol->execute($temizYemekIds);
            if ((int)$yemekKontrol->fetchColumn() !== count($temizYemekIds)) {
                throw new InvalidArgumentException('Seçilen yemeklerden biri bulunamadı.');
            }

            if ($menuId === null) {
                $ekle = $db->prepare(
                    'INSERT INTO menuler
                        (yemekhane_id, tarih, ogun_id, ogrenci_fiyati, personel_fiyati)
                     VALUES
                        (:yemekhane_id, :tarih, :ogun_id, :ogrenci_fiyati, :personel_fiyati)'
                );
                $ekle->execute([
                    'yemekhane_id' => $yemekhaneId,
                    'tarih' => $tarih,
                    'ogun_id' => $ogunId,
                    'ogrenci_fiyati' => $ogrenciFiyati,
                    'personel_fiyati' => $personelFiyati,
                ]);
                $kaydedilenMenuId = (int)$db->lastInsertId();
            } else {
                $mevcutSorgu = $db->prepare(
                    'SELECT id, yemekhane_id, tarih, ogun_id
                     FROM menuler
                     WHERE id = :id
                     FOR UPDATE'
                );
                $mevcutSorgu->execute(['id' => $menuId]);
                $mevcutMenu = $mevcutSorgu->fetch();

                if (!$mevcutMenu) {
                    throw new RuntimeException('Düzenlenecek menü bulunamadı.');
                }

                $eskiMenu = $mevcutMenu;

                $rezervasyonSayisi = $db->prepare(
                    'SELECT COUNT(*) FROM rezervasyonlar WHERE menu_id = :menu_id'
                );
                $rezervasyonSayisi->execute(['menu_id' => $menuId]);
                $rezervasyonVar = (int)$rezervasyonSayisi->fetchColumn() > 0;

                $kimlikDegisti =
                    (int)$mevcutMenu['yemekhane_id'] !== $yemekhaneId ||
                    $mevcutMenu['tarih'] !== $tarih ||
                    (int)$mevcutMenu['ogun_id'] !== $ogunId;

                if ($rezervasyonVar && $kimlikDegisti) {
                    throw new DomainException(
                        'Bu menüye ait rezervasyonlar bulunduğu için yemekhane, tarih veya öğün değiştirilemez. Fiyat ve yemekleri düzenleyebilirsiniz.'
                    );
                }

                $guncelle = $db->prepare(
                    'UPDATE menuler
                     SET yemekhane_id = :yemekhane_id,
                         tarih = :tarih,
                         ogun_id = :ogun_id,
                         ogrenci_fiyati = :ogrenci_fiyati,
                         personel_fiyati = :personel_fiyati
                     WHERE id = :id'
                );
                $guncelle->execute([
                    'yemekhane_id' => $yemekhaneId,
                    'tarih' => $tarih,
                    'ogun_id' => $ogunId,
                    'ogrenci_fiyati' => $ogrenciFiyati,
                    'personel_fiyati' => $personelFiyati,
                    'id' => $menuId,
                ]);

                $sil = $db->prepare('DELETE FROM menu_yemekleri WHERE menu_id = :menu_id');
                $sil->execute(['menu_id' => $menuId]);
                $kaydedilenMenuId = $menuId;
            }

            $yemekEkle = $db->prepare(
                'INSERT INTO menu_yemekleri (menu_id, yemek_id)
                 VALUES (:menu_id, :yemek_id)'
            );
            foreach ($temizYemekIds as $yemekId) {
                $yemekEkle->execute([
                    'menu_id' => $kaydedilenMenuId,
                    'yemek_id' => $yemekId,
                ]);
            }

            $db->commit();

            // Veritabanı kaydı tamamlandıktan sonra eski ve yeni menü önbelleğini sil.
            if (is_array($eskiMenu)) {
                RedisOnbellek::menuOnbellekSil(
                    (int)$eskiMenu['yemekhane_id'],
                    (string)$eskiMenu['tarih'],
                    (int)$eskiMenu['ogun_id']
                );
            }

            $yeniAnahtar = RedisOnbellek::menuAnahtariOlustur(
                $yemekhaneId,
                $tarih,
                $ogunId
            );

            $eskiAnahtar = is_array($eskiMenu)
                ? RedisOnbellek::menuAnahtariOlustur(
                    (int)$eskiMenu['yemekhane_id'],
                    (string)$eskiMenu['tarih'],
                    (int)$eskiMenu['ogun_id']
                )
                : null;

            if ($eskiAnahtar !== $yeniAnahtar) {
                RedisOnbellek::menuOnbellekSil($yemekhaneId, $tarih, $ogunId);
            }

            return $kaydedilenMenuId;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function fiyatDogrula(string $fiyat, string $alanAdi): string
    {
        $fiyat = trim(str_replace(',', '.', $fiyat));
        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $fiyat)) {
            throw new InvalidArgumentException($alanAdi . ' geçerli bir tutar olmalıdır.');
        }

        return number_format((float)$fiyat, 2, '.', '');
    }
}

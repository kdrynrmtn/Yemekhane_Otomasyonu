<?php

require_once __DIR__ . '/../Database.php';

class Degerlendirme
{
    /** Kullanıcının kullandığı menüleri ve varsa önceki değerlendirmelerini replikadan getirir. */
    public function kullanilmisMenuleriGetir(int $kullaniciId): array
    {
        $db = Database::okuma();
        $sorgu = $db->prepare(
            "SELECT
                m.id AS menu_id,
                m.tarih,
                yh.ad AS yemekhane,
                o.ad AS ogun,
                r.kullanma_zamani,
                d.puan,
                d.yorum,
                d.olusturma AS degerlendirme_zamani,
                (SELECT GROUP_CONCAT(y.ad ORDER BY k.sira, y.ad SEPARATOR ', ')
                   FROM menu_yemekleri my
                   INNER JOIN yemekler y ON y.id = my.yemek_id
                   INNER JOIN yemek_kategorileri k ON k.id = y.kategori_id
                  WHERE my.menu_id = m.id) AS yemekler
             FROM rezervasyonlar r
             INNER JOIN menuler m ON m.id = r.menu_id
             INNER JOIN yemekhaneler yh ON yh.id = m.yemekhane_id
             INNER JOIN ogunler o ON o.id = m.ogun_id
             LEFT JOIN degerlendirmeler d
                    ON d.menu_id = m.id
                   AND d.kullanici_id = r.kullanici_id
             WHERE r.kullanici_id = :kullanici_id
               AND r.durum = 'kullanildi'
             ORDER BY r.kullanma_zamani DESC, m.tarih DESC, m.id DESC"
        );
        $sorgu->execute(['kullanici_id' => $kullaniciId]);

        return $sorgu->fetchAll();
    }

    /** Yalnızca kullanılmış rezervasyonlar için puan/yorum ekler veya günceller. */
    public function kaydet(int $kullaniciId, int $menuId, int $puan, string $yorum): void
    {
        if ($puan < 1 || $puan > 5) {
            throw new InvalidArgumentException('Puan 1 ile 5 arasında olmalıdır.');
        }

        $yorum = trim($yorum);
        if (preg_match_all('/./us', $yorum, $karakterler) === false) {
            throw new InvalidArgumentException('Yorum geçerli UTF-8 karakterleri içermelidir.');
        }
        if (count($karakterler[0]) > 500) {
            throw new InvalidArgumentException('Yorum en fazla 500 karakter olabilir.');
        }

        $db = Database::yazma();

        try {
            $db->beginTransaction();

            $rezervasyon = $db->prepare(
                "SELECT id
                 FROM rezervasyonlar
                 WHERE kullanici_id = :kullanici_id
                   AND menu_id = :menu_id
                   AND durum = 'kullanildi'
                 FOR UPDATE"
            );
            $rezervasyon->execute([
                'kullanici_id' => $kullaniciId,
                'menu_id' => $menuId,
            ]);

            if (!$rezervasyon->fetch()) {
                throw new DomainException('Yalnızca kullanılmış bir menüyü değerlendirebilirsiniz.');
            }

            $sorgu = $db->prepare(
                'INSERT INTO degerlendirmeler
                    (kullanici_id, menu_id, puan, yorum)
                 VALUES
                    (:kullanici_id, :menu_id, :puan, :yorum)
                 ON DUPLICATE KEY UPDATE
                    puan = :guncel_puan,
                    yorum = :guncel_yorum,
                    olusturma = CURRENT_TIMESTAMP'
            );
            $sorgu->execute([
                'kullanici_id' => $kullaniciId,
                'menu_id' => $menuId,
                'puan' => $puan,
                'yorum' => $yorum === '' ? null : $yorum,
                'guncel_puan' => $puan,
                'guncel_yorum' => $yorum === '' ? null : $yorum,
            ]);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database.php';

class Rezervasyon
{
    /** Para hareketini veritabanındaki stored procedure ile oluşturur. */
    public function olustur(int $kullaniciId, int $menuId): array
    {
        try {
            $db = Database::yazma();
            $sorgu = $db->prepare('CALL sp_rezervasyon_yap(:kullanici_id, :menu_id)');
            $sorgu->execute([
                'kullanici_id' => $kullaniciId,
                'menu_id' => $menuId,
            ]);

            $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC) ?: [];
            $sorgu->closeCursor();

            return [
                'basarili' => true,
                'mesaj' => 'Rezervasyonunuz başarıyla oluşturuldu.',
                'rezervasyon_id' => (int)($sonuc['rezervasyon_id'] ?? 0),
                'ucret' => $sonuc['ucret'] ?? null,
                'bakiye' => $sonuc['bakiye'] ?? null,
            ];
        } catch (PDOException $e) {
            error_log('Rezervasyon oluşturma hatası: ' . $e->getMessage());
            return [
                'basarili' => false,
                'mesaj' => $this->sqlHataMesaji($e),
            ];
        }
    }

    /** Kullanıcının rezervasyonlarını replika üzerinden listeler. */
    public function kullaniciRezervasyonlariniGetir(int $kullaniciId): array
    {
        $db = Database::okuma();
        $sorgu = $db->prepare(
            'SELECT
                r.id AS rezervasyon_id,
                r.menu_id,
                r.durum,
                r.ucret,
                r.olusturma,
                r.iptal_zamani,
                r.kullanma_zamani,
                m.tarih,
                yh.ad AS yemekhane,
                o.ad AS ogun,
                TIMESTAMP(m.tarih, o.son_saat) AS rezervasyon_son,
                CASE
                    WHEN r.durum = "aktif" AND NOW() < TIMESTAMP(m.tarih, o.son_saat)
                    THEN 1 ELSE 0
                END AS iptal_edilebilir
             FROM rezervasyonlar r
             INNER JOIN menuler m ON m.id = r.menu_id
             INNER JOIN yemekhaneler yh ON yh.id = m.yemekhane_id
             INNER JOIN ogunler o ON o.id = m.ogun_id
             WHERE r.kullanici_id = :kullanici_id
             ORDER BY m.tarih DESC, o.sira DESC, r.id DESC'
        );

        $sorgu->execute(['kullanici_id' => $kullaniciId]);
        return $sorgu->fetchAll();
    }

    /** İptal ve iade işlemini stored procedure yürütür. */
    public function iptalEt(int $kullaniciId, int $rezervasyonId): array
    {
        try {
            $db = Database::yazma();
            $sorgu = $db->prepare(
                'CALL sp_rezervasyon_iptal(:kullanici_id, :rezervasyon_id)'
            );
            $sorgu->execute([
                'kullanici_id' => $kullaniciId,
                'rezervasyon_id' => $rezervasyonId,
            ]);

            $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC) ?: [];
            $sorgu->closeCursor();

            return [
                'basarili' => true,
                'mesaj' => 'Rezervasyon iptal edildi ve ücret bakiyenize iade edildi.',
                'iade' => $sonuc['iade'] ?? null,
                'bakiye' => $sonuc['bakiye'] ?? null,
            ];
        } catch (PDOException $e) {
            error_log('Rezervasyon iptal hatası: ' . $e->getMessage());
            return [
                'basarili' => false,
                'mesaj' => $this->sqlHataMesaji($e),
            ];
        }
    }

    private function sqlHataMesaji(PDOException $e): string
    {
        $mesaj = $e->errorInfo[2] ?? '';
        if (is_string($mesaj) && trim($mesaj) !== '') {
            return trim($mesaj);
        }

        return 'İşlem gerçekleştirilemedi. Lütfen tekrar deneyin.';
    }
}

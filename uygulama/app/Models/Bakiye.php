<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database.php';

class Bakiye
{
    /**
     * Güncel bakiye kaynak MySQL'den okunur.
     * Böylece yazma işleminden hemen sonra replika gecikmesi yaşanmaz.
     */
    public function guncelBakiyeGetir(int $kullaniciId): string
    {
        $db = Database::yazma();
        $sorgu = $db->prepare(
            'SELECT bakiye FROM kullanicilar WHERE id = :id LIMIT 1'
        );
        $sorgu->execute(['id' => $kullaniciId]);
        $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC);

        if (!$sonuc) {
            throw new RuntimeException('Kullanıcı bakiyesi bulunamadı.');
        }

        return (string)$sonuc['bakiye'];
    }

    /** Bakiye hareket geçmişi replika üzerinden listelenir. */
    public function hareketleriGetir(int $kullaniciId): array
    {
        $db = Database::okuma();
        $sorgu = $db->prepare(
            'SELECT id, tur, tutar, rezervasyon_id, aciklama, olusturma
             FROM bakiye_hareketleri
             WHERE kullanici_id = :kullanici_id
             ORDER BY olusturma DESC, id DESC
             LIMIT 100'
        );
        $sorgu->execute(['kullanici_id' => $kullaniciId]);

        return $sorgu->fetchAll();
    }

    /** Bakiye yüklemesini yalnızca sp_bakiye_yukle prosedürü yapar. */
    public function yukle(int $kullaniciId, string $tutar): array
    {
        $tutar = trim(str_replace(',', '.', $tutar));

        if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $tutar)) {
            return [
                'basarili' => false,
                'mesaj' => 'Lütfen geçerli bir tutar girin.',
            ];
        }

        $tutarSayi = (float)$tutar;
        if ($tutarSayi < 10 || $tutarSayi > 1000) {
            return [
                'basarili' => false,
                'mesaj' => 'Bakiye yükleme tutarı 10 TL ile 1000 TL arasında olmalıdır.',
            ];
        }

        $tutar = number_format($tutarSayi, 2, '.', '');

        try {
            $db = Database::yazma();
            $sorgu = $db->prepare(
                'CALL sp_bakiye_yukle(:kullanici_id, :tutar)'
            );
            $sorgu->execute([
                'kullanici_id' => $kullaniciId,
                'tutar' => $tutar,
            ]);

            $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC) ?: [];
            $sorgu->closeCursor();

            // Prosedürden sonra anlık bakiyeyi kaynaktan oku.
            $guncelBakiye = $this->guncelBakiyeGetir($kullaniciId);

            return [
                'basarili' => true,
                'mesaj' => 'Bakiye yükleme işlemi başarıyla tamamlandı.',
                'hareket_id' => (int)($sonuc['hareket_id'] ?? 0),
                'bakiye' => $guncelBakiye,
            ];
        } catch (PDOException $e) {
            error_log('Bakiye yükleme hatası: ' . $e->getMessage());
            $mesaj = $e->errorInfo[2] ?? '';

            return [
                'basarili' => false,
                'mesaj' => is_string($mesaj) && trim($mesaj) !== ''
                    ? trim($mesaj)
                    : 'Bakiye yükleme işlemi gerçekleştirilemedi.',
            ];
        }
    }
}

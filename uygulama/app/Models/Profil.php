<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database.php';

class Profil
{
    /** Sistemde tanımlı alerjenleri replika üzerinden listeler. */
    public function alerjenleriGetir(): array
    {
        return Database::okuma()->query(
            'SELECT id, ad FROM alerjenler ORDER BY ad'
        )->fetchAll();
    }

    /** Kullanıcının seçtiği alerjenleri replika üzerinden getirir. */
    public function kullaniciAlerjenleriniGetir(int $kullaniciId): array
    {
        $sorgu = Database::okuma()->prepare(
            'SELECT a.id, a.ad
             FROM kullanici_alerjenleri ka
             INNER JOIN alerjenler a ON a.id = ka.alerjen_id
             WHERE ka.kullanici_id = :kullanici_id
             ORDER BY a.ad'
        );
        $sorgu->execute(['kullanici_id' => $kullaniciId]);
        return $sorgu->fetchAll();
    }

    /** Alerjen tercihlerini kaynak veritabanında tek işlem olarak yeniler. */
    public function tercihleriKaydet(int $kullaniciId, array $alerjenIdleri): void
    {
        $ids = [];
        foreach ($alerjenIdleri as $id) {
            $dogrulanmis = filter_var($id, FILTER_VALIDATE_INT);
            if ($dogrulanmis === false || $dogrulanmis === null || $dogrulanmis <= 0) {
                throw new InvalidArgumentException('Geçersiz alerjen seçimi.');
            }
            $ids[] = (int)$dogrulanmis;
        }
        $ids = array_values(array_unique($ids));

        $db = Database::yazma();
        try {
            $db->beginTransaction();

            $kullaniciSorgu = $db->prepare(
                'SELECT id FROM kullanicilar WHERE id = :id FOR UPDATE'
            );
            $kullaniciSorgu->execute(['id' => $kullaniciId]);
            if (!$kullaniciSorgu->fetch()) {
                throw new RuntimeException('Kullanıcı bulunamadı.');
            }

            if ($ids !== []) {
                $yerTutucular = implode(',', array_fill(0, count($ids), '?'));
                $kontrol = $db->prepare(
                    "SELECT COUNT(*) FROM alerjenler WHERE id IN ($yerTutucular)"
                );
                $kontrol->execute($ids);
                if ((int)$kontrol->fetchColumn() !== count($ids)) {
                    throw new InvalidArgumentException('Seçilen alerjenlerden biri bulunamadı.');
                }
            }

            $sil = $db->prepare(
                'DELETE FROM kullanici_alerjenleri WHERE kullanici_id = :kullanici_id'
            );
            $sil->execute(['kullanici_id' => $kullaniciId]);

            if ($ids !== []) {
                $ekle = $db->prepare(
                    'INSERT INTO kullanici_alerjenleri (kullanici_id, alerjen_id)
                     VALUES (:kullanici_id, :alerjen_id)'
                );
                foreach ($ids as $id) {
                    $ekle->execute([
                        'kullanici_id' => $kullaniciId,
                        'alerjen_id' => $id,
                    ]);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

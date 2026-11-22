<?php
declare(strict_types=1);

require_once __DIR__ . '/RedisBaglanti.php';

/** Menü verilerini Redis'te gün sonuna kadar saklar. */
class RedisOnbellek
{
    public static function menuAnahtariOlustur(
        int $yemekhaneId,
        string $tarih,
        int $ogunId
    ): string {
        return sprintf('menu:%d:%s:%d', $yemekhaneId, $tarih, $ogunId);
    }

    /** @return array<int, array<string, mixed>>|null Önbellek yoksa null döner. */
    public static function menuGetir(string $anahtar): ?array
    {
        try {
            $deger = RedisBaglanti::istemci()->get($anahtar);

            if ($deger === false || $deger === null) {
                error_log('[Redis] MENU CACHE MISS; DB source used; key=' . $anahtar);
                return null;
            }

            $cozulmus = json_decode((string)$deger, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($cozulmus)) {
                RedisBaglanti::istemci()->del($anahtar);
                error_log('[Redis] MENU CACHE INVALID; key=' . $anahtar);
                return null;
            }

            error_log('[Redis] MENU CACHE HIT; Redis source used; key=' . $anahtar);
            return $cozulmus;
        } catch (Throwable $e) {
            error_log('[Redis] MENU CACHE READ ERROR; DB fallback; ' . $e->getMessage());
            return null;
        }
    }

    /** Menü verisini takvim gününün sonuna kadar Redis'e yazar. */
    public static function menuKaydet(
        string $anahtar,
        array $veri,
        string $tarih
    ): void {
        try {
            $json = json_encode(
                $veri,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
            $ttl = self::gunSonunaKalanSaniye($tarih);
            RedisBaglanti::istemci()->setex($anahtar, $ttl, $json);
            error_log('[Redis] MENU CACHE STORED; ttl=' . $ttl . '; key=' . $anahtar);
        } catch (Throwable $e) {
            // Redis geçici olarak erişilemezse menü veritabanından gösterilmeye devam eder.
            error_log('[Redis] MENU CACHE WRITE ERROR; ' . $e->getMessage());
        }
    }

    /** Yönetici menüyü oluşturduğunda veya değiştirdiğinde eski kaydı siler. */
    public static function menuOnbellekSil(
        int $yemekhaneId,
        string $tarih,
        int $ogunId
    ): void {
        $anahtar = self::menuAnahtariOlustur($yemekhaneId, $tarih, $ogunId);

        try {
            RedisBaglanti::istemci()->del($anahtar);
            error_log('[Redis] MENU CACHE INVALIDATED; key=' . $anahtar);
        } catch (Throwable $e) {
            // Veritabanı kaydı tamamlandıktan sonra Redis hatası menü kaydını geri almamalı.
            error_log('[Redis] MENU CACHE INVALIDATION ERROR; ' . $e->getMessage());
        }
    }

    private static function gunSonunaKalanSaniye(string $tarih): int
    {
        $saatDilimi = new DateTimeZone('Europe/Istanbul');
        $gun = DateTimeImmutable::createFromFormat('!Y-m-d', $tarih, $saatDilimi);

        if (!$gun || $gun->format('Y-m-d') !== $tarih) {
            return 1;
        }

        $sonrakiGun = $gun->modify('+1 day');
        $simdi = new DateTimeImmutable('now', $saatDilimi);

        return max(1, $sonrakiGun->getTimestamp() - $simdi->getTimestamp());
    }
}

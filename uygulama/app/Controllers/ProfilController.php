<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Profil.php';

class ProfilController
{
    public function index(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        $kullanici = $_SESSION['kullanici'];
        $alerjenler = [];
        $seciliAlerjenler = [];
        $hata = '';

        try {
            $model = new Profil();
            $alerjenler = $model->alerjenleriGetir();
            $seciliKayitlar = $model->kullaniciAlerjenleriniGetir((int)$kullanici['id']);
            $seciliAlerjenler = array_map(
                static fn(array $satir): int => (int)$satir['id'],
                $seciliKayitlar
            );
        } catch (Throwable $e) {
            error_log('Profil bilgileri alınırken hata: ' . $e->getMessage());
            $hata = 'Alerjen tercihleri şu anda alınamıyor.';
        }

        $bildirim = $this->bildirimAl();
        require __DIR__ . '/../Views/profil.php';
    }

    public function alerjenKaydet(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo 'Geçersiz istek yöntemi.';
            return;
        }

        if (!$this->csrfGecerliMi()) {
            http_response_code(400);
            echo 'Geçersiz istek.';
            return;
        }

        $ids = $_POST['alerjen_ids'] ?? [];
        if (!is_array($ids)) {
            $this->bildirimAyarla('hata', 'Alerjen seçimleri geçersiz.');
            header('Location: /profil');
            exit;
        }

        try {
            $model = new Profil();
            $model->tercihleriKaydet((int)$_SESSION['kullanici']['id'], $ids);
            $this->bildirimAyarla('basarili', 'Alerjen tercihleriniz kaydedildi.');
        } catch (PDOException $e) {
            error_log('Alerjen tercihi veritabanı hatası: ' . $e->getMessage());
            $this->bildirimAyarla('hata', 'Alerjen tercihleriniz kaydedilemedi.');
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->bildirimAyarla('hata', $e->getMessage());
        } catch (Throwable $e) {
            error_log('Alerjen tercihleri kaydedilirken hata: ' . $e->getMessage());
            $this->bildirimAyarla('hata', 'Alerjen tercihleriniz kaydedilemedi.');
        }

        header('Location: /profil');
        exit;
    }

    private function csrfGecerliMi(): bool
    {
        $oturumToken = $_SESSION['csrf'] ?? '';
        $gelenToken = $_POST['csrf'] ?? '';
        return is_string($oturumToken)
            && $oturumToken !== ''
            && is_string($gelenToken)
            && hash_equals($oturumToken, $gelenToken);
    }

    private function bildirimAyarla(string $tur, string $mesaj): void
    {
        $_SESSION['profil_bildirim_turu'] = $tur;
        $_SESSION['profil_bildirim_mesaji'] = $mesaj;
    }

    private function bildirimAl(): array
    {
        $bildirim = [
            'tur' => $_SESSION['profil_bildirim_turu'] ?? '',
            'mesaj' => $_SESSION['profil_bildirim_mesaji'] ?? '',
        ];
        unset($_SESSION['profil_bildirim_turu'], $_SESSION['profil_bildirim_mesaji']);
        return $bildirim;
    }
}

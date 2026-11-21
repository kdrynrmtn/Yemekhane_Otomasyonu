<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Rezervasyon.php';

class RezervasyonController
{
    /** Görev 4: Kullanıcının aktif, kullanılmış ve iptal edilmiş rezervasyonları. */
    public function index(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        $hata = '';
        $rezervasyonlar = [];
        $kullanici = $_SESSION['kullanici'];

        try {
            $model = new Rezervasyon();
            $rezervasyonlar = $model->kullaniciRezervasyonlariniGetir(
                (int)$kullanici['id']
            );
        } catch (Throwable $e) {
            error_log('Rezervasyonlar listelenirken hata: ' . $e->getMessage());
            $hata = 'Rezervasyonlarınız şu anda alınamıyor.';
        }

        $bildirim = $this->bildirimAl();
        require __DIR__ . '/../Views/rezervasyonlarim.php';
    }

    /** Aktif ve son saati geçmemiş rezervasyonu stored procedure ile iptal eder. */
    public function iptalEt(): void
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

        $rezervasyonId = filter_var(
            $_POST['rezervasyon_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($rezervasyonId === false || $rezervasyonId === null || $rezervasyonId <= 0) {
            $this->bildirimAyarla('hata', 'Geçersiz rezervasyon bilgisi.');
            header('Location: /rezervasyonlarim');
            exit;
        }

        try {
            $model = new Rezervasyon();
            $sonuc = $model->iptalEt(
                (int)$_SESSION['kullanici']['id'],
                $rezervasyonId
            );

            $this->bildirimAyarla(
                $sonuc['basarili'] ? 'basarili' : 'hata',
                $sonuc['mesaj']
            );
        } catch (Throwable $e) {
            error_log('Rezervasyon iptal isteği hatası: ' . $e->getMessage());
            $this->bildirimAyarla('hata', 'Rezervasyon iptal edilemedi.');
        }

        header('Location: /rezervasyonlarim');
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
        $_SESSION['bildirim_turu'] = $tur;
        $_SESSION['bildirim_mesaji'] = $mesaj;
    }

    private function bildirimAl(): array
    {
        $bildirim = [
            'tur' => $_SESSION['bildirim_turu'] ?? '',
            'mesaj' => $_SESSION['bildirim_mesaji'] ?? '',
        ];

        unset($_SESSION['bildirim_turu'], $_SESSION['bildirim_mesaji']);
        return $bildirim;
    }
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Kullanici.php';

class GirisController
{
    public function form(): void
    {
        if (isset($_SESSION['kullanici'])) {
            header('Location: /panel');
            exit;
        }
        $hata = $_SESSION['giris_hatasi'] ?? '';
        unset($_SESSION['giris_hatasi']);
        require __DIR__ . '/../Views/giris.php';
    }

    public function girisYap(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(400);
            echo 'Geçersiz istek.';
            return;
        }

        $eposta = trim($_POST['eposta'] ?? '');
        $sifre = $_POST['sifre'] ?? '';

        if (!filter_var($eposta, FILTER_VALIDATE_EMAIL) || $sifre === '') {
            $_SESSION['giris_hatasi'] = 'E-posta veya şifre hatalı.';
            header('Location: /giris');
            exit;
        }

        try {
            $model = new Kullanici();
            $kullanici = $model->epostaIleBul($eposta);
        } catch (Throwable $e) {
            error_log('Giriş sırasında veritabanı hatası: ' . $e->getMessage());
            http_response_code(503);
            echo 'Şu anda giriş yapılamıyor. Lütfen daha sonra tekrar deneyin.';
            return;
        }

        if (!$kullanici || !(bool)$kullanici['aktif'] ||
            !password_verify($sifre, $kullanici['sifre_hash'])) {
            $_SESSION['giris_hatasi'] = 'E-posta veya şifre hatalı.';
            header('Location: /giris');
            exit;
        }

        session_regenerate_id(true);
        unset($kullanici['sifre_hash']);
        $_SESSION['kullanici'] = $kullanici;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: /panel');
        exit;
    }

    public function cikisYap(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
            hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }
        header('Location: /giris');
        exit;
    }
}

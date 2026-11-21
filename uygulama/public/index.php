<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Istanbul');

session_name('yemekhane_oturum');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../app/Controllers/GirisController.php';
require_once __DIR__ . '/../app/Controllers/PanelController.php';
require_once __DIR__ . '/../app/Controllers/MenuController.php';
require_once __DIR__ . '/../app/Controllers/RezervasyonController.php';
require_once __DIR__ . '/../app/Controllers/BakiyeController.php';
require_once __DIR__ . '/../app/Controllers/DegerlendirmeController.php';
require_once __DIR__ . '/../app/Controllers/YoneticiController.php';

$yol = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$giris = new GirisController();
$panel = new PanelController();
$menu = new MenuController();
$rezervasyon = new RezervasyonController();
$bakiye = new BakiyeController();
$degerlendirme = new DegerlendirmeController();
$yonetici = new YoneticiController();

switch ($yol) {
    case '/':
    case '/giris':
        $giris->form();
        break;

    case '/giris-yap':
        $giris->girisYap();
        break;

    case '/cikis':
        $giris->cikisYap();
        break;

    case '/panel':
        $panel->index();
        break;

    case '/gunun-menusu':
        $menu->gununMenusu();
        break;

    case '/haftalik-menu':
        $menu->haftalikMenu();
        break;

    case '/rezervasyon-yap':
        $menu->rezervasyonYap();
        break;

    case '/rezervasyonlarim':
        $rezervasyon->index();
        break;

    case '/rezervasyon-iptal':
        $rezervasyon->iptalEt();
        break;

    case '/bakiye':
        $bakiye->index();
        break;

    case '/bakiye-yukle':
        $bakiye->yukle();
        break;

    case '/degerlendirme':
        $degerlendirme->index();
        break;

    case '/degerlendirme-kaydet':
        $degerlendirme->kaydet();
        break;

    case '/yonetici':
        $yonetici->index();
        break;

    case '/yonetici-menu-kaydet':
        $yonetici->menuKaydet();
        break;

    default:
        http_response_code(404);
        echo 'Sayfa bulunamadı.';
}

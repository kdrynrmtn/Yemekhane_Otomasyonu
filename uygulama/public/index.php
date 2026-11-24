<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . '/../app/RedisBaglanti.php';

// Aşama 3: PHP oturumlarını Redis'te tut.
// Prefix, Redis anahtarlarını oturum:{token} biçiminde oluşturur.
if (!extension_loaded('redis')) {
    http_response_code(503);
    echo 'Redis PHP eklentisi bulunamadı. Uygulama imajını yeniden oluşturun.';
    exit;
}

$redisHost = getenv('REDIS_HOST') ?: 'redis';
$redisPort = (int)(getenv('REDIS_PORT') ?: 6379);

try {
    // session_start öncesinde Redis bağlantısını doğrula.
    $redisKontrol = new Redis();
    if (!$redisKontrol->connect($redisHost, $redisPort, 2.5)) {
        throw new RuntimeException('Redis bağlantısı kurulamadı.');
    }
    $redisKontrol->close();
} catch (Throwable $e) {
    error_log('[Redis] SESSION CONNECTION ERROR; ' . $e->getMessage());
    http_response_code(503);
    echo 'Oturum servisine şu anda ulaşılamıyor. Lütfen daha sonra tekrar deneyin.';
    exit;
}

session_name('yemekhane_oturum');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

ini_set('session.save_handler', 'redis');
ini_set(
    'session.save_path',
    sprintf(
        'tcp://%s:%d?prefix=oturum:&database=0&timeout=2.5&read_timeout=2.5',
        $redisHost,
        $redisPort
    )
);
ini_set('session.gc_maxlifetime', '1800');
// Aynı oturuma eşzamanlı isteklerde veri kaybını önlemek için Redis kilidini aç.
ini_set('redis.session.locking_enabled', '1');
ini_set('redis.session.lock_expire', '60');
ini_set('redis.session.lock_wait_time', '50000');
ini_set('redis.session.lock_retries', '2000');
// Değişmeyen oturumlar da her istekte yeniden yazılır; TTL 30 dakika uzatılır.
ini_set('session.lazy_write', '0');

if (session_start() !== true) {
    http_response_code(503);
    echo 'Oturum başlatılamadı. Lütfen tekrar deneyin.';
    exit;
}

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
require_once __DIR__ . '/../app/Controllers/ProfilController.php';

$yol = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$giris = new GirisController();
$panel = new PanelController();
$menu = new MenuController();
$rezervasyon = new RezervasyonController();
$bakiye = new BakiyeController();
$degerlendirme = new DegerlendirmeController();
$yonetici = new YoneticiController();
$profil = new ProfilController();

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

    case '/profil':
        $profil->index();
        break;

    case '/profil-alerjen-kaydet':
        $profil->alerjenKaydet();
        break;

    default:
        http_response_code(404);
        echo 'Sayfa bulunamadı.';
}

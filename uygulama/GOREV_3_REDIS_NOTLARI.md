# Aşama 3 – Redis önbellek ve oturum entegrasyonu

Bu uygulama, başlangıç paketindeki `redis` servisini kullanır. Kök dizindeki `docker-compose.yml` dosyasında aşağıdaki ayarların bulunması gerekir (başlangıç paketinde zaten vardır):

- `redis` servisi ve `redis:7.4-alpine` imajı
- `REDIS_HOST: redis` ve `REDIS_PORT: "6379"` uygulama ortam değişkenleri
- Redis için `redis-veri:/data` kalıcı volume'u ve AOF (`--appendonly yes`)
- `uygulama` servisinin Redis sağlıklı olduktan sonra başlaması (`depends_on: redis: condition: service_healthy`)

## 1. Uygulamayı Redis eklentisiyle oluşturma

Uygulama `Dockerfile` dosyası `phpredis` eklentisini kurar. Proje kök dizininde:

```powershell
docker compose up -d --build uygulama
```

## 2. Menü önbelleği

Menü verisi şu anahtarla tutulur:

```text
menu:{yemekhane_id}:{tarih}:{ogun_id}
```

Gerçek örnek anahtar biçimi:

```text
menu:1:2026-11-22:2
```

İlk istekte Redis kaydı bulunmazsa menü `Database::okuma()` üzerinden MySQL replikasından okunur ve Redis'e takvim gününün sonuna kadar TTL ile yazılır. İkinci istekte aynı menü Redis'ten okunur. Uygulama günlüğüne aşağıdaki mesajlar yazılır:

```text
[Redis] MENU CACHE MISS; DB source used
[Redis] MENU CACHE STORED
[Redis] MENU CACHE HIT; Redis source used
```

Kapasite, boş yer ve kullanıcının kendi rezervasyon durumu önbelleğe alınmaz; bunlar her istekte replikadan okunur. Böylece rezervasyon değişiklikleri nedeniyle doluluk sayısı eski kalmaz.

Önbelleği kontrol etmek için:

```powershell
docker compose exec redis redis-cli --scan --pattern "menu:*"
```

Listeden bir anahtar seçip kalan TTL'yi görmek için:

```powershell
docker compose exec redis redis-cli TTL "menu:1:2026-11-22:2"
```

`TTL` çıktısı saniye cinsinden kalan süreyi verir. Gerçek anahtar adını `--scan` çıktısından alın.

## 3. Menü değiştiğinde önbelleği silme

Yönetici menü oluşturduğunda veya mevcut menüyü kaydettiğinde, ilgili eski anahtar ve gerekiyorsa yeni anahtar Redis'ten silinir. Uygulama günlüğüne şu mesaj yazılır:

```text
[Redis] MENU CACHE INVALIDATED
```

Bu işlem `Yonetim::menuKaydet()` başarılı veritabanı commit'inden sonra yapılır.

## 4. Redis oturum deposu

PHP oturumları Redis'te `oturum:` önekiyle tutulur:

```text
oturum:{token}
```

Oturum süresi 1800 saniyedir (30 dakika). `session.lazy_write=0` ayarı, değişmeyen oturumların da her istekte yeniden yazılmasını sağlayarak süreyi kayan zaman aşımı biçiminde yeniler. `redis.session.locking_enabled=1` eşzamanlı isteklerde oturum verisinin çakışmasını önlemeye yardımcı olur. Çıkışta `session_destroy()` Redis oturum kaydını siler. PHP oturum verisi uygulama container'ının dosya sisteminde tutulmaz; bu nedenle yalnızca `uygulama` container'ı yeniden oluşturulunca kullanıcı oturumu kaybolmaz.

Aktif oturum anahtarlarını görmek için:

```powershell
docker compose exec redis redis-cli --scan --pattern "oturum:*"
```

Listeden bir anahtar seçip TTL kontrolü:

```powershell
docker compose exec redis redis-cli TTL "oturum:BURAYA_TOKEN"
```

Çıkış yaptıktan sonra aynı anahtarın silindiğini yeniden `--scan` komutuyla kontrol edin.

## 5. Uygulama günlüğüyle MISS ve HIT kanıtı

Bir menü sayfasını ilk kez açın, sonra aynı menüyü yenileyin. Uygulama loglarını takip edin:

```powershell
docker compose logs -f uygulama
```

İlk istekte `MENU CACHE MISS` ve `MENU CACHE STORED`, sonraki istekte `MENU CACHE HIT` satırları görünmelidir.

> Not: PHP dosyalarının sözdizimi kontrolü yapılmıştır. Redis sunucusu ve Docker container'ları bu çalışma ortamında çalıştırılamadığı için canlı bağlantı, TTL ve restart testi kullanıcının Docker ortamında doğrulanmalıdır.

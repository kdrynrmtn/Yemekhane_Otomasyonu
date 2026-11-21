# Yemekhane Otomasyonu – Görev 3, 4 ve 5

Bu `uygulama` klasörü, mevcut PHP MVC uygulamasına aşağıdaki ekranları ekler.

## Görev 3 – Haftalık menü ve rezervasyon

- `/haftalik-menu` adresinde tarih aralığı ve yemekhane seçimi yapılır.
- Öğünler, yemekler, kullanıcı rolüne uygun fiyat, toplam kapasite ve boş yer gösterilir.
- Rezervasyon son saati geçen veya kapasitesi dolan öğünlerin butonu pasiftir.
- Aktif/kullanılmış rezervasyonu bulunan kullanıcıya aynı öğün için ikinci butonla işlem yaptırılmaz.
- Rezervasyon `sp_rezervasyon_yap` stored procedure'ü ile oluşturulur.

## Görev 4 – Rezervasyonlarım

- `/rezervasyonlarim` adresinde kullanıcının aktif, kullanılmış ve iptal edilmiş rezervasyonları listelenir.
- Aktif ve iptal süresi dolmamış rezervasyonlarda iptal düğmesi görünür.
- İptal ve ücret iadesi `sp_rezervasyon_iptal` stored procedure'ü ile yapılır.

## Görev 5 – Bakiye

- `/bakiye` adresinde güncel bakiye ve bakiye hareketleri görüntülenir.
- 10–1000 TL aralığında bakiye yükleme yapılabilir.
- Yükleme `sp_bakiye_yukle` stored procedure'ü ile yapılır.
- Güncel bakiye yazma kaynağından, hareket geçmişi replikadan okunur.

## Gerekli veritabanı nesneleri

Uygulama, ödevin başlangıç paketindeki `yemekhane` veritabanında bulunan aşağıdaki view ve stored procedure'leri kullanır:

- View: `v_menu_detay`, `v_menu_doluluk`
- Stored procedure: `sp_rezervasyon_yap`, `sp_rezervasyon_iptal`, `sp_bakiye_yukle`

Para hareketleri uygulama içinde doğrudan INSERT/UPDATE ile yapılmaz; ilgili stored procedure'lere çağrı gönderilir.

## Yeni adresler

- `GET /haftalik-menu`
- `POST /rezervasyon-yap`
- `GET /rezervasyonlarim`
- `POST /rezervasyon-iptal`
- `GET /bakiye`
- `POST /bakiye-yukle`

Tüm form işlemlerinde oturumdaki CSRF anahtarı kullanılır. PHP dosyaları UTF-8 (BOM olmadan) kaydedilmiştir.

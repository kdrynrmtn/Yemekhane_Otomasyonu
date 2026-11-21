# Görev 6 ve 7 – Değerlendirme ve Yönetici Paneli

## Görev 6 – Değerlendirme

- `GET /degerlendirme` sayfası yalnızca kullanılmış (`durum = 'kullanildi'`) menüleri listeler.
- Kullanıcı, kullandığı her menüye 1–5 arasında puan ve en fazla 500 karakter yorum verebilir.
- Aynı kullanıcı aynı menü için tek değerlendirme kaydına sahiptir. Daha önce değerlendirme yaptıysa yeni kayıt açılmaz; mevcut puan ve yorum güncellenir.
- Listeleme `Database::okuma()` ile replikadan, kaydetme `Database::yazma()` ile kaynak MySQL'den yapılır.

## Görev 7 – Yönetici Paneli

- `GET /yonetici` yalnızca `yonetici` rolündeki kullanıcılar tarafından açılabilir.
- Yönetici yemekhane, tarih, öğün, öğrenci/personel fiyatı ve yemekleri seçerek yeni menü oluşturabilir.
- Rapor tablosundaki Düzenle bağlantısı var olan menüyü forma getirir. Yönetici fiyat ve yemek listesini düzenleyebilir.
- Menüye ait rezervasyon kaydı varsa yemekhane/tarih/öğün kimliği değiştirilemez; böylece mevcut rezervasyonların bağlı olduğu menü bilgisi korunur.
- Menü oluşturma ve düzenleme işlemleri kaynak MySQL'e yazılır. Menü seçenekleri ve doluluk/ortalama puan raporu replikadan okunur.
- Doluluk `v_menu_doluluk`, ortalama puan ve oy sayısı `v_menu_puan` view'larından alınır.

## Kaynak–replika ayrımı ve bakiye açıklaması

Uygulamada kayıt ekleme, güncelleme, rezervasyon, iptal, bakiye yükleme, değerlendirme ve menü oluşturma/düzenleme gibi yazma işlemleri kaynak MySQL'e gönderilir. Menü listeleri, rezervasyon listeleri, bakiye hareket geçmişi ve yönetici raporları replikadan okunur. Replika, kaynak sunucudaki değişiklikleri asenkron biçimde alır; bu nedenle yazma işleminden hemen sonra replika kısa süre eski veriyi gösterebilir (replikasyon gecikmesi). Kullanıcının rezervasyon yaptıktan hemen sonra doğru ve güncel bakiyeyi görmesi için bakiye özellikle kaynak MySQL'den okunur. Bakiye hareket geçmişi ve raporlar ise listeleme oldukları için replikadan alınır.

## Yeni adresler

- `GET /degerlendirme`
- `POST /degerlendirme-kaydet`
- `GET /yonetici`
- `POST /yonetici-menu-kaydet`

Tüm POST formları oturumdaki CSRF anahtarını gönderir. PHP dosyaları UTF-8 (BOM olmadan) kaydedilmiştir.

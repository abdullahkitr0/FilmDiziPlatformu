# Film ve Dizi Öneri Sistemi

Bu proje, kullanıcıların film ve dizi önerileri alabileceği, içeriklere puan verebileceği ve yorum yapabileceği bir web uygulamasıdır. Kullanıcılar, farklı platformlar arasında içerik arayabilir ve önerilerde bulunabilirler.

## İçindekiler

- [Proje Hakkında](#proje-hakkında)
- [Not](#not)
- [Özellikler](#özellikler)
- [Teknolojiler](#teknolojiler)
- [Kurulum](#kurulum)
- [Veritabanı](#veritabanı)
- [Kullanım](#kullanım)
- [Kullanıcı Tarafı Özellikleri](#kullanıcı-tarafı-özellikleri)
- [Yönetici Paneli](#yönetici-paneli)
- [Katkıda Bulunma](#katkıda-bulunma)
- [Lisans](#lisans)

## Proje Hakkında

Film ve Dizi Öneri Sistemi, kullanıcıların film ve dizi önerileri alabileceği, içeriklere puan verebileceği ve yorum yapabileceği bir platformdur. Kullanıcılar, içeriklere puan vererek ve yorum yaparak topluluğa katkıda bulunabilirler. Ayrıca, yöneticiler içerikleri ve platformları yönetebilir.


**Not:** Bu proje, henüz final ürünü değildir ve geliştirilme aşamasındadır. Bazı eksiklikler ve sorunlar mevcut olup, bunların farkındayım. Vakit buldukça projeyi geliştirmeye çalışıyorum. Bu bir hobi projesidir. Eğer siz de bu projeye katkıda bulunmak isterseniz, "[Katkıda Bulunma](#katkıda-bulunma)" kısmına göz atabilirsiniz.


## Özellikler

- **Kullanıcı Kaydı ve Girişi:** Kullanıcılar, sistemde hesap oluşturabilir ve giriş yapabilir.
- **Film ve Dizi Önerileri:** Kullanıcılar, önerilen içerikleri görüntüleyebilir.
- **Yıldız Derecelendirme:** Kullanıcılar, içeriklere puan verebilir.
- **Yorum Yapma:** Kullanıcılar, içeriklere yorum yapabilir.
- **Platform Yönetimi:** Yöneticiler, platformları ekleyip çıkarabilir.
- **Yönetici Paneli:** İçeriklerin ve kullanıcıların yönetimi için bir panel.

## Teknolojiler

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Veritabanı:** MySQL
- **Framework:** Tabler.io (CSS Framework)
- **Kütüphaneler:** Bootstrap, jQuery

## Kurulum

### Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache veya Nginx web sunucusu
- Composer (isteğe bağlı, bağımlılık yönetimi için)

### Adım Adım Kurulum

1. **Depoyu Klonlayın:**
   ```bash
   git clone https://github.com/kullaniciadi/proje-adi.git
   cd proje-adi
   ```

2. **Gerekli Kütüphaneleri Yükleyin:**
   - PHP ve MySQL'in kurulu olduğundan emin olun.
   - Gerekli PHP uzantılarını yükleyin (PDO, mbstring, vb.).

3. **Veritabanı Ayarları:**
   - `config/database.php` dosyasını açın ve veritabanı bilgilerinizi girin.
   - Veritabanını oluşturmak için film_dizi_db.sql dosyasını veritabanına yükleyin.

4. **Config Ayarları:**
    - `config/config.php` dosyasını açın ve gerekli ayarları güncelleyin.
    - Veritabanı bilgilerinizi, API anahtarınızı ve diğer ayarları doğru bir şekilde girdiğinizden emin     olun.

5. **Proje Dosyalarını Sunucuya Yükleyin:**
   - Proje dosyalarını bir web sunucusuna (Apache, Nginx vb.) yükleyin.

6. **Gerekli İzinleri Ayarlayın:**
   - `uploads` klasörüne yazma izinleri verin.
   ```bash
   chmod -R 755 uploads
   ```

7. **.htaccess Dosyası (Apache için):**
   - Proje kök dizinine `.htaccess` dosyası ekleyin ve aşağıdaki içeriği ekleyin:
   ```apache
   RewriteEngine On
   RewriteBase /
   RewriteRule ^index\.php$ - [L]
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule . /index.php [L]
   ```

## Veritabanı 

Veritabanı dosyasını indirmek için [buraya](https://github.com/kullaniciadi/proje-adi/blob/main/no/film_dizi_db(1).sql) tıklayın.

## Kullanım

- Uygulamayı başlatın ve tarayıcınızda `http://localhost/proje-adi` adresine gidin.
- Kullanıcı kaydı oluşturun veya giriş yapın.
- Film ve dizi önerilerini görüntüleyin, puan verin ve yorum yapın.

## Kullanıcı Tarafı Özellikleri

Uygulamanız, kullanıcıların film ve dizi önerilerini görüntüleyebileceği, puan verebileceği ve yorum yapabileceği bir arayüze sahiptir. Aşağıda kullanıcı tarafında bulunan sayfalar ve özellikler detaylıca açıklanmaktadır:

1. **Ana Sayfa:**
   - Kullanıcılar, uygulamayı açtıklarında ana sayfada popüler film ve dizi önerilerini görecekler. Bu sayfa, kullanıcıların ilgisini çekecek içeriklerle doludur.

2. **Film/Dizi Detay Sayfası:**
   - Her film veya dizi için ayrı bir detay sayfası bulunmaktadır. Bu sayfada içerik hakkında detaylı bilgiler, kapak resmi, açıklama ve kullanıcıların puanları yer alır. Kullanıcılar, bu sayfada içerik hakkında yorum yapabilir ve puan verebilir.

3. **Kullanıcı Kaydı:**
   - Yeni kullanıcılar, uygulamaya kaydolmak için bir kayıt formu doldurmalıdır. Bu formda kullanıcı adı, e-posta ve şifre gibi bilgiler istenir.

4. **Giriş Yapma:**
   - Kayıtlı kullanıcılar, kullanıcı adı ve şifreleri ile giriş yaparak uygulamanın tüm özelliklerine erişebilirler. Giriş yaptıktan sonra kullanıcılar, kendi profillerine ve içerik önerilerine ulaşabilir.

5. **Kullanıcı Profili:**
   - Kullanıcılar, kendi profillerinde kişisel bilgilerini görebilir ve güncelleyebilirler. Ayrıca, daha önce izledikleri içeriklerin listesini ve verdikleri puanları görüntüleyebilirler.

6. **İçerik Arama:**
   - Kullanıcılar, belirli bir film veya dizi aramak için arama çubuğunu kullanabilirler. Bu özellik, kullanıcıların istedikleri içeriği hızlı bir şekilde bulmalarını sağlar.

7. **Yorumlar ve Puanlama:**
   - Kullanıcılar, izledikleri içeriklere yorum yapabilir ve puan verebilirler. Bu yorumlar, diğer kullanıcılar tarafından görüntülenebilir ve içeriklerin popülaritesine katkıda bulunur.

8. **Favori Listesi:**
   - Kullanıcılar, beğendikleri içerikleri favori listelerine ekleyebilirler. Bu sayede, daha sonra kolayca erişim sağlayabilirler.

Uygulamanız, kullanıcı dostu bir arayüz ile bu özellikleri sunarak, kullanıcıların film ve dizi deneyimlerini zenginleştirmeyi hedeflemektedir.


### Yönetici Paneli 

Yönetici paneli, uygulamanızın içerik ve kullanıcı yönetimini sağlamak için tasarlanmıştır. Aşağıda yönetici panelinin sunduğu özellikler ve işlevler hakkında detaylı bilgiler bulunmaktadır:

1. **Giriş Yapma:**
   - Yönetici paneline erişim için admin kullanıcı adı ve şifresi ile giriş yapmanız gerekmektedir. Giriş yaptıktan sonra, yönetici panelinin ana sayfasına yönlendirileceksiniz.

2. **İçerik Yönetimi:**
   - **Yeni İçerik Ekleme:** Yönetici panelinden yeni film veya dizi ekleyebilirsiniz. İçerik eklerken aşağıdaki bilgileri doldurmanız gerekmektedir:
     - **Başlık:** İçeriğin adı.
     - **Açıklama:** İçeriğin detaylı açıklaması.
     - **Kategori:** İçeriğin ait olduğu kategori (örneğin, aksiyon, dram, komedi).
     - **Puan:** İçeriğin kullanıcılar tarafından verilen puanı.
     - **Kapak Resmi:** İçeriğin görseli için bir resim yükleyebilirsiniz.
   - **İçerik Düzenleme:** Mevcut içerikleri düzenleyebilir, içerik bilgilerini güncelleyebilirsiniz. Düzenleme işlemi için içerik listesinden ilgili içeriği seçmeniz yeterlidir.
   - **İçerik Silme:** İstenmeyen içerikleri silmek için içerik listesinden silmek istediğiniz içeriği seçip "Sil" butonuna tıklamanız yeterlidir.

3. **Platform Yönetimi:**
   - **Yeni Platform Ekleme:** Uygulamanızda desteklenen yeni platformları ekleyebilirsiniz. Platform eklerken aşağıdaki bilgileri girmeniz gerekmektedir:
     - **Platform Adı:** Yeni platformun adı.
     - **Açıklama:** Platform hakkında kısa bir bilgi.
     - **Logo:** Platformun logosunu yükleyebilirsiniz.
   - **Mevcut Platformları Kaldırma:** Kullanımda olmayan veya gereksiz platformları kaldırabilirsiniz. Bu işlem, platform listesinden ilgili platformu seçip "Kaldır" butonuna tıklayarak yapılır.

4. **Kullanıcı Yönetimi:**
   - **Kullanıcı Listesi:** Tüm kullanıcıların listesini görüntüleyebilir, kullanıcı bilgilerini inceleyebilirsiniz.
   - **Kullanıcı Bilgilerini Düzenleme:** Belirli bir kullanıcının bilgilerini güncelleyebilir, kullanıcıların rollerini değiştirebilirsiniz.
   - **Kullanıcı Silme:** İstenmeyen kullanıcıları sistemden kaldırabilirsiniz. Kullanıcı listesinden silmek istediğiniz kullanıcıyı seçip "Sil" butonuna tıklamanız yeterlidir.

5. **Raporlama ve Analiz:**
   - Yönetici paneli, içerik ve kullanıcı etkileşimleri hakkında raporlar sunar. Bu raporlar, içeriklerin popülaritesini ve kullanıcı davranışlarını analiz etmenize yardımcı olur.

6. **Ayarlar:**
   - Yönetici panelinde genel ayarları yapabileceğiniz bir bölüm bulunmaktadır. Bu bölümde uygulamanızın adı, logosu, iletişim bilgileri ve sosyal medya bağlantıları gibi bilgileri güncelleyebilirsiniz.

Yönetici paneli, uygulamanızın yönetimini kolaylaştırmak için kullanıcı dostu bir arayüze sahiptir. Tüm işlemler, basit ve anlaşılır bir şekilde tasarlanmıştır.


## Katkıda Bulunma

Katkıda bulunmak isterseniz, lütfen aşağıdaki adımları izleyin:

1. Depoyu fork edin.
2. Yeni bir dal oluşturun (`git checkout -b feature/Özellik`).
3. Değişikliklerinizi yapın ve commit edin (`git commit -m 'Yeni özellik ekledim'`).
4. Dalınızı gönderin (`git push origin feature/Özellik`).
5. Bir pull request oluşturun.

## İletişim Bilgileri

Eğer benimle iletişime geçmek isterseniz, aşağıdaki bağlantılardan ulaşabilirsiniz:

- **Web Sitem:** [www.abdullahki.com](https://abdullahki.com)
- **Instagram:** [@abdullah.kvrak](https://www.instagram.com/abdullah.kvrak)
- **GitHub:** [abdullahkitr0](https://github.com/abdullahkitr0)
- **Linkedin:** [abdullahki](https://www.linkedin.com/in/abdullahki)



## Lisans

Bu proje MIT Lisansı altında lisanslanmıştır. Daha fazla bilgi için `LICENSE` dosyasını inceleyin.
# Time API
API modul time management. API ini bagian dari arsitektur microservice aplikasi Linov HR.

## Getting Started
Instruksi berikut akan mendeskripsikan bagaimana cara copy project dan menjalankannya di lokal komputer development. Lihat bagian deployment untuk deploy project di environment yang lain.

### Build with
Framework yang dipakai adalah [Lumen 5.4](https://lumen.laravel.com/docs/5.4)<br/>
Database yang dipakai adalah Postgres 9.6

### Prerequisites
1. Install docker machine<br/>
   Linux:<br/>
   Install [docker engine CE](https://docs.docker.com/engine/installation/)<br/>
   Install [docker compose](https://docs.docker.com/compose/install/)<br/>
   Windows:<br/>
   Install [docker toolbox](https://docs.docker.com/toolbox/toolbox_install_windows/)<br/>
2. Untuk menjalankan repository ini, dibutuhkan repository [docker](https://github.com/linovsoftware/docker)
3. PHP Editor (recommendation: PHP Storm)

### Installing
Berikut cara menjalankan aplikasi di environment development:
1. Buat folder linov_hr dan masuk ke dalam folder tersebut sebagai working directory
2. Clone repository
3. Jalankan script `composer-install.sh`. Script ini akan install dependency library menggunakan composer
4. Copy .env.example dan rename dengan .env, lalu update isi config di file .env tersebut
5. jalankan perintah " docker exec -it linovhr_time-api-php_1 bash" pada terminal
6. jalankan perintah artisan migrate
7. Untuk menjalankan API sebagai docker container, silahkan mengacu ke readme di repository docker

### Migration database
1. buat file migration dengan perintah php artisan make:migration <namaFile> --create="<namaTable>" atau --table="<namaTable>"
    keterangan
    --create="<namatable>" untuk membuat table baru
    --table="<namatable>" untuk membuat memodifikasi table yang bersangkutan
2 jalankan perintah " docker exec -it linovhr_time-api-php_1 bash" pada terminal
3 jalankan perintah php artisan migrate untuk menjalankan migration yang sudah dibuat
  keterangan
  php artisan migrate untuk menjalankan migration
  php artisan migrate:refresh --step=1  (me rollback dan migrate lagi table migration terahir yang suddah dijalankan)
  php artisan migrate:rollback --step=1  (me rollback migration terahir yang suddah dijalankan)

  disarankan menggunakan --step=1 (nomor urut migration di hitung dari bawah) pada perintah php artisan migrate:refresh dan php artisan migrate:rollback agar data dalam databasse tiddak hilang

  untuk pembuatan migration bisa di lihat di laravelsd.com

### Code Architecture
Secara garis besar, layer yang ada di aplikasi adalah: DAO (Data Access Object) - Controller
- DAO mendiskripsikan function untuk manipulasi data di database
- Controller mendeskripsikan function endpoint, business logic.<br/>
  Deklarasi URL & function controller ada di `routes/web.php`<br/>
  Class DAO diinject ke Class Controller melalui constructor.
  Lalu, controller akan memanggil function dari class DAO yang telah diinject tsb.

Basicly, susunan folder mengikuti standard yang didefinisikan oleh Lumen.

### Code standardization
#### DAO
1. Wajib ada blok komentar di bagian class declaration & function declaration
2. DAO class diletakkan di folder app/Business/Dao
3. DAO class diberi suffix Dao
4. Penamaan DAO class berdasarkan domain problem. Ex: manipulasi data terkait masalah role, akan diletakkan di RoleDao.php
5. Query yang dibuat di DAO menggunakan [query builder](https://laravel.com/docs/5.4/queries). Namun, jika query builder tidak memungkinkan, maka bisa menggunakan native query. Penggunaan ORM/Eloquent tidak diijinkan karena performance issue.

#### Controller
1. Wajib ada blok komentar di bagian class declaration & function declaration
2. Controller class diletakkan di folder app/Http/Controllers
3. Controller class diberi suffix Controller
4. Penamaan Controller class berdasarkan domain problem. Ex: Endpoint terkait masalah role, akan diletakkan di RoleController.php
5. Wajib menggunakan transactional di setiap pemanggilan method DAO (insert, update, delete). Ex:
    ```
        DB::transaction(function () use ($request) {
           //call DAO function here
        });
    ```

`Hal-hal lain terkait dengan code, dapat dilihat di dokumentasi Laravel/Lumen. Karena masih mengikuti bentuk standard.`

## Deployment
Repository ini memiliki 2 folder yaitu _docker & _kubernetes.<br/>
Di dalam folder _docker terdapat file konfigurasi php & nginx. Jika diperlukan file-file ini dapat dimodifikasi.<br/>
Di dalam folder _kubernetes terdapat file time-api.yaml yang merupakan file untuk deploy aplikasi ke kubernetes environment<br/>
<br/>
### Deployment ke environment staging:<br/>
Secara proses mirip dengan proses installing di environment development. Hanya pastikan bahwa isi file .env sudah terdefinisi dengan benar.
### Deployment ke environment production:
1. Pastikan file .env.prod terdefinisi dengan benar
2. Ganti image tag di file docker-compose.prod.yaml
3. Ganti image tag di file _kubernetes/time-api.yaml
4. Push project ke github
5. Jalankan script `build-docker.prod.sh` (jika ingin build semua API, silahkan lihat readme di repository [docker](https://github.com/linovsoftware/docker))
6. Push image yang terbentuk ke GCP container registry
7. Deploy di environment kubernetes menggunakan config _kubernetes/time-api.yaml

## Lisensi
Hak milik PT. Drife Solusi Integrasi

## Naive Bayes Classifier untuk Klasifikasi Karyawan
Ini adalah implementasi sederhana dari Naive Bayes Classifier untuk klasifikasi karyawan berdasarkan data training yang diberikan. Proyek ini menggunakan PHP untuk logika aplikasi dan MySQL sebagai database penyimpanan data.

## Deskripsi Singkat
Naive Bayes Classifier digunakan di sini untuk mengklasifikasikan apakah seorang karyawan akan naik jabatan atau tidak berdasarkan kriteria-kriteria yang diberikan. Kriteria-kriteria ini diambil dari data training yang tersedia dalam database.

## Fitur Utama
Mengambil data karyawan dan kriteria dari database MySQL.
Menghitung probabilitas prior dari data training.
Menghitung likelihood untuk setiap sub-kriteria berdasarkan kategori "Naik" dan "Tidak Naik".
Menggunakan hasil Naive Bayes untuk menghitung posterior dan menyimpannya di database.
Melakukan evaluasi menggunakan confusion matrix untuk mengukur akurasi, presisi, recall, dan F1 score dari klasifikasi.
## Setup Proyek
1. Prasyarat:
   Pastikan Anda memiliki PHP dan MySQL terinstal di lingkungan pengembangan Anda.
   Buatlah database MySQL dan importlah skema dari file database_schema.sql.
2. Konfigurasi Koneksi:
   Edit file koneksi.php dan sesuaikan pengaturan host, username, password, dan nama database sesuai dengan lingkungan MySQL Anda.
3. Menjalankan Aplikasi:
   Akses aplikasi melalui web browser dengan mengarahkan ke direktori tempat file ini disimpan.

## Struktur Proyek
index.php: Halaman utama untuk login dan verifikasi peran pengguna. <br>
klasifikasi.php: Halaman untuk menginisiasi perhitungan Naive Bayes dan menampilkan hasilnya. <br>
koneksi.php: File untuk mengelola koneksi ke database MySQL. <br>
training_data.sql: Contoh data training untuk digunakan dalam aplikasi. <br>
README.md: Dokumen ini, memberikan penjelasan singkat tentang proyek. <br>

Kontribusi
Anda dipersilakan untuk berkontribusi pada proyek ini dengan cara melakukan fork, membuat perubahan, dan mengirimkan pull request. Jika Anda menemukan masalah atau memiliki saran perbaikan, buka Issue baru di repositori ini.

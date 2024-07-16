<?php
// session_start();
require_once "../../koneksi.php";

// Koneksi ke database
$conn = connectDatabase();

// Ambil data karyawan
$sql_karyawan = "SELECT * FROM karyawan";
$result_karyawan = $conn->query($sql_karyawan);
if (!$result_karyawan) {
    die("Error querying karyawan: " . $conn->error);
}
$karyawan_data = $result_karyawan->fetch_all(MYSQLI_ASSOC);

// Ambil data kriteria
$sql_kriteria = "SELECT * FROM criteria";
$result_kriteria = $conn->query($sql_kriteria);
if (!$result_kriteria) {
    die("Error querying criteria: " . $conn->error);
}
$criteria = $result_kriteria->fetch_all(MYSQLI_ASSOC);

$prior = [];
$total_data = 0;

// Jika tombol "Hitung" ditekan, panggil fungsi hitungNaiveBayes
if (isset($_POST['hitung'])) {
    // Hapus hasil sebelumnya
    if (!$conn->query("TRUNCATE TABLE hasil_klasifikasi")) {
        die("Error truncating hasil_klasifikasi: " . $conn->error);
    }

    if (!$conn->query("TRUNCATE TABLE posterior")) {
        die("Error truncating posterior: " . $conn->error);
    }

    if (!$conn->query("TRUNCATE TABLE probabilitas_prior")) {
        die("Error truncating probabilitas_prior: " . $conn->error);
    }

    if (!$conn->query("TRUNCATE TABLE likelihood")) {
        die("Error truncating likelihood: " . $conn->error);
    }

    // Ambil probabilitas prior
    $sql_prior = "SELECT status, COUNT(*) as count FROM training_data GROUP BY status";
    $result_prior = $conn->query($sql_prior);
    if (!$result_prior) {
        die("Error querying training_data: " . $conn->error);
    }

    while ($row = $result_prior->fetch_assoc()) {
        $prior[$row['status']] = $row['count'];
    }
    $total_data = array_sum($prior); // Total data dari probabilitas prior

    // Cek apakah criteria tidak kosong
    if (!empty($criteria)) {
        foreach ($criteria as $criterion) {
            // Asumsikan $criterion berisi 'id' dan 'name' dari criteria
            $criteria_id = $criterion['id'];
            $criteria_name = $criterion['name'];
            
            // Ambil sub-criteria
            $sql_sub_criteria = "
                SELECT sc.id AS sub_criteria_id, sc.name AS sub_criteria_name
                FROM sub_criteria sc
                LEFT JOIN karyawan_kriteria kk ON sc.id = kk.sub_criteria_id
                WHERE kk.criteria_id = $criteria_id
                GROUP BY sc.id, sc.name
            ";
            $result_sub_criteria = $conn->query($sql_sub_criteria);

            if ($result_sub_criteria->num_rows > 0) {
                // Loop melalui setiap sub-criteria
                while ($row_sub_criteria = $result_sub_criteria->fetch_assoc()) {
                    $sub_criteria_id = $row_sub_criteria['sub_criteria_id'];
                    $sub_criteria_name = $row_sub_criteria['sub_criteria_name'];

                    // Hitung kejadian untuk "Naik"
                    $sql_count_naik = "
                        SELECT COUNT(*) AS count_naik
                        FROM karyawan_kriteria kk
                        LEFT JOIN training_data td ON kk.karyawan_id = td.karyawan_id
                        WHERE kk.sub_criteria_id = $sub_criteria_id AND td.status = 'Naik'
                    ";
                    $result_count_naik = $conn->query($sql_count_naik);
                    $count_naik = $result_count_naik->fetch_assoc()['count_naik'];

                    // Hitung total "Naik"
                    $sql_total_naik = "
                        SELECT COUNT(*) AS total_naik
                        FROM training_data
                        WHERE status = 'Naik'
                    ";
                    $result_total_naik = $conn->query($sql_total_naik);
                    $total_naik = $result_total_naik->fetch_assoc()['total_naik'];

                    // Hitung kejadian untuk "Tidak Naik"
                    $sql_count_tidak_naik = "
                        SELECT COUNT(*) AS count_tidak_naik
                        FROM karyawan_kriteria kk
                        LEFT JOIN training_data td ON kk.karyawan_id = td.karyawan_id
                        WHERE kk.sub_criteria_id = $sub_criteria_id AND td.status != 'Naik'
                    ";
                    $result_count_tidak_naik = $conn->query($sql_count_tidak_naik);
                    $count_tidak_naik = $result_count_tidak_naik->fetch_assoc()['count_tidak_naik'];

                    // Hitung total "Tidak Naik"
                    $sql_total_tidak_naik = "
                        SELECT COUNT(*) AS total_tidak_naik
                        FROM training_data
                        WHERE status != 'Naik'
                    ";
                    $result_total_tidak_naik = $conn->query($sql_total_tidak_naik);
                    $total_tidak_naik = $result_total_tidak_naik->fetch_assoc()['total_tidak_naik'];

                    // Hitung likelihood untuk "Naik"
                    $likelihood_naik = ($total_naik > 0) ? $count_naik / $total_naik : 0;

                    // Hitung likelihood untuk "Tidak Naik"
                    $likelihood_tidak_naik = ($total_tidak_naik > 0) ? $count_tidak_naik / $total_tidak_naik : 0;

                    // Simpan likelihood ke database
                    $sql_save_likelihood = "
                        INSERT INTO likelihood (criteria_id, kelas, nilai, likelihood_naik, likelihood_tidak_naik)
                        VALUES ($criteria_id, '$sub_criteria_name', $sub_criteria_id, $likelihood_naik, $likelihood_tidak_naik)
                    ";
                    if (!$conn->query($sql_save_likelihood)) {
                        die("Error saving likelihood: " . $conn->error);
                    }
                }
            }
        }
    } else {
        die("No criteria found.");
    }

    // Fungsi untuk menghitung dan menyimpan nilai posterior
    function hitungPosterior($conn, $karyawan_id, $posterior_naik, $posterior_tidak_naik) {
        // Simpan nilai posterior ke dalam tabel posterior
        $sql_save_posterior = "
            INSERT INTO posterior (karyawan_id, naik, tidak_naik)
            VALUES ($karyawan_id, $posterior_naik, $posterior_tidak_naik)
        ";
        if (!$conn->query($sql_save_posterior)) {
            die("Error saving posterior: " . $conn->error);
        }
    }

    // Fungsi hitungNaiveBayes
    function hitungNaiveBayes($conn, $karyawan_data, $criteria, $prior, $total_data) {
        foreach ($karyawan_data as $karyawan) {
            $karyawan_id = $karyawan['id'];
            $posterior_naik = 1.0;
            $posterior_tidak_naik = 1.0;
    
            // Ambil kriteria yang dimiliki oleh karyawan dari tabel karyawan_kriteria
            $sql_kriteria = "
                SELECT criteria_id, sub_criteria_id
                FROM karyawan_kriteria
                WHERE karyawan_id = $karyawan_id
            ";
            $result_kriteria = $conn->query($sql_kriteria);
    
            if ($result_kriteria) {
                while ($row_kriteria = $result_kriteria->fetch_assoc()) {
                    $criteria_id = $row_kriteria['criteria_id'];
                    $sub_criteria_id = $row_kriteria['sub_criteria_id'];
    
                    // Query untuk mengambil nilai likelihood untuk kriteria dan sub kriteria yang sesuai
                    $sql_likelihood = "
                        SELECT likelihood_naik, likelihood_tidak_naik
                        FROM likelihood
                        WHERE criteria_id = $criteria_id
                        AND nilai = $sub_criteria_id
                    ";
                    $result_likelihood = $conn->query($sql_likelihood);
    
                    if ($result_likelihood) {
                        $row_likelihood = $result_likelihood->fetch_assoc();
                        $likelihood_naik = $row_likelihood['likelihood_naik'];
                        $likelihood_tidak_naik = $row_likelihood['likelihood_tidak_naik'];
    
                        // Mengalikan likelihood untuk masing-masing kriteria dan sub kriteria
                        $posterior_naik *= $likelihood_naik;
                        $posterior_tidak_naik *= $likelihood_tidak_naik;
                    } else {
                        // Penanganan kesalahan jika query tidak berhasil dijalankan
                        die("Error in query: " . $conn->error);
                    }
                }
    
                // Panggil fungsi hitungPosterior untuk menyimpan nilai posterior ke dalam tabel
                hitungPosterior($conn, $karyawan_id, $posterior_naik, $posterior_tidak_naik);
            } else {
                // Penanganan kesalahan jika query tidak berhasil dijalankan
                die("Error in query: " . $conn->error);
            }
        }
        // Simpan probabilitas prior ke dalam tabel probabilitas_prior
        foreach ($prior as $kelas => $count) {
            $probabilitas = $count / $total_data;
            $sql_prior_insert = "INSERT INTO probabilitas_prior (kelas, jumlah, probabilitas) VALUES ('$kelas', $count, $probabilitas)";
            if (!$conn->query($sql_prior_insert)) {
                die("Error inserting probabilitas_prior: " . $conn->error);
            }
        }
    }

    // Panggil fungsi hitungNaiveBayes
    hitungNaiveBayes($conn, $karyawan_data, $criteria, $prior, $total_data);

    // Fungsi untuk menentukan hasil klasifikasi dan menyimpan ke dalam tabel hasil_klasifikasi
    function hasil_klasifikasi($conn) {
        // Kosongkan tabel hasil_klasifikasi sebelum memasukkan hasil yang baru
        if (!$conn->query("TRUNCATE TABLE hasil_klasifikasi")) {
            die("Error truncating hasil_klasifikasi: " . $conn->error);
        }

        // Ambil data posterior dari tabel posterior
        $sql_posterior = "SELECT karyawan_id, naik, tidak_naik FROM posterior";
        $result_posterior = $conn->query($sql_posterior);

        if ($result_posterior) {
            while ($row_posterior = $result_posterior->fetch_assoc()) {
                $karyawan_id = $row_posterior['karyawan_id'];
                $naik = $row_posterior['naik'];
                $tidak_naik = $row_posterior['tidak_naik'];

                // Tentukan hasil klasifikasi berdasarkan perbandingan naik dan tidak_naik
                $hasil_klasifikasi = ($naik > $tidak_naik) ? 'Naik' : 'Tidak Naik';

                // Tanggal sekarang
                $tanggal = date('Y-m-d H:i:s');

                // Status (contoh: aktif, tidak aktif, dll)
                $status = 'Aktif'; // Misalnya, Anda dapat menyesuaikan status sesuai dengan kebutuhan

                // Simpan hasil klasifikasi ke dalam tabel hasil_klasifikasi
                $sql_save_classification = "
                    INSERT INTO hasil_klasifikasi (karyawan_id, hasil, tanggal, status)
                    VALUES ($karyawan_id, '$hasil_klasifikasi', '$tanggal', '$status')
                ";
                if (!$conn->query($sql_save_classification)) {
                    die("Error saving classification: " . $conn->error);
                }
            }

            // SweetAlert2 untuk pemberitahuan perhitungan berhasil
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Perhitungan berhasil!',
                            text: 'Perhitungan Naive Bayes Classifier selesai.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'klasifikasi.php'; // Ganti dengan halaman tujuan setelah berhasil
                        });
                    });
                  </script>";
        } else {
            // Penanganan kesalahan jika query tidak berhasil dijalankan
            die("Error in query: " . $conn->error);
        }
    }

    // Panggil fungsi hasil_klasifikasi untuk memulai proses klasifikasi setelah perhitungan selesai
    hasil_klasifikasi($conn);
}

// Proses reset data jika tombol reset ditekan
if (isset($_POST['confirm_reset'])) {
    $tables = ['hasil_klasifikasi', 'posterior', 'probabilitas_prior', 'likelihood'];
    foreach ($tables as $table) {
        if (!$conn->query("TRUNCATE TABLE $table")) {
            die("Error truncating $table: " . $conn->error);
        }
    }
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Reset berhasil!',
                    text: 'Data berhasil dihapus.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'klasifikasi.php'; // Ganti dengan halaman tujuan setelah reset
                });
            });
          </script>";

    // Redirect setelah berhasil
    exit;
}

// Query untuk mengambil actual dan predicted dari hasil klasifikasi
$sql_results = "SELECT td.status as actual, hk.hasil as predicted 
               FROM hasil_klasifikasi hk 
               JOIN training_data td ON hk.karyawan_id = td.karyawan_id";
$result_results = $conn->query($sql_results);

if ($result_results && $result_results->num_rows > 0) {
    // Inisialisasi confusion matrix
    $confusion_matrix = [
        "TP" => 0,
        "FP" => 0,
        "FN" => 0,
        "TN" => 0
    ];

    // Mengisi confusion matrix berdasarkan hasil actual dan predicted
    while ($row = $result_results->fetch_assoc()) {
        $actual = $row['actual'];
        $predicted = $row['predicted'];

        if ($actual == "Naik" && $predicted == "Naik") {
            $confusion_matrix["TP"]++;
        } elseif ($actual == "Tidak Naik" && $predicted == "Naik") {
            $confusion_matrix["FP"]++;
        } elseif ($actual == "Naik" && $predicted == "Tidak Naik") {
            $confusion_matrix["FN"]++;
        } elseif ($actual == "Tidak Naik" && $predicted == "Tidak Naik") {
            $confusion_matrix["TN"]++;
        }
    }

    // Hitung metrik evaluasi
    $accuracy = ($confusion_matrix["TP"] + $confusion_matrix["TN"]) / array_sum($confusion_matrix);

    // Precision Naik
    if ($confusion_matrix["TP"] + $confusion_matrix["FP"] > 0) {
        $precision_naik = $confusion_matrix["TP"] / ($confusion_matrix["TP"] + $confusion_matrix["FP"]);
    } else {
        $precision_naik = 0;
    }

    // Recall Naik
    if ($confusion_matrix["TP"] + $confusion_matrix["FN"] > 0) {
        $recall_naik = $confusion_matrix["TP"] / ($confusion_matrix["TP"] + $confusion_matrix["FN"]);
    } else {
        $recall_naik = 0;
    }

    // F1-score Naik
    if ($precision_naik + $recall_naik > 0) {
        $f1_score_naik = 2 * ($precision_naik * $recall_naik) / ($precision_naik + $recall_naik);
    } else {
        $f1_score_naik = 0;
    }
}
?>
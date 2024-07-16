<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'kabid') {
    header("Location: ../../index.php");
    exit();
}

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
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/logo.png">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>
    Kabid | Dukcapil
  </title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
  <!-- Nepcha Analytics (nepcha.com) -->
  <!-- Nepcha is a easy-to-use web analytics. No cookies and fully compliant with GDPR, CCPA and PECR. -->
  <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
</head>

<body class="g-sidenav-show  bg-gray-200">
  <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3   bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-0" href=" https://demos.creative-tim.com/material-dashboard/pages/dashboard " target="_blank">
        <img src="../assets/img/logo.png" style="width:25px !important;" class="navbar-brand-img h-100" alt="main_logo">
        <span class="ms-1 font-weight-bold text-white">Dinas Dukcapil Paluta</span>
      </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link text-white" href="../pages/dashboard.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">dashboard</i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../pages/pegawai.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">group</i>
            </div>
            <span class="nav-link-text ms-1">Manajemen Pegawai</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white active bg-gradient-primary" href="../pages/klasifikasi.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">receipt_long</i>
            </div>
            <span class="nav-link-text ms-1">Klasifikasi</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../pages/laporan.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">print</i>
            </div>
            <span class="nav-link-text ms-1">Laporan</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="../pages/kriteria.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">hub</i>
            </div>
            <span class="nav-link-text ms-1">Kriteria</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="../pages/sub_kriteria.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">hive</i>
            </div>
            <span class="nav-link-text ms-1">Sub Kriteria</span>
          </a>
        </li>
        <li class="nav-item mt-3">
          <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Account pages</h6>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../pages/users.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">groups</i>
            </div>
            <span class="nav-link-text ms-1">Manajemen Users</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="../pages/profil.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">person</i>
            </div>
            <span class="nav-link-text ms-1">Profil</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white " href="#" id="logoutBtn" >
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">logout</i>
            </div>
            <span class="nav-link-text ms-1">Logout</span>
          </a>
        </li>
      </ul>
    </div>
  </aside>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
              <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
              <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Klasifikasi</li>
            </ol>
            <h6 class="font-weight-bolder mb-0">Klasifikasi</h6>
          </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
          <div class="ms-md-auto pe-md-3 d-flex align-items-center">
          </div>
          <ul class="navbar-nav  justify-content-end">
            
          
            <li class="nav-item d-flex align-items-center">
              <a href="../../logout.php" id="logoutBtn" class="nav-link text-body font-weight-bold px-0">
                <i class="fa fa-user me-sm-1"></i>
                <span class="d-sm-inline d-none">Sign Out</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- End Navbar -->
<!-- HTML untuk Tampilan Data Pegawai dan Hasil Klasifikasi -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <form method="post" id="naiveBayesForm">
                <button type="submit" name="hitung" class="btn btn-success">Hitung</button>
                <button type="button" id="resetButton" class="btn btn-danger ms-2">Reset Data</button>
            </form>
            <!-- Tabel Data Pegawai -->
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Data Pegawai</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table table-hover align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Nama</th>
                                    <th class="text-center">Usia</th>
                                    <th class="text-center">Jenis Kelamin</th>
                                    <?php
                                    // Query untuk mengambil semua kriteria dari tabel criteria
                                    $sql_criteria = "SELECT * FROM criteria";
                                    $result_criteria = $conn->query($sql_criteria);

                                    if ($result_criteria && $result_criteria->num_rows > 0) {
                                        while ($row = $result_criteria->fetch_assoc()) {
                                            ?>
                                            <th class="text-center"><?php echo $row['name']; ?></th>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Query untuk mengambil data karyawan beserta nilai dari sub_criteria yang relevan
                                $sql_karyawan = "SELECT k.nama, k.usia, k.jenis_kelamin";

                                // Menyiapkan array untuk menyimpan nama kriteria
                                $criteria_names = [];

                                // Mengambil nama kriteria dari tabel criteria
                                $result_criteria = $conn->query($sql_criteria);
                                if ($result_criteria && $result_criteria->num_rows > 0) {
                                    while ($row = $result_criteria->fetch_assoc()) {
                                        $criteria_id = $row['id'];
                                        $criteria_name = $row['name'];
                                        $criteria_names[$criteria_id] = $criteria_name; // Menyimpan nama kriteria ke dalam array
                                        $sql_karyawan .= ", (SELECT sc.value FROM sub_criteria sc 
                                                            INNER JOIN karyawan_kriteria kk ON sc.criteria_id = kk.criteria_id 
                                                            WHERE kk.karyawan_id = k.id AND kk.criteria_id = $criteria_id 
                                                            AND sc.value = kk.value LIMIT 1) AS criteria_$criteria_id";
                                    }
                                }

                                $sql_karyawan .= " FROM karyawan k";

                                $result_karyawan = $conn->query($sql_karyawan);

                                if ($result_karyawan && $result_karyawan->num_rows > 0) {
                                    while ($row = $result_karyawan->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $row['nama']; ?></td>
                                            <td class="text-center"><?php echo $row['usia'],' Tahun'; ?></td>
                                            <td class="text-center"><?php echo $row['jenis_kelamin']; ?></td>
                                            <?php
                                            // Menampilkan nilai kriteria berdasarkan nama kriteria
                                            foreach ($criteria_names as $criteria_id => $criteria_name) {
                                                ?>
                                                <td class="text-center">
                                                    <?php echo $row["criteria_$criteria_id"] ?? "N/A"; ?>
                                                </td>
                                                <?php
                                            }
                                            ?>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="<?php echo count($criteria_names) + 3; ?>" class="text-center">Belum ada Data</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <br>
            <!-- Tabel Probabilitas Prior -->
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                    <h6 class="text-white text-capitalize ps-3">Probabilitas Prior</h6>
                </div>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table table-hover align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">Kelas</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-center">Probabilitas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query untuk mengambil data probabilitas prior dari tabel probabilitas_prior
                            $sql_prob_prior = "SELECT * FROM probabilitas_prior";
                            $result_prob_prior = $conn->query($sql_prob_prior);
                            
                            // Menampilkan probabilitas prior
                            if ($result_prob_prior && $result_prob_prior->num_rows > 0) {
                                while ($row = $result_prob_prior->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $row['kelas']; ?></td>
                                        <td class="text-center">
                                            <?php echo $row['jumlah'];?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo number_format($row['probabilitas'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada data probabilitas prior</td>
                                </tr>
                                <?php
                            }
                            
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <br>

        <!-- Tabel Likelihood -->
        <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
            <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Likelihood</h6>
            </div>
        </div>
        <div class="card-body px-0 pb-2">
            <div class="table-responsive p-0">
                <table class="table table-hover align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">Kriteria</th>
                            <th class="text-center">Sub Kriteria</th>
                            <!-- <th class="text-center">Nilai</th> -->
                            <th class="text-center">Naik</th>
                            <th class="text-center">Tidak Naik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query untuk mengambil data likelihood dengan join ke tabel criteria
                        $sql_likelihood = "
                            SELECT l.criteria_id, c.name AS criteria_name, l.kelas, l.likelihood_naik, l.likelihood_tidak_naik
                            FROM likelihood l
                            JOIN criteria c ON l.criteria_id = c.id
                        ";
                        $result_likelihood = $conn->query($sql_likelihood);
                        
                        // Menampilkan likelihood
                        if ($result_likelihood && $result_likelihood->num_rows > 0) {
                            while ($row = $result_likelihood->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $row['criteria_name']; ?></td>
                                    <td class="text-center"><?php echo $row['kelas']; ?></td>
                                    <!-- <td class="text-center"><?php echo $row['nilai']; ?></td> -->
                                    <td class="text-center"><?php echo $row['likelihood_naik']; ?></td>
                                    <td class="text-center"><?php echo $row['likelihood_tidak_naik']; ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data likelihood</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

            <br>
             <!-- Tabel Posterior -->
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                    <h6 class="text-white text-capitalize ps-3">Posterior</h6>
                </div>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table table-hover align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">Nama</th>
                                <th class="text-center">Naik</th>
                                <th class="text-center">Tidak Naik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query untuk mengambil hasil klasifikasi dari tabel posterior
                            $sql_posterior = "SELECT k.nama, p.naik, p.tidak_naik 
                                              FROM posterior p 
                                              JOIN karyawan k ON p.karyawan_id = k.id";
                            $result_posterior = $conn->query($sql_posterior);
                                                        // Menampilkan hasil posterior
                                                        if ($result_posterior && $result_posterior->num_rows > 0) {
                                                          while ($row = $result_posterior->fetch_assoc()) {
                                                              ?>
                                                              <tr>
                                                                  <td class="text-center">
                                                                      <?php echo $row['nama']; ?>
                                                                  </td>
                                                                  <td class="text-center">
                                                                      <?php echo $row['naik']; ?>
                                                                  </td>
                                                                  <td class="text-center">
                                                                      <?php echo $row['tidak_naik']; ?>
                                                                  </td>
                                                              </tr>
                                                              <?php
                                                          }
                                                      } else {
                                                          ?>
                                                          <tr>
                                                              <td colspan="3" class="text-center">Tidak ada data hasil klasifikasi</td>
                                                          </tr>
                                                          <?php
                                                      }
                                                      ?>
                                                  </tbody>
                                              </table>
                                          </div>
                                      </div>
                                  </div>
                              </div>
            <br>
            <!-- Tabel Hasil Klasifikasi -->
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Hasil Klasifikasi</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table table-hover align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Nama</th>
                                    <th class="text-center">Usia</th>
                                    <th class="text-center">Jenis Kelamin</th>
                                    <th class="text-center">Hasil Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_hasil = "SELECT k.*, h.hasil FROM karyawan k LEFT JOIN hasil_klasifikasi h ON k.id = h.karyawan_id";
                                $result_hasil = $conn->query($sql_hasil);
                                if ($result_hasil && $result_hasil->num_rows > 0): ?>
                                    <?php while ($row = $result_hasil->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $row['nama']; ?></td>
                                            <td class="text-center"><?php echo $row['usia'], ' Tahun'; ?></td>
                                            <td class="text-center"><?php echo $row['jenis_kelamin']; ?></td>
                                            <td class="text-center">
                                            <?php
                                                if ($row['hasil'] === 'Naik') {
                                                    echo '<span class="badge bg-success">Naik</span>';
                                                } elseif ($row['hasil'] === 'Tidak Naik') {
                                                    echo '<span class="badge bg-danger">Tidak Naik</span>';
                                                } else {
                                                    echo '<span class="badge bg-warning">Belum klasifikasi</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Belum ada Data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
        <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
            <h6 class="text-white text-capitalize ps-3">Metric Evaluasi Naive Bayes</h6>
        </div>
    </div>
    <div class="card-body px-0 pb-2">
    <div class="chart" style="max-width: 500px; margin: auto;">
            <canvas id="donutChart" height="10px !important;"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-3d"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('donutChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Accuracy', 'Precision', 'Recall', 'F1-score'],
                datasets: [{
                    label: 'Metrik Evaluasi',
                    data: [
                        <?php echo round($accuracy, 2); ?>,
                        <?php echo round($precision_naik, 2); ?>,
                        <?php echo round($recall_naik, 2); ?>,
                        <?php echo round($f1_score_naik, 2); ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Metrik Evaluasi Naive Bayes'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                var label = tooltipItem.label || '';

                                if (label) {
                                    label += ': ';
                                }
                                label += Math.round(tooltipItem.raw);
                                return label;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                },
                elements: {
                    arc: {
                        borderAlign: 'center'
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10,
                        left: 10,
                        right: 10
                    }
                },
                plugins: {
                    doughnutlabel: {
                        labels: [{
                            text: 'Total',
                            font: {
                                size: '60'
                            }
                        }, {
                            text: '100%',
                            font: {
                                size: '50'
                            }
                        }]
                    },
                    threeD: {
                        depth: 15,
                        shadowOffsetX: 5,
                        shadowOffsetY: 5,
                        shadowBlur: 10,
                        shadowColor: 'rgba(0,0,0,0.5)'
                    }
                }
            }
        });

        Chart.plugins.register({
            afterDraw: function(chart) {
                var width = chart.chart.width,
                    height = chart.chart.height,
                    ctx = chart.chart.ctx;

                var fontSize = (height / 114).toFixed(2);
                ctx.font = fontSize + "em sans-serif";
                ctx.textBaseline = "middle";

                var text = Math.round(chart.data.datasets[0].data[0] * 100) / 100 + "%",
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2;

                ctx.fillStyle = '#36A2EB';
                ctx.fillText(text, textX, textY);

                text = Math.round(chart.data.datasets[0].data[1] * 100) / 100 + "%";
                textX = Math.round((width - ctx.measureText(text).width) / 2);
                textY = height / 3;

                ctx.fillStyle = '#FFCE56';
                ctx.fillText(text, textX, textY);

                text = Math.round(chart.data.datasets[0].data[2] * 100) / 100 + "%";
                textX = Math.round((width - ctx.measureText(text).width) / 2);
                textY = height / 1.5;

                ctx.fillStyle = '#FF6384';
                ctx.fillText(text, textX, textY);

                text = Math.round(chart.data.datasets[0].data[3] * 100) / 100 + "%";
                textX = Math.round((width - ctx.measureText(text).width) / 2);
                textY = height / 1.2;

                ctx.fillStyle = '#4BC0C0';
                ctx.fillText(text, textX, textY);
            }
        });
    });
</script>

      <div class="row mt-4">
      <footer class="footer py-4  ">
        <div class="container-fluid">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
              <div class="copyright text-center text-sm text-muted text-lg-start">
                © <script>
                  document.write(new Date().getFullYear())
                </script>
                <a href="#" class="font-weight-bold">Dinas Dukcapil Padang Lawas Utara</a>
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </main>
     <!-- Pastikan Bootstrap JavaScript dan jQuery dimuat sebelum tag </body> -->
<script src="path/to/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.1.0"></script>
  
  <script>
        document.getElementById('logoutBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Anda akan keluar dari Aplikasi ini!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, keluar!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../logout.php';
                }
            });
        });
    </script>
     <script>
        document.getElementById('resetButton').addEventListener('click', function() {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Ingin Reset Data Hasil Perhitungan ini!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Buat form dan submit untuk konfirmasi reset
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="confirm_reset" value="true">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</body>

</html>
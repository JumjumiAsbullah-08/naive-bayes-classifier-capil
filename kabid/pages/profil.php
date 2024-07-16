<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit();
}

// Buat koneksi ke database
$conn = connectDatabase();

$username = $_SESSION['username'];

// Ambil data user dari database berdasarkan username
$query = "SELECT * FROM users WHERE username = '$username'";
$result = $conn->query($query);

if (!$result) {
    die("Query Error: " . $conn->error);
}

// Periksa apakah data ditemukan
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $role = $row['role'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/logo.png">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <title>
  Kabid | Dukcapil
  </title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
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

<body class="g-sidenav-show bg-gray-200">
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
          <a class="nav-link text-whites" href="../pages/pegawai.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">group</i>
            </div>
            <span class="nav-link-text ms-1">Manajemen Pegawai</span>
          </a>
        </li>
        <li class="nav-item">
        <a class="nav-link text-white " href="../pages/klasifikasi.php">
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
          <a class="nav-link text-white active bg-gradient-primary" href="../pages/profil.php">
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
  <div class="main-content position-relative max-height-vh-100 h-100">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;">Pages</a></li>
            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Profile</li>
          </ol>
          <h6 class="font-weight-bolder mb-0">Profile</h6>
        </nav>
      </div>
    </nav>
    <!-- End Navbar -->
    <div class="container-fluid px-2 px-md-4">
        <div class="page-header min-height-300 border-radius-xl mt-4" style="background-image: url('../assets/img/candi.jfif');">
            <span class="mask bg-gradient-primary  opacity-6"></span>
        </div>
        <div class="card card-body mx-3 mx-md-4 mt-n6">
            <div class="row gx-4 mb-2">
                <div class="col-auto">
                    <div class="avatar avatar-xl position-relative">
                        <img src="../assets/img/logo.png" style="width:40px; height:90px !important;" alt="profile_image" class="w-100 border-radius-lg shadow-sm">
                    </div>
                </div>
                <div class="col-auto my-auto">
                    <div class="h-100">
                        <h5 class="mb-1">
                            <?php echo $row['username']; ?>
                        </h5>
                        <p class="mb-0 font-weight-normal text-sm">
                            Role : <?php echo ucfirst($role); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <h6 class="text-uppercase text-muted mb-3">Latar Belakang Dinas Dukcapil</h6>    
                    <p style="text-align: justify">Pada awal abad XIX, kota Batavia (Jakarta) mengalami perkembangan pesat di bidang pemerintahan dan perdagangan pada masa kolonial Belanda. Infrastruktur seperti pusat ibadah, gedung kesenian, kantor pos, dan lapangan Banteng hingga Monas menandakan Batavia sebagai kota yang berpotensi menjadi ibukota. Seiring pembangunan tersebut, kebutuhan akan administrasi kependudukan dan pencatatan sipil (Burgerlijk Stand) mulai terorganisir, dengan dokumen akta pencatatan sipil pertama kali muncul pada tahun 1829.</p>
                    <p style="text-align: justify">Pelayanan Burgerlijk Stand diatur oleh peraturan Belanda (asas konkordansi), berlaku bagi warga Belanda, Eropa, dan Amerika. Ordonansi pencatatan sipil pertama di Hindia Belanda diberlakukan pada tahun 1850, mengatur daftar pencatatan sipil untuk Bangsa Eropa, Pribumi, dan Timur Asing.</p>
                    <p style="text-align: justify">Pada masa pendudukan Jepang (1942-1945), pelayanan catatan sipil dikenal dengan istilah "Cacah Jiwa" dan lembaganya disebut "Kantor Pencacah Jiwa". Setelah kemerdekaan RI, pengelolaan catatan sipil diambil alih oleh Pemerintah Indonesia, dan lembaga Burgerlijk Stand (BS) berlanjut sebagai Kantor Catatan Sipil, dengan istilah "Catatan Sipil" dipilih dalam Kongres Bahasa kedua di Medan pada tahun 1950.</p>
                    <p style="text-align: justify">Di Jakarta, Kantor Catatan Sipil Batavia dan Mister Cornelis bergabung menjadi Kantor Catatan Sipil DCI Jakarta Raya setelah status Jakarta menjadi Daerah Istimewa Tingkat I pada tahun 1957. Era Orde Baru (dari 1965) membawa perubahan signifikan dalam penyelenggaraan catatan sipil, dengan Instruksi Presidium Kabinet Ampera No. 31/In/U/12/66 yang membuka layanan catatan sipil untuk semua warga negara, baik Indonesia maupun asing.</p>
                </div>
            </div>

        </div>
    </div>
    <?php
} else {
    echo "User tidak ditemukan.";
}
?>
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
  <!--   Core JS Files   -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="../assets/js/material-dashboard.min.js?v=3.1.0"></script>
</body>

</html>
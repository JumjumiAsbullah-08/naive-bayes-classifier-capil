<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'kabid') {
    header("Location: ../../index.php");
    exit();
}

// Buat koneksi ke database
$conn = connectDatabase();

// Ambil semua kriteria dari database
$criteria_result = $conn->query("SELECT * FROM criteria");

// Proses penyimpanan sub-kriteria
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Jika form edit sub-kriteria disubmit
    if (isset($_POST['sub_criteria_id']) && isset($_POST['name']) && isset($_POST['value'])) {
        $sub_criteria_id = $_POST['sub_criteria_id'];
        $name = $_POST['name'];
        $value = $_POST['value'];

        $sql = "UPDATE sub_criteria SET name = '$name', value = '$value' WHERE id = $sub_criteria_id";

        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sub-Kriteria berhasil diperbarui',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function() {
                            window.location.href = 'sub_kriteria.php';
                        });
                    });
                  </script>";
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }

    // Jika form tambah sub-kriteria disubmit
    if (isset($_POST['criteria_id']) && isset($_POST['name']) && isset($_POST['value'])) {
        $criteria_id = $_POST['criteria_id'];
        $name = $_POST['name'];
        $value = $_POST['value'];

        $sql = "INSERT INTO sub_criteria (criteria_id, name, value) VALUES ('$criteria_id', '$name', '$value')";
        
        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sub-Kriteria berhasil ditambahkan',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function() {
                            window.location.href = 'sub_kriteria.php';
                        });
                    });
                  </script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Ambil ulang data kriteria setelah proses update atau tambah sub-kriteria
$criteria_result = $conn->query("SELECT * FROM criteria");

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
          <a class="nav-link text-white active bg-gradient-primary" href="../pages/sub_kriteria.php">
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
              <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Sub Kriteria</li>
            </ol>
            <h6 class="font-weight-bolder mb-0">Sub Kriteria</h6>
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
    <?php
if ($criteria_result->num_rows > 0) {
    while ($criteria = $criteria_result->fetch_assoc()) {
        echo "<div class='card my-4'>
                <div class='card-header p-0 position-relative mt-n4 mx-3 z-index-2'>
                    <div class='bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3'>
                        <div class='d-flex justify-content-between align-items-center'>
                            <h6 class='text-white ps-3 mb-0'>{$criteria['name']} ({$criteria['kode_criteria']})</h6>
                        </div>
                    </div>
                </div>
                <div class='card-body px-0 pb-2'>
                    <div class='table-responsive p-0'>
                        <table class='table align-items-center table-hover mb-0'>
                            <thead>
                                <tr>
                                    <th class='text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12'>Nama Sub Kriteria</th>
                                    <th class='text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12'>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>";
        
        // Ambil sub kriteria dari database berdasarkan criteria_id
        $sub_criteria_result = $conn->query("SELECT * FROM sub_criteria WHERE criteria_id = {$criteria['id']}");
        if ($sub_criteria_result->num_rows > 0) {
            while ($sub = $sub_criteria_result->fetch_assoc()) {
                echo "<tr>
                        <td class='text-center'>{$sub['name']}</td>
                        <td class='text-center'>{$sub['value']}</td>
                      </tr>";
                
                // Modal Edit Sub Kriteria
                echo "<div class='modal fade' id='editSubCriteriaModal_{$sub['id']}' tabindex='-1' aria-labelledby='editSubCriteriaModalLabel_{$sub['id']}' aria-hidden='true'>
                        <div class='modal-dialog'>
                            <div class='modal-content'>
                                <div class='modal-header'>
                                    <h5 class='modal-title' id='editSubCriteriaModalLabel_{$sub['id']}'>Edit Sub Kriteria {$sub['name']}</h5>
                                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                        <span aria-hidden='true'>&times;</span>
                                    </button>
                                </div>
                                <div class='modal-body'>
                                    <form action='sub_kriteria.php' method='POST'>
                                        <input type='hidden' name='sub_criteria_id' value='{$sub['id']}'>
                                        <div class='input-group input-group-outline mb-3'>
                                            <label for='name' class='form-label'>Nama Sub Kriteria:</label>
                                            <input type='text' id='name' name='name' class='form-control' value='{$sub['name']}' required>
                                        </div>
                                        <div class='input-group input-group-outline mb-3'>
                                            <label for='value' class='form-label'>Nilai:</label>
                                            <input type='number' id='value' name='value' class='form-control' value='{$sub['value']}' required>
                                        </div>
                                        <button type='submit' class='btn btn-info'>Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>";
            }
        } else {
            echo "<tr><td colspan='3' class='text-center'>Belum ada Sub Kriteria</td></tr>";
        }
        
        echo "          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>";

        // Modal Tambah Sub Kriteria
        echo "<div class='modal fade' id='addSubCriteriaModal_{$criteria['id']}' tabindex='-1' aria-labelledby='addSubCriteriaModalLabel_{$criteria['id']}' aria-hidden='true'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title' id='addSubCriteriaModalLabel_{$criteria['id']}'>Tambah Sub Kriteria untuk {$criteria['name']}</h5>
                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'></button>
                        </div>
                        <div class='modal-body'>
                            <form action='sub_kriteria.php' method='POST'>
                                <input type='hidden' name='criteria_id' value='{$criteria['id']}'>
                                <div class='input-group input-group-outline mb-3'>
                                    <label for='name' class='form-label'>Nama Sub Kriteria:</label>
                                    <input type='text' id='name' name='name' class='form-control' required>
                                </div>
                                <div class='input-group input-group-outline mb-3'>
                                    <label for='value' class='form-label'>Nilai:</label>
                                    <input type='number' id='value' name='value' class='form-control' required>
                                </div>
                                <button type='submit' class='btn btn-primary'>Simpan Sub Kriteria</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>";
    }
} else {
    echo "<p>Belum ada kriteria</p>";
}
?>

        <!-- JavaScript untuk konfirmasi penghapusan kriteria -->
        <script>
            function confirmDeleteSub(subCriteriaId) {
                if (confirm('Anda yakin ingin menghapus sub kriteria ini?')) {
                    window.location.href = `delete_sub_criteria.php?id=${subCriteriaId}`;
                }
            }
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
  
  <!--   Core JS Files   -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>
  <!-- Github buttons -->
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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
</body>
</body>

</html>
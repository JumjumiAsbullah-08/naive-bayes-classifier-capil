<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

$conn = connectDatabase();

$result = $conn->query("SELECT * FROM hasil_klasifikasi");
if (!$result) {
    die("Query Error: " . $conn->error);
}

// Query untuk mengambil data hasil klasifikasi bersama dengan nama karyawan
$sql = "SELECT hk.id, hk.karyawan_id, k.nama, hk.hasil, hk.tanggal, hk.status
        FROM hasil_klasifikasi hk
        JOIN karyawan k ON hk.karyawan_id = k.id";

$result = $conn->query($sql);
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
    Admin | Dukcapil
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
          <a class="nav-link text-white" href="../pages/pegawai.php">
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
          <a class="nav-link text-white active bg-gradient-primary" href="../pages/laporan.php">
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
          <a class="nav-link text-white" href="../pages/users.php">
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
              <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Laporan</li>
            </ol>
            <h6 class="font-weight-bolder mb-0">Laporan</h6>
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
    <div class="container-fluid py-4">
    <div class="row p-3">
        <div class="col-md-2">
            <label for="filterStatus" class="form-label">Filter Status:</label>
                <div class="input-group input-group-outline mb-3">
                    <select class="form-control" id="filterStatus" onchange="filterTable()">
                        <option value="all">Semua</option>
                        <option value="naik">Naik</option>
                        <option value="tidak_naik">Tidak Naik</option>
                    </select>
                </div>
        </div>
    <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-success" onclick="cetakLaporan()">Cetak Laporan</button>
    </div>
    </div>
    <div class="row">
        <div class="col-12">
          <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
              <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Laporan Klasifikasi Karyawan</h6>
              </div>
            </div>
            <div class="card-body px-0 pb-2">
              <div class="table-responsive p-0">
                <table class="table align-items-center mb-0" id="laporanTable">
                  <thead>
                    <tr>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">#</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Karyawan ID</th>
                      <!-- <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Hasil</th> -->
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Tanggal</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="laporanRow" data-status="<?php echo strtolower($row['hasil'] == 'Naik' ? 'naik' : 'tidak_naik'); ?>">
                            <td class="text-center"><?php echo $row['id']; ?></td>
                            <td class="text-center"><?php echo $row['nama']; ?></td>
                            <td class="text-center"><?php echo $row['tanggal']; ?></td>
                            <td class="text-center"><?php echo $row['hasil'] == 'Naik' ? 'Naik' : 'Tidak Naik'; ?></td>
                            <!-- <td class="text-center"><?php echo $row['status']; ?></td> -->
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Belum ada Data</td>
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

<script>
function filterTable() {
    var filter = document.getElementById('filterStatus').value;
    var rows = document.querySelectorAll('.laporanRow');
    console.log("Filter:", filter);  // Debugging line

    rows.forEach(row => {
        console.log("Row data-status:", row.getAttribute('data-status'));  // Debugging line
        if (filter === 'all') {
            row.style.display = '';
        } else if (row.getAttribute('data-status') === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}


function cetakLaporan() {
    var filter = document.getElementById('filterStatus').value;
    var table = document.getElementById('laporanTable').outerHTML;
    var printWindow = window.open('', '_blank');
    var today = new Date();
    var date = today.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

    printWindow.document.write('<html><head><title>Laporan Klasifikasi Karyawan</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
    printWindow.document.write('th, td { border: 1px solid #000; padding: 8px; text-align: center; }');
    printWindow.document.write('.header { display: flex; align-items: center; margin-bottom: 20px; }');
    printWindow.document.write('.header img { width: 100px; }');
    printWindow.document.write('.header div { flex-grow: 1; text-align: center; }');
    printWindow.document.write('.header .kode-pos { text-align: right; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="header">');
    printWindow.document.write('<img src="../assets/img/logo.png" alt="Logo">');
    printWindow.document.write('<div>');
    printWindow.document.write('<h3>PEMERINTAH KABUPATEN PADANG LAWAS UTARA</h3>');
    printWindow.document.write('<h3>DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL</h3>');
    printWindow.document.write('<p>Jl. Gunungtua - PadangSidimpuan Km. 3,5 Telp (0635) 510810 Faks. (0635) 510001</p>');
    printWindow.document.write('<p>GUNUNGTUA</p>');
    printWindow.document.write('</div>');
    // printWindow.document.write('<div class="kode-pos">');
    // printWindow.document.write('<p>Kode Pos : 22753</p>');
    // printWindow.document.write('</div>');
    printWindow.document.write('</div>');
    printWindow.document.write('<hr style="border-width: 3px; color: black; !important;">');
    printWindow.document.write('<h3>Laporan Klasifikasi Karyawan - ' + (filter === 'all' ? 'Semua Status' : (filter === 'naik' ? 'Naik' : 'Tidak Naik')) + '</h3>');
    printWindow.document.write(table);
    printWindow.document.write('<p>Gunungtua, ' + date + '</p>');
    printWindow.document.write('<p>Kepala Bidang Dinas Kependudukan dan Pencatatan Sipil</p>');
    printWindow.document.write('<br><br><br>');
    printWindow.document.write('<p>(Sutan Soripada Naburju, M. Ag)</p>');
    printWindow.document.write('<p>NIP. 20217899837556367</p>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
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
  <script>
  function deleteUsers(id) {
      if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
          window.location.href = 'delete_user.php?id=' + id;
      }
  }
</script>

  
  <!--   Core JS Files   -->
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
</body>
</body>

</html>
<?php
session_start();
require_once "../../koneksi.php";
// Koneksi ke database
$conn = connectDatabase();

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'kabid') {
    header("Location: ../../index.php");
    exit();


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
  <title>
    Kabid | Dukcapil
  </title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-3d@3.1.0/dist/chartjs-chart-3d.min.js"></script> -->
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
          <a class="nav-link text-white active bg-gradient-primary" href="../pages/dashboard.php">
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
            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Dashboard</li>
          </ol>
          <h6 class="font-weight-bolder mb-0">Dashboard</h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
          <div class="ms-md-auto pe-md-3 d-flex align-items-center">
            <!-- <div class="input-group input-group-outline">
              <label class="form-label">Type here...</label>
              <input type="text" class="form-control">
            </div> -->
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
      <div class="row">
      <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-header p-3 pt-2">
                <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                    <i class="material-icons opacity-10">apps</i>
                </div>
                <div class="text-end pt-1">
                    <p class="text-sm mb-0 text-capitalize">Data Kriteria</p>
                    <h4 class="mb-0">
                        <?php
                        $sql_criteria_count = "SELECT COUNT(*) as count FROM criteria";
                        $result_criteria_count = $conn->query($sql_criteria_count);
                        if ($result_criteria_count && $result_criteria_count->num_rows > 0) {
                            $row = $result_criteria_count->fetch_assoc();
                            echo $row['count'];
                        } else {
                            echo "0";
                        }
                        ?>
                    </h4>
                </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
                <a href="kriteria.php" class="mb-0"><span class="text-success text-sm font-weight-bolder">Visit</span></a>
            </div>
        </div>
    </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">groups</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Data Set</p>
                <h4 class="mb-0">
                  <?php
                    $sql_criteria_data_set = "SELECT COUNT(*) as data_set FROM karyawan";
                    $result_criteria_data_set = $conn->query($sql_criteria_data_set);
                      if ($result_criteria_data_set && $result_criteria_data_set->num_rows > 0) {
                        $row = $result_criteria_data_set->fetch_assoc();
                        echo $row['data_set'];
                      } else {
                        echo "0";
                      }
                  ?>
                </h4>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
            <a href="pegawai.php" class="mb-0"><span class="text-success text-sm font-weight-bolder">Visit</a>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">engineering</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Users</p>
                <h4 class="mb-0">
                  <?php
                    $sql_user = "SELECT COUNT(*) as user FROM users";
                    $result_users = $conn->query($sql_user);
                      if ($result_users && $result_users->num_rows > 0) {
                        $row = $result_users->fetch_assoc();
                        echo $row['user'];
                      } else {
                        echo "0";
                      }
                  ?>
                </h4>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
            <a href="#" class="mb-0"><span class="text-success text-sm font-weight-bolder">Visit</a>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">visibility</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Hasil Klasifikasi</p>
                <h6 class="mb-0">
                <?php
                // Query untuk menghitung jumlah 'Naik' dan 'Tidak Naik' dari hasil klasifikasi
                $sql_count = "SELECT 
                                SUM(CASE WHEN hasil = 'Naik' THEN 1 ELSE 0 END) AS naik_count,
                                SUM(CASE WHEN hasil = 'Tidak Naik' THEN 1 ELSE 0 END) AS tidak_naik_count
                            FROM hasil_klasifikasi";
                $result_count = $conn->query($sql_count);

                // Output hasil perhitungan
                if ($result_count && $result_count->num_rows > 0) {
                    $row = $result_count->fetch_assoc();
                    echo "Naik: " . $row['naik_count'] . " <br> Tidak Naik: " . $row['tidak_naik_count'];
                } else {
                    echo "Naik: 0 | Tidak Naik: 0";
                }
                ?>
                </h6>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
            <a href="klasifikasi.php" class="mb-0"><span class="text-success text-sm font-weight-bolder">Visit</a>
            </div>
          </div>
        </div>
      </div>
      <div class="container-fluid py-4">
    <div class="row">
        <div class="col-xl-6 col-sm-12 mb-xl-0 mb-4">
            <div class="card">
            <h6 class="text-center text-capitalize ps-3">Grafik Jumlah Data dan Klasifikasi Naik/Tidak Naik</h6>
                <div id="chart-container1" style="width: 100%; height: 600px;">
                    <canvas id="myChart1"></canvas>
                </div>
                <?php
                // Fetch data from the database
                $sql_criteria_count = "SELECT COUNT(*) as count FROM criteria";
                $result_criteria_count = $conn->query($sql_criteria_count);
                $criteria_count = ($result_criteria_count && $result_criteria_count->num_rows > 0) ? $result_criteria_count->fetch_assoc()['count'] : 0;

                $sql_criteria_data_set = "SELECT COUNT(*) as data_set FROM karyawan";
                $result_criteria_data_set = $conn->query($sql_criteria_data_set);
                $data_set_count = ($result_criteria_data_set && $result_criteria_data_set->num_rows > 0) ? $result_criteria_data_set->fetch_assoc()['data_set'] : 0;

                $sql_user = "SELECT COUNT(*) as user FROM users";
                $result_users = $conn->query($sql_user);
                $user_count = ($result_users && $result_users->num_rows > 0) ? $result_users->fetch_assoc()['user'] : 0;

                $sql_count = "SELECT 
                SUM(CASE WHEN hasil = 'Naik' THEN 1 ELSE 0 END) AS naik_count,
                SUM(CASE WHEN hasil = 'Tidak Naik' THEN 1 ELSE 0 END) AS tidak_naik_count
                FROM hasil_klasifikasi";
                $result_count = $conn->query($sql_count);

                // Memproses hasil query
                if ($result_count && $result_count->num_rows > 0) {
                $classification_counts = $result_count->fetch_assoc();
                } else {
                $classification_counts = ['naik_count' => 0, 'tidak_naik_count' => 0];
                }
                // Set colors for bars
                $colors = ['rgb(58, 71, 80)', 'rgb(100, 150, 200)', 'rgb(255, 193, 7)', 'rgb(0, 255, 0)', 'rgb(255, 0, 0)'];
                ?>
                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                      const ctx = document.getElementById('myChart1').getContext('2d');
                      const chartData = {
                          labels: ['Data Kriteria', 'Data Set', 'Users', 'Naik', 'Tidak Naik'],
                          datasets: [{
                              label: 'Jumlah',
                              data: [
                                  <?php echo $criteria_count; ?>, 
                                  <?php echo $data_set_count; ?>, 
                                  <?php echo $user_count; ?>, 
                                  <?php echo $classification_counts['naik_count']; ?>, 
                                  <?php echo $classification_counts['tidak_naik_count']; ?>
                              ],
                              backgroundColor: <?php echo json_encode($colors); ?> // Set colors dynamically or statically
                          }]
                      };

                      const myChart1 = new Chart(ctx, {
                          type: 'bar', // Use 'bar' as the chart type
                          data: chartData,
                          options: {
                            responsive: true,
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Kategori'
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Jumlah'
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.raw;
                                        }
                                    }
                                }
                            },
                            animation: {
                                duration: 1500,
                                easing: 'easeInOutQuart'
                            },
                            maintainAspectRatio: false,
                            title: {  // Tambahkan bagian title di sini
                                display: true,
                                text: 'Grafik Jumlah Data dan Klasifikasi Naik/Tidak Naik'
                            }
                        },
                      });
                  });
                </script>
            </div>
        </div>
        <div class="col-xl-6 col-sm-12">
          <div class="card">
            <h6 class="text-center text-capitalize ps-3">Grafik Metriks Evaluasi Klasifikasi</h6>
            <div id="chart-container2" style="width: 100%; height: 600px;">
                <canvas id="myChart2"></canvas>
            </div>
            <?php
            // Masukkan klasifikasi.php untuk mengakses variabel-variabel yang sudah dihitung
            require_once 'hitung.php';

            $data_pie = [];

            if (isset($accuracy)) {
                $data_pie['Accuracy'] = round($accuracy, 2);
            }
            
            if (isset($precision_naik)) {
                $data_pie['Precision'] = round($precision_naik, 2);
            }
            
            if (isset($recall_naik)) {
                $data_pie['Recall'] = round($recall_naik, 2);
            }
            
            if (isset($f1_score_naik)) {
                $data_pie['F1-score'] = round($f1_score_naik, 2);
            }
            
            // Encode data pie ke format JSON untuk digunakan dalam JavaScript
            $data_pie_json = json_encode($data_pie);
            ?>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Ambil elemen canvas untuk grafik
                const ctx2 = document.getElementById('myChart2').getContext('2d');
                
                // Data untuk grafik pie (gunakan PHP untuk menyisipkan data)
                const dataPie = <?php echo $data_pie_json; ?>;
                
                // Labels dari data pie
                const labels = Object.keys(dataPie);
                
                // Nilai dari data pie
                const data = Object.values(dataPie);

                // Warna dari setiap bagian pie
                const backgroundColors = [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                ];

                // Buat grafik pie
                const myPieChart = new Chart(ctx2, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Metrik Evaluasi',
                            data: data,
                            backgroundColor: backgroundColors,
                        }]
                    },
                    options: {
                      responsive: true,
                      plugins: {
                          tooltip: {
                              callbacks: {
                                  label: function(tooltipItem) {
                                      return tooltipItem.label + ': ' + tooltipItem.raw.toFixed(2); // Tampilkan nilai dengan 2 desimal
                                  }
                              }
                          }
                      },
                      title: {  // Tambahkan bagian title di sini
                          display: true,
                          text: 'Grafik Evaluasi Klasifikasi'
                      }
                  }
                });
            });
            </script>
    </div>
</div>

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
  <!-- <script src="../assets/js/plugins/chartjs.min.js"></script> -->
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
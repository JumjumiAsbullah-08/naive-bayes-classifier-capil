<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

$conn = connectDatabase();

// Fungsi untuk mengambil semua kriteria
function getAllCriteria($conn) {
    $sql = "SELECT * FROM criteria";
    $result = $conn->query($sql);

    // Periksa apakah query berhasil
    if (!$result) {
        die("Query Error: " . $conn->error);
    }

    $criteria = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $criteria[] = $row;
        }
    }
    return $criteria;
}

// Fungsi untuk mengambil sub-kriteria berdasarkan ID kriteria
function getSubCriteriaByCriteriaId($conn, $criteria_id) {
    $sql = "SELECT * FROM sub_criteria WHERE criteria_id = ?";
    $stmt = $conn->prepare($sql);

    // Periksa apakah statement berhasil diprepare
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $criteria_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Periksa apakah query berhasil
    if (!$result) {
        die("Query Error: " . $stmt->error);
    }

    $sub_criteria = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sub_criteria[] = $row;
        }
    }
    return $sub_criteria;
}

// Fungsi untuk mengisi tabel training_data
function isiTrainingData($conn) {
    // Hapus data training sebelumnya
    if (!$conn->query("TRUNCATE TABLE training_data")) {
        die("Error truncating training_data: " . $conn->error);
    }
    
    // Ambil data dari karyawan_kriteria
    $sql_kriteria = "SELECT karyawan_id, SUM(value) as total_value FROM karyawan_kriteria GROUP BY karyawan_id";
    $result_kriteria = $conn->query($sql_kriteria);
    if (!$result_kriteria) {
        die("Error querying karyawan_kriteria: " . $conn->error);
    }
    
    // Masukkan data ke dalam training_data
    while ($row = $result_kriteria->fetch_assoc()) {
        $status = ($row['total_value'] > 10) ? 'Naik' : 'Tidak Naik';
        $sql_insert = "INSERT INTO training_data (karyawan_id, status) VALUES ({$row['karyawan_id']}, '$status')";
        if (!$conn->query($sql_insert)) {
            die("Error inserting into training_data: " . $conn->error);
        }
    }
}

// Ambil semua data karyawan dari database
$result = $conn->query("SELECT * FROM karyawan");
if (!$result) {
    die("Query Error: " . $conn->error);
}

// Ambil semua kriteria dan sub-kriteria
$criteria = getAllCriteria($conn);
$sub_criteria = [];
foreach ($criteria as $c) {
    $sub_criteria[$c['id']] = getSubCriteriaByCriteriaId($conn, $c['id']);
}

// Proses penyimpanan data pegawai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nama'])) {
    $nama = $_POST['nama'];
    $usia = $_POST['usia'];
    $jenis_kelamin = $_POST['jenis_kelamin'];

    // Inisialisasi array untuk nilai kriteria
    $kriteria_values = [];
    foreach ($criteria as $c) {
        $kriteria_id = $c['id'];
        if (isset($_POST['kriteria_' . $kriteria_id]) && isset($_POST['value_' . $kriteria_id])) {
            $sub_criteria_id = $_POST['kriteria_' . $kriteria_id];
            $value = $_POST['value_' . $kriteria_id];

            if ($value === null || $value === '') {
                die("Error: Nilai untuk kriteria ID {$kriteria_id} tidak boleh kosong.");
            }

            // Periksa apakah sub_criteria_id valid
            $isValidSubCriteria = false;
            foreach ($sub_criteria[$kriteria_id] as $sub) {
                if ($sub['id'] == $sub_criteria_id && $sub['value'] == $value) {
                    $isValidSubCriteria = true;
                    break;
                }
            }

            if (!$isValidSubCriteria) {
                die("Error: Sub-kriteria ID {$sub_criteria_id} tidak valid untuk kriteria ID {$kriteria_id}.");
            }

            $kriteria_values[$kriteria_id] = [
                'sub_criteria_id' => $sub_criteria_id,
                'value' => $value
            ];
        } else {
            die("Error: Kriteria ID {$kriteria_id} tidak lengkap.");
        }
    }

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // Simpan data pegawai
        $sql = "INSERT INTO karyawan (nama, usia, jenis_kelamin) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("sis", $nama, $usia, $jenis_kelamin);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $karyawan_id = $stmt->insert_id;

        // Simpan data kriteria pegawai
        foreach ($kriteria_values as $kriteria_id => $data) {
            $sub_criteria_id = $data['sub_criteria_id'];
            $value = $data['value'];

            // Lakukan insert ke dalam tabel karyawan_kriteria
            $sql = "INSERT INTO karyawan_kriteria (karyawan_id, criteria_id, sub_criteria_id, value) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("iiid", $karyawan_id, $kriteria_id, $sub_criteria_id, $value);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
        }

        // Isi tabel training_data
        isiTrainingData($conn);

        // Commit transaksi
        $conn->commit();

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Data pegawai berhasil ditambahkan',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        window.location.href = 'pegawai.php';
                    });
                });
              </script>";
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi kesalahan
        $conn->rollback();
        echo "Error: " . $e->getMessage();
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
          <a class="nav-link text-white active bg-gradient-primary" href="../pages/pegawai.php">
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
              <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Pegawai</li>
            </ol>
            <h6 class="font-weight-bolder mb-0">Pegawai</h6>
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
    <div class="row">
        <div class="col-12">
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addDataModal">Tambah Data</button>
          <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
              <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Data Pegawai</h6> 
              </div>
            </div>
            <div class="card-body px-0 pb-2">
              <div class="table-responsive p-0">
              <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Nama</th>
                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Usia</th>
                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Jenis Kelamin</th>
                        <?php foreach ($criteria as $c): ?>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12"><?php echo $c['name']; ?></th>
                        <?php endforeach; ?>
                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-12">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                  <?php if ($result->num_rows > 0): ?>
                      <?php while($row = $result->fetch_assoc()): ?>
                          <tr>
                              <td class="text-center"><?php echo htmlspecialchars($row['nama']); ?></td>
                              <td class="text-center"><?php echo htmlspecialchars($row['usia']); ?></td>
                              <td class="text-center"><?php echo htmlspecialchars($row['jenis_kelamin']); ?></td>
                              <?php
                              foreach ($criteria as $c):
                                  $criteria_id = $c['id'];
                                  $sql = "SELECT value FROM karyawan_kriteria WHERE karyawan_id = ? AND criteria_id = ?";
                                  $stmt = $conn->prepare($sql);
                                  if ($stmt === false) {
                                      die('Prepare failed: ' . $conn->error);
                                  }
                                  $stmt->bind_param("ii", $row['id'], $criteria_id);
                                  if (!$stmt->execute()) {
                                      die('Execute failed: ' . $stmt->error);
                                  }
                                  $criteria_result = $stmt->get_result();
                                  if ($criteria_result->num_rows > 0) {
                                      $criteria_value = $criteria_result->fetch_assoc()['value'];
                                  } else {
                                      $criteria_value = null; // handle if no value found
                                  }
                                  $stmt->close();

                                  // Cari sub-kriteria berdasarkan nilai
                                  $sub_criteria_value = "N/A";
                                  foreach ($sub_criteria[$criteria_id] as $sub) {
                                      if ($sub['value'] == $criteria_value) {
                                          $sub_criteria_value = htmlspecialchars($sub['name']);
                                          break;
                                      }
                                  }
                              ?>
                                  <td class="text-center"><?php echo $sub_criteria_value; ?></td>
                              <?php endforeach; ?>
                              <td class="text-center">
                                  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editEmployeeModal<?php echo $row['id']; ?>">Edit</button>
                                  <button class="btn btn-danger" onclick="deleteEmployee(<?php echo $row['id']; ?>)">Hapus</button>
                              </td>
                          </tr>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <tr>
                          <td colspan="<?php echo count($criteria) + 4; ?>" class="text-center">Belum ada Data</td>
                      </tr>
                  <?php endif; ?>
              </tbody>

            </table>

              </div>
            </div>
          </div>
        </div>
      </div>

<!-- Modal -->
<div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDataModalLabel">Tambah Data Pegawai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addEmployeeForm" action="pegawai.php" method="POST">
                    <!-- Nama -->
                    <div class="input-group input-group-outline mb-3">
                        <label for="nama" class="form-label">Nama:</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                    <!-- Usia -->
                    <div class="input-group input-group-outline mb-3">
                        <label for="usia" class="form-label">Usia:</label>
                        <input type="number" class="form-control" id="usia" name="usia" required>
                    </div>
                    <!-- Jenis Kelamin -->
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin:</label>
                    <div class="input-group input-group-outline mb-3">
                        <select class="form-control" id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="Pria">Pria</option>
                            <option value="Wanita">Wanita</option>
                        </select>
                    </div>

                    <!-- Loop untuk Kriteria dan Sub-Kriteria -->
                    <?php
                    foreach ($criteria as $c) {
                        echo "<label for='kriteria_{$c['id']}' class='form-label'>{$c['name']}:</label>";
                        echo "<div class='input-group input-group-outline mb-3'>";
                        echo "<select class='form-control' id='kriteria_{$c['id']}' name='kriteria_{$c['id']}' required>";
                        if (isset($sub_criteria[$c['id']])) {
                            foreach ($sub_criteria[$c['id']] as $sub) {
                                // Mengirimkan sub_criteria_id dan value
                                echo "<option value='{$sub['id']}' data-value='{$sub['value']}'>{$sub['name']}</option>";
                            }
                        }
                        echo "</select>";
                        echo "<input type='hidden' name='value_{$c['id']}' id='value_{$c['id']}' value=''>";
                        echo "</div>";
                    }
                    ?>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($criteria as $c): ?>
        document.getElementById('kriteria_<?php echo $c['id']; ?>').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var value = selectedOption.getAttribute('data-value');
            document.getElementById('value_<?php echo $c['id']; ?>').value = value;
        });
    <?php endforeach; ?>
});
</script>

<?php
// Query to get employee data for the modal
$employees = $conn->query("SELECT * FROM karyawan");
while ($employee = $employees->fetch_assoc()) {
?>
<div class="modal fade" id="editEmployeeModal<?php echo $employee['id']; ?>" tabindex="-1" aria-labelledby="editEmployeeModalLabel<?php echo $employee['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEmployeeModalLabel<?php echo $employee['id']; ?>">Edit Data Pegawai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editEmployeeForm<?php echo $employee['id']; ?>" action="edit_pegawai.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                    <!-- Nama -->
                    <div class="input-group input-group-outline mb-3">
                        <label for="nama" class="form-label">Nama:</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?php echo $employee['nama']; ?>" required>
                    </div>
                    <!-- Usia -->
                    <div class="input-group input-group-outline mb-3">
                        <label for="usia" class="form-label">Usia:</label>
                        <input type="number" class="form-control" id="usia" name="usia" value="<?php echo $employee['usia']; ?>" required>
                    </div>
                    <!-- Jenis Kelamin -->
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin:</label>
                    <div class="input-group input-group-outline mb-3">
                        <select class="form-control" id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="Pria" <?php echo ($employee['jenis_kelamin'] == 'Pria') ? 'selected' : ''; ?>>Pria</option>
                            <option value="Wanita" <?php echo ($employee['jenis_kelamin'] == 'Wanita') ? 'selected' : ''; ?>>Wanita</option>
                        </select>
                    </div>

                    <!-- Loop untuk Kriteria dan Sub-Kriteria -->
                    <?php
                    foreach ($criteria as $c) {
                        echo "<label for='kriteria_{$c['id']}' class='form-label'>{$c['name']}:</label>";
                        echo "<div class='input-group input-group-outline mb-3'>";
                        echo "<select class='form-control' id='kriteria_{$c['id']}' name='kriteria_{$c['id']}' required>";
                        if (isset($sub_criteria[$c['id']])) {
                            foreach ($sub_criteria[$c['id']] as $sub) {
                                // Fetching existing value for the sub criteria
                                $sql = "SELECT sub_criteria_id FROM karyawan_kriteria WHERE karyawan_id = ? AND criteria_id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("ii", $employee['id'], $c['id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $existing_value = $result->fetch_assoc();
                                
                                $selected = ($existing_value['sub_criteria_id'] == $sub['id']) ? 'selected' : '';
                                echo "<option value='{$sub['id']}' {$selected}>{$sub['name']}</option>";
                            }
                        }
                        echo "</select>";
                        echo "</div>";
                    }
                    ?>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
}
?>



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
  function deleteEmployee(id) {
      if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
          window.location.href = 'delete_pegawai.php?id=' + id;
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
<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

$conn = connectDatabase();

// Ambil data dari form
$id = $_POST['id'];
$nama = $_POST['nama'];
$usia = $_POST['usia'];
$jenis_kelamin = $_POST['jenis_kelamin'];

// Query untuk update data karyawan
$sql_update_karyawan = "UPDATE karyawan SET nama=?, usia=?, jenis_kelamin=? WHERE id=?";
$stmt_update_karyawan = $conn->prepare($sql_update_karyawan);
$stmt_update_karyawan->bind_param("sisi", $nama, $usia, $jenis_kelamin, $id);

// Eksekusi statement update data karyawan
if ($stmt_update_karyawan->execute()) {
    // Lakukan update karyawan_kriteria setelah update karyawan berhasil
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'kriteria_') === 0) {
            $criteria_id = substr($key, strlen('kriteria_'));
            $criteria_value = $value;

            // Query untuk update atau insert jika belum ada data pada karyawan_kriteria
            $sql_update_karyawan_kriteria = "INSERT INTO karyawan_kriteria (karyawan_id, criteria_id, value) 
                                             VALUES (?, ?, ?) 
                                             ON DUPLICATE KEY UPDATE value = ?";
            $stmt_update_karyawan_kriteria = $conn->prepare($sql_update_karyawan_kriteria);
            $stmt_update_karyawan_kriteria->bind_param("iisi", $id, $criteria_id, $criteria_value, $criteria_value);
            $stmt_update_karyawan_kriteria->execute();

            // Handle kesalahan jika diperlukan
            if ($stmt_update_karyawan_kriteria->errno) {
                echo "Error: " . $stmt_update_karyawan_kriteria->error;
            }

            $stmt_update_karyawan_kriteria->close();
        }
    }

    // Redirect ke halaman pegawai.php setelah berhasil disimpan
    header("Location: pegawai.php");
    exit();
} else {
    // Jika terjadi error, tampilkan pesan error atau redirect ke halaman error
    echo "Error: " . $conn->error;
}

$stmt_update_karyawan->close();
$conn->close();
?>

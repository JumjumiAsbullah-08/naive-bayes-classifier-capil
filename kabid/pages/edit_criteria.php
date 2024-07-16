<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Buat koneksi ke database
$conn = connectDatabase();

// Proses edit kriteria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criteria_id'])) {
    $criteria_id = $_POST['criteria_id'];
    $name = $_POST['name'];
    $kode_criteria = $_POST['kode_criteria'];

    // Query untuk update data kriteria
    $sql = "UPDATE criteria SET name='$name', kode_criteria='$kode_criteria' WHERE id=$criteria_id";

    if ($conn->query($sql) === TRUE) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Kriteria berhasil diperbarui',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        window.location.href = 'kriteria.php';
                    });
                });
              </script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error updating record',
                        text: '" . $conn->error . "',
                    }).then(function() {
                        window.location.href = 'kriteria.php';
                    });
                });
              </script>";
    }
}

$conn->close();
?>

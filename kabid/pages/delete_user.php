<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}
// Sambungkan ke database
$conn = connectDatabase();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $id = $_GET['id'];

    // Buat query SQL untuk menghapus data
    $sql = "DELETE FROM users WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Data user berhasil dihapus!',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(function() {
                        window.location.href = 'users.php';
                    });
                });
              </script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Data user gagal dihapus: " . $conn->error . "',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(function() {
                        window.location.href = 'users.php';
                    });
                });
              </script>";
    }

    $conn->close();
}
?>

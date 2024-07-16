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

// Proses hapus kriteria
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM criteria WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Kriteria berhasil dihapus');
                window.location.href = 'kriteria.php';
              </script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

$conn->close();
?>

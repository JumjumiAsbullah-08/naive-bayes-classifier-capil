<?php
// Pastikan halaman ini hanya bisa diakses jika sudah login dan memiliki peran admin
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

require_once "../../koneksi.php";

$conn = connectDatabase();
// Pastikan id criteria yang dihapus ada dan valid
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: sub_kriteria.php");
    exit();
}

$id = $_GET['id'];

// Hapus data kriteria berdasarkan id
$sql = "DELETE FROM sub_criteria WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redirect ke halaman kriteria.php setelah berhasil dihapus
    header("Location: sub_kriteria.php");
    exit();
} else {
    echo "Gagal menghapus data kriteria";
}

$stmt->close();
$conn->close();
?>

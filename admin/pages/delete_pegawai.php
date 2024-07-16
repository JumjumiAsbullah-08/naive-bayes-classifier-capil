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

// Tangkap parameter id yang akan dihapus
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Hapus terlebih dahulu data yang terkait di tabel posterior
    $sql_delete_posterior = "DELETE FROM posterior WHERE karyawan_id = ?";
    $stmt_delete_posterior = $conn->prepare($sql_delete_posterior);
    $stmt_delete_posterior->bind_param("i", $id);

    // Hapus terlebih dahulu data yang terkait di tabel hasil_klasifikasi
    $sql_delete_hasil = "DELETE FROM hasil_klasifikasi WHERE karyawan_id = ?";
    $stmt_delete_hasil = $conn->prepare($sql_delete_hasil);
    $stmt_delete_hasil->bind_param("i", $id);

    // Mulai transaksi
    $conn->begin_transaction();

    // Hapus dari tabel posterior
    if ($stmt_delete_posterior->execute()) {
        // Hapus dari tabel hasil_klasifikasi
        if ($stmt_delete_hasil->execute()) {
            // Hapus data karyawan
            $sql_delete_karyawan = "DELETE FROM karyawan WHERE id = ?";
            $stmt_delete_karyawan = $conn->prepare($sql_delete_karyawan);
            $stmt_delete_karyawan->bind_param("i", $id);

            if ($stmt_delete_karyawan->execute()) {
                // Commit transaksi jika semua berhasil
                $conn->commit();
                // Redirect kembali ke halaman pegawai.php
                header("Location: pegawai.php");
            } else {
                // Rollback transaksi jika terjadi kesalahan
                $conn->rollback();
                echo "Error deleting karyawan: " . $conn->error;
            }

            // Tutup statement hapus karyawan
            $stmt_delete_karyawan->close();
        } else {
            // Rollback transaksi jika terjadi kesalahan
            $conn->rollback();
            echo "Error deleting hasil klasifikasi: " . $conn->error;
        }

        // Tutup statement hapus hasil_klasifikasi
        $stmt_delete_hasil->close();
    } else {
        // Rollback transaksi jika terjadi kesalahan
        $conn->rollback();
        echo "Error deleting posterior: " . $conn->error;
    }

    // Tutup statement hapus posterior
    $stmt_delete_posterior->close();
}

// Tutup koneksi
$conn->close();
?>

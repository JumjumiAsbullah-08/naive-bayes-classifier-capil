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

// Ambil semua kriteria dan sub-kriteria
$criteria = getAllCriteria($conn);
$sub_criteria = [];
foreach ($criteria as $c) {
    $sub_criteria[$c['id']] = getSubCriteriaByCriteriaId($conn, $c['id']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $usia = $_POST['usia'];
    $jenis_kelamin = $_POST['jenis_kelamin'];

    // Inisialisasi array untuk nilai kriteria
    $kriteria_values = [];
    foreach ($criteria as $c) {
        $kriteria_id = $c['id'];
        if (isset($_POST['kriteria_' . $kriteria_id])) {
            $kriteria_values[$kriteria_id] = $_POST['kriteria_' . $kriteria_id];
        } else {
            die("Error: Nilai untuk kriteria ID {$kriteria_id} tidak boleh kosong.");
        }
    }

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // Update data pegawai
        $sql = "UPDATE karyawan SET nama = ?, usia = ?, jenis_kelamin = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("sisi", $nama, $usia, $jenis_kelamin, $id);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Hapus data kriteria pegawai sebelumnya
        $sql = "DELETE FROM karyawan_kriteria WHERE karyawan_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Simpan data kriteria pegawai baru
        foreach ($kriteria_values as $kriteria_id => $sub_criteria_id) {
            // Cari nilai value dari sub_kriteria
            $sql = "SELECT value FROM sub_criteria WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $sub_criteria_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $sub_criteria_value = $result->fetch_assoc()['value'];

            // Lakukan insert ke dalam tabel karyawan_kriteria
            $sql = "INSERT INTO karyawan_kriteria (karyawan_id, criteria_id, sub_criteria_id, value) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("iiid", $id, $kriteria_id, $sub_criteria_id, $sub_criteria_value);
            if (!$stmt->execute()) {
                die("Error inserting into karyawan_kriteria: " . $stmt->error);
            }
        }

        // Commit transaksi
        $conn->commit();

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Data pegawai berhasil diperbarui',
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
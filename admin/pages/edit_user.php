<?php
session_start();
require_once "../../koneksi.php";

// Periksa apakah user sudah login dan memiliki peran sebagai admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit();
}

$conn = connectDatabase();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET username='$username', password='$password', role='$role' WHERE id='$id'";
    } else {
        $sql = "UPDATE users SET username='$username', role='$role' WHERE id='$id'";
    }

    if ($conn->query($sql) === TRUE) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Data user berhasil diupdate!',
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
                        text: 'Data user gagal diupdate: " . $conn->error . "',
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

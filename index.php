<?php
include "koneksi.php";
session_start(); // Mulai session untuk menyimpan status login

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Panggil fungsi koneksi database
    $conn = connectDatabase();

    // Ambil data dari form login
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Lindungi dari SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Query untuk mencari user berdasarkan username
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        // Jika data ditemukan, ambil informasi user
        $row = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            // Simpan informasi user ke dalam session
            $_SESSION['id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Tampilkan pesan sukses dengan SweetAlert2 dan redirect setelah beberapa detik
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Berhasil',
                            text: 'Anda akan dialihkan dalam beberapa saat...',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.href = '" . ($row['role'] == 'admin' ? 'admin/pages/dashboard.php' : ($row['role'] == 'kabid' ? 'kabid/pages/dashboard.php' : 'index.php')) . "';
                        });
                    });
                </script>";
        } else {
            // Jika password salah, tampilkan pesan error dengan SweetAlert2
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Gagal',
                            text: 'Username atau password salah!',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.href = 'index.php';
                        });
                    });
                </script>";
        }
    } else {
        // Jika username tidak ditemukan, tampilkan pesan error dengan SweetAlert2
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Gagal',
                        text: 'Username atau password salah!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = 'index.php';
                    });
                });
            </script>";
    }
    // Tutup koneksi database
    $conn->close();
    exit();
}
?>

<!doctype html>
<html lang="en">
  <head>
  	<title>Login | Dukcapil</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">

	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	
	<link rel="stylesheet" href="css/style.css">
	<link rel="shortcut icon" href="images/logo.png" type="image/x-icon">

	</head>
	<body class="img js-fullheight" style="background-image: url(images/bg.jpg);">
	<section class="ftco-section">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-md-6 text-center mb-5">
				</div>
			</div>
			<div class="row justify-content-center">
				<div class="col-md-6 col-lg-4">
					<div class="login-wrap p-0">
		      	<h3 class="mb-4 text-center">Silahkan Login!</h3>
		      	<form action="#" class="signin-form" method="post">
		      		<div class="form-group">
		      			<input type="text" class="form-control" placeholder="Username" name="username" required>
		      		</div>
	            <div class="form-group">
	              <input id="password-field" type="password" class="form-control" placeholder="Password" name="password" required>
	              <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
	            </div>
	            <div class="form-group">
	            	<button type="submit" class="form-control btn btn-primary submit px-3">Sign In</button>
	            </div>
	            <div class="form-group d-md-flex">
	            	<div class="w-50">
		            	<label class="checkbox-wrap checkbox-primary">Remember Me
									  <input type="checkbox" checked>
									  <span class="checkmark"></span>
									</label>
								</div>
	            </div>
	          </form>
		      </div>
				</div>
			</div>
		</div>
	</section>

	<script src="js/jquery.min.js"></script>
  <script src="js/popper.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/main.js"></script>

	</body>
</html>


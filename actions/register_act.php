<?php
// 1. Panggil file koneksi. Karena file ini ada di dalam folder actions, kita gunakan '../' untuk naik satu folder.
require '../koneksi.php';

// 2. Cek apakah tombol register sudah ditekan
if (isset($_POST['register'])) {
    
    // 3. Ambil data dari form dan gunakan perlindungan mysqli_real_escape_string (Mencegah SQL Injection)
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // 4. Enkripsi password menggunakan BCRYPT (Mencegah password terlihat di database)
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // 5. Query untuk menyimpan data ke database
    $query = "INSERT INTO users (nama, email, password, role) 
              VALUES ('$nama', '$email', '$password_hashed', '$role')";

    // 6. Eksekusi query dan arahkan halaman
    if (mysqli_query($koneksi, $query)) {
        echo "<script>
                alert('Registrasi berhasil! Silakan login.');
                window.location.href = '../login.php';
              </script>";
    } else {
        echo "<script>
                alert('Registrasi gagal: " . mysqli_error($koneksi) . "');
                window.history.back();
              </script>";
    }
}
?>
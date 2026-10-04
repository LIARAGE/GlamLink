<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - GlamLink</title>
</head>
<body>
    <h2>Daftar Akun GlamLink</h2>
    <!-- Action mengarah ke folder actions/ -->
    <form action="actions/register_act.php" method="POST">
        <label>Nama Lengkap:</label><br>
        <input type="text" name="nama" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Mendaftar sebagai:</label><br>
        <select name="role" required>
            <option value="pelanggan">Pelanggan</option>
            <option value="mua">Makeup Artist (MUA)</option>
        </select><br><br>

        <button type="submit" name="register">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
</body>
</html>
<?php
session_start();

// Jika admin sudah login, langsung arahkan ke dashboard admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - GlamLink</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-color, #FAF6F0);
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }

        .login-card {
            background: #FFFFFF;
            border: 1px solid #EFECE6;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .login-card .logo {
            font-size: 28px;
            font-weight: 700;
            color: #4A3B32;
            margin-bottom: 5px;
        }

        .login-card p {
            color: #7A6F68;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4A3B32;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #D7CCC8;
            border-radius: 10px;
            font-size: 14px;
            box-sizing: border-box;
            background-color: #FAFAFA;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #4A3B32;
            background-color: #FFFFFF;
        }

        .alert-danger {
            background-color: #FFEBEE;
            color: #C62828;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
            border: 1px solid #FFCDD2;
        }

        .btn-submit {
            width: 100%;
            background-color: #4A3B32;
            color: #FFFFFF;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #382C25;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo">GlamLink</div>
        <p>Masuk ke Panel Administrator</p>

        <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'gagal') : ?>
            <div class="alert-danger">
                Email atau Password salah, atau akun Anda bukan Admin.
            </div>
        <?php elseif (isset($_GET['pesan']) && $_GET['pesan'] == 'belum_login') : ?>
            <div class="alert-danger">
                Silakan login terlebih dahulu untuk mengakses halaman admin.
            </div>
        <?php endif; ?>

        <form action="login_act.php" method="POST">
            <div class="form-group">
                <label for="email">Email Administrator</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="admin@glamlink.com" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Masuk ke Dashboard</button>
        </form>
    </div>

</body>
</html>
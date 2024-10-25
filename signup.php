<!-- signup.php -->
<?php
session_start();

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "uts_lec";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $name = $conn->real_escape_string($name);
    $email = $conn->real_escape_string($email);
    $pass = $conn->real_escape_string($pass);

    // Periksa apakah email sudah terdaftar
    $checkEmail = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        $error = "Email sudah terdaftar. Gunakan email lain.";
    } else {
        // Enkripsi password sebelum menyimpannya ke database
        $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashedPassword')";

        if ($conn->query($sql) === TRUE) {
            // Redirect ke halaman signin.php dengan pesan sukses
            header("Location: signin.php?success=Akun berhasil dibuat. Silakan masuk.");
            exit();
        } else {
            $error = "Gagal mendaftarkan akun. Silakan coba lagi.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="signup-container">
        <h2>Sign Up</h2>
        <?php if (!empty($error)) { echo "<p class='error-message'>$error</p>"; } ?>
        <form action="signup.php" method="post">
            <div class="form-group">
                <label for="name">Nama:</label>
                <input type="name" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="signup-button">Sign Up</button>
        </form>
    </div>
</body>
</html>

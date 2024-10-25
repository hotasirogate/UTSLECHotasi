<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "uts_lec";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$error = "";
$success = "";

// Ambil informasi profil pengguna
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE id='$user_id'";
$user_result = $conn->query($user_sql);
$user_info = $user_result->fetch_assoc();

// Penanganan form untuk menambah event jika user adalah admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION['role'] == 'admin') {
    if (isset($_POST['add_event'])) {
        $event_name = $_POST['event_name'];
        $event_date = $_POST['event_date'];
        $event_time = $_POST['event_time'];
        $location = $_POST['location'];
        $max_participants = $_POST['max_participants'];
        $event_description = $_POST['event_description'];

        // Escape input untuk mencegah SQL injection
        $event_name = $conn->real_escape_string($event_name);
        $event_date = $conn->real_escape_string($event_date);
        $event_time = $conn->real_escape_string($event_time);
        $location = $conn->real_escape_string($location);
        $max_participants = $conn->real_escape_string($max_participants);
        $event_description = $conn->real_escape_string($event_description);

        // Query untuk menambah event ke database
        $sql = "INSERT INTO events (event_name, event_date, event_time, location, max_participants, event_description) 
                VALUES ('$event_name', '$event_date', '$event_time', '$location', '$max_participants', '$event_description')";

        if ($conn->query($sql) === TRUE) {
            $success = "Event berhasil ditambahkan!";
        } else {
            $error = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['edit_event'])) {
        // Mengedit event yang ada
        $event_id = $_POST['event_id'];
        $event_name = $_POST['event_name'];
        $event_date = $_POST['event_date'];
        $event_time = $_POST['event_time'];
        $location = $_POST['location'];
        $max_participants = $_POST['max_participants'];
        $event_description = $_POST['event_description'];

        // Escape input untuk mencegah SQL injection
        $event_id = $conn->real_escape_string($event_id);
        $event_name = $conn->real_escape_string($event_name);
        $event_date = $conn->real_escape_string($event_date);
        $event_time = $conn->real_escape_string($event_time);
        $location = $conn->real_escape_string($location);
        $max_participants = $conn->real_escape_string($max_participants);
        $event_description = $conn->real_escape_string($event_description);

        // Update event di database
        $sql = "UPDATE events SET event_name='$event_name', event_date='$event_date', event_time='$event_time', location='$location', max_participants='$max_participants', event_description='$event_description' WHERE id='$event_id'";

        if ($conn->query($sql) === TRUE) {
            $success = "Event berhasil diperbarui!";
        } else {
            $error = "Terjadi kesalahan: " . $conn->error;
        }
    }
} elseif (isset($_GET['delete'])) {
    // Menghapus event
    $event_id = $_GET['delete'];
    $sql = "DELETE FROM events WHERE id='$event_id'";
    if ($conn->query($sql) === TRUE) {
        $success = "Event berhasil dihapus!";
    } else {
        $error = "Error: " . $conn->error;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_profile'])) {
    // Mengupdate informasi profil pengguna
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Escape input untuk mencegah SQL injection
    $name = $conn->real_escape_string($name);
    $email = $conn->real_escape_string($email);

    // Jika password diisi, hash password baru
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Update query untuk password
        $sql = "UPDATE users SET name='$name', email='$email', password='$hashed_password' WHERE id='$user_id'";
    } else {
        // Update tanpa mengubah password
        $sql = "UPDATE users SET name='$name', email='$email' WHERE id='$user_id'";
    }

    if ($conn->query($sql) === TRUE) {
        $success = "Profil berhasil diperbarui!";
        // Refresh data pengguna setelah update
        $user_info['name'] = $name;
        $user_info['email'] = $email;
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Handle event registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_event'])) {
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];

    // Escape input to prevent SQL injection
    $event_id = $conn->real_escape_string($event_id);
    $user_id = $conn->real_escape_string($user_id);

    // Query to insert registration into the database
    $sql = "INSERT INTO registrations (event_id, user_id) VALUES ('$event_id', '$user_id')";

    if ($conn->query($sql) === TRUE) {
        $success = "Anda berhasil mendaftar untuk event ini!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Handle registration deletion
if (isset($_GET['delete_registration'])) {
    $registration_id = $_GET['delete_registration'];
    $sql = "DELETE FROM registrations WHERE id='$registration_id'";
    if ($conn->query($sql) === TRUE) {
        $success = "Registrasi berhasil dihapus!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Ambil daftar event dari database
$sql = "SELECT * FROM events";
$result = $conn->query($sql);

// Get registered users for each event (admin functionality)
$registrations_sql = "SELECT r.id, r.registration_date, e.event_name, u.name FROM registrations r
                      JOIN events e ON r.event_id = e.id
                      JOIN users u ON r.user_id = u.id";
$registrations_result = $conn->query($registrations_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Dashboard</h2>
        <button class="account-info-button" onclick="toggleAccountInfo()">Info Akun</button>
        
        <?php if (!empty($success)): ?>
            <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <!-- Form untuk edit profil pengguna -->
        <div id="account-info" style="display:none;">
            <h3>Edit Profil</h3>
            <form action="dashboard.php" method="post">
                <div class="form-group">
                    <label for="name">Nama:</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user_info['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password (kosongkan jika tidak ingin mengubah):</label>
                    <input type="password" name="password" id="password">
                </div>
                <button type="submit" name="edit_profile">Simpan Perubahan</button>
            </form>
        </div>

        <?php if ($_SESSION['role'] == 'admin'): ?>
            <h3>Tambah Event Baru</h3>
            <form action="dashboard.php" method="post">
                <div class="form-group">
                    <label for="event_name">Nama Event:</label>
                    <input type="text" name="event_name" id="event_name" required>
                </div>
                <div class="form-group">
                    <label for="event_date">Tanggal Event:</label>
                    <input type="text" name="event_date" id="event_date" required>
                </div>
                <div class="form-group">
                    <label for="event_time">Waktu Event:</label>
                    <input type="text" name="event_time" id="event_time" required>
                </div>
                <div class="form-group">
                    <label for="location">Lokasi:</label>
                    <input type="text" name="location" id="location" required>
                </div>
                <div class="form-group">
                    <label for="max_participants">Maksimum Peserta:</label>
                    <input type="text" name="max_participants" id="max_participants" required>
                </div>
                <div class="form-group">
                    <label for="event_description">Deskripsi Event:</label>
                    <textarea name="event_description" id="event_description" rows="4" required></textarea>
                </div>
                <button type="submit" name="add_event">Tambah Event</button>
            </form>
        <?php endif; ?>

        <h3>Daftar Event</h3>
        <form action="dashboard.php" method="get">
            <input type="text" name="search" placeholder="Cari event..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Cari</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Nama Event</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Lokasi</th>
                    <th>Maks. Peserta</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['event_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['max_participants']); ?></td>
                            <td><?php echo htmlspecialchars($row['event_description']); ?></td>
                            <td>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="?edit=<?php echo $row['id']; ?>">Edit</a> |
                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus event ini?');">Delete</a>
                                <?php else: ?>
                                    <form action="dashboard.php" method="post" style="display:inline;">
                                        <input type="hidden" name="event_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="register_event">Daftar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Tidak ada event yang tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        // Jika admin memilih untuk mengedit event
        if (isset($_GET['edit'])) {
            $event_id = $_GET['edit'];
            
            // Ambil data event dari database berdasarkan ID
            $edit_sql = "SELECT * FROM events WHERE id='$event_id'";
            $edit_result = $conn->query($edit_sql);
            $event_data = $edit_result->fetch_assoc();
            
            if ($event_data): // Jika event ditemukan
        ?>
            <h3>Edit Event</h3>
            <form action="dashboard.php" method="post">
                <input type="hidden" name="event_id" value="<?php echo $event_data['id']; ?>">
                <div class="form-group">
                    <label for="event_name">Nama Event:</label>
                    <input type="text" name="event_name" id="event_name" value="<?php echo htmlspecialchars($event_data['event_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="event_date">Tanggal Event:</label>
                    <input type="text" name="event_date" id="event_date" value="<?php echo htmlspecialchars($event_data['event_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="event_time">Waktu Event:</label>
                    <input type="text" name="event_time" id="event_time" value="<?php echo htmlspecialchars($event_data['event_time']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="location">Lokasi:</label>
                    <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($event_data['location']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="max_participants">Maksimum Peserta:</label>
                    <input type="text" name="max_participants" id="max_participants" value="<?php echo htmlspecialchars($event_data['max_participants']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="event_description">Deskripsi Event:</label>
                    <textarea name="event_description" id="event_description" rows="4" required><?php echo htmlspecialchars($event_data['event_description']); ?></textarea>
                </div>
                <button type="submit" name="edit_event">Simpan Perubahan</button>
            </form>
        <?php
            endif;
        }
        ?>

        <h3>Registrasi Event</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama Event</th>
                    <th>Peserta</th>
                    <th>Tanggal Registrasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($registrations_result->num_rows > 0): ?>
                    <?php while ($reg = $registrations_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['registration_date']); ?></td>
                            <td>
                                <a href="?delete_registration=<?php echo $reg['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus registrasi ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Tidak ada registrasi yang ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleAccountInfo() {
            var accountInfo = document.getElementById('account-info');
            accountInfo.style.display = accountInfo.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>

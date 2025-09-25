<?php
require_once 'db_connect.php';

// Jika sudah login, redirect ke dashboard yang sesuai
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: index.php");
        exit();
    } else {
        header("Location: user_dashboard.php");
        exit();
    }
}

$pesan_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Jangan trim password, bisa memotong karakter
    
    // Debug: tampilkan panjang password (hapus setelah testing)
    // echo "Password length: " . strlen($password) . "<br>";
    
    $sql = "SELECT * FROM users WHERE email = ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID untuk keamanan
        session_regenerate_id(true);
        
        // Simpan data user ke session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;

        // Debug session (hapus setelah testing)
        // echo "Session set: " . print_r($_SESSION, true);
        
        // Arahkan berdasarkan role dengan absolute URL
        if ($user['role'] == 'admin') {
            // Gunakan JavaScript redirect sebagai backup
            echo "<script>window.location.href = 'index.php';</script>";
            header("Location: index.php");
            exit();
        } else {
            echo "<script>window.location.href = 'user_dashboard.php';</script>";
            header("Location: user_dashboard.php");
            exit();
        }
    } else {
        $pesan_error = "Email atau password salah, atau akun belum disetujui admin.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login | Sneat</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/css/pages/page-auth.css" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center">
                            <a href="#" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <svg width="25" viewBox="0 0 25 42" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                        <defs>
                                            <path d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z" id="path-1"></path>
                                        </defs>
                                        <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                                                <g id="Icon" transform="translate(27.000000, 15.000000)">
                                                    <g id="Mask" transform="translate(0.000000, 8.000000)">
                                                        <mask id="mask-2" fill="white">
                                                            <use xlink:href="#path-1"></use>
                                                        </mask>
                                                        <use fill="#696cff" xlink:href="#path-1"></use>
                                                    </g>
                                                </g>
                                            </g>
                                        </g>
                                    </svg>
                                </span>
                                <span class="app-brand-text demo text-body fw-bolder">Sneat</span>
                            </a>
                        </div>
                        <h4 class="mb-2">Welcome to Sneat! 👋</h4>
                        <p class="mb-4">Please sign-in to your account and start the adventure</p>

                        <?php if (!empty($pesan_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($pesan_error); ?>
                            </div>
                        <?php endif; ?>

                        <form id="formAuthentication" class="mb-3" action="login.php" method="POST" autocomplete="off">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       autofocus required autocomplete="email" />
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Password</label>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password" 
                                           placeholder="············" aria-describedby="password" required 
                                           autocomplete="current-password" maxlength="255" />
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="bx bx-hide" id="toggleIcon"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                            </div>
                        </form>

                        <p class="text-center">
                            <span>New on our platform?</span>
                            <a href="register.php">
                                <span>Create an account</span>
                            </a>
                        </p>

                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Demo Accounts:</strong><br>
                                Admin: admin@example.com / admin123<br>
                                (User accounts need admin approval after registration)
                            </small>
                        </div>
                        
                        <!-- Debug Tools (hapus di production) -->
                        <div class="mt-3 text-center">
                            <small>
                                <a href="debug_login.php" class="text-muted">🔍 Debug</a> | 
                                <a href="reset_admin.php" class="text-muted">🔧 Reset Password</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('bx-hide');
                toggleIcon.classList.add('bx-show');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('bx-show');
                toggleIcon.classList.add('bx-hide');
            }
        });
        
        // Prevent form auto-completion issues
        document.getElementById('formAuthentication').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            console.log('Password length:', password.length); // Debug
            
            // Pastikan password tidak kosong
            if (password.length === 0) {
                e.preventDefault();
                alert('Please enter your password');
                return false;
            }
        });
        
        // Auto-fill untuk testing (hapus di production)
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('autofill=1')) {
                document.getElementById('email').value = 'admin@example.com';
                document.getElementById('password').value = 'admin123';
            }
        });
    </script>
</body>
</html>

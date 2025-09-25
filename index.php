<?php
require_once 'db_connect.php';
require_once 'check_session.php';

// Proteksi halaman: hanya admin yang bisa akses
checkLogin('admin');

// Handle user deletion
if (isset($_GET['delete_user_id'])) {
    $user_id = $_GET['delete_user_id'];
    // Prevent deletion of admin users
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_room_id'])) {
    $id_to_delete = $_GET['delete_room_id'];
    $stmt = $conn->prepare("DELETE FROM ruang WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_facility_id'])) {
    $id_to_delete = $_GET['delete_facility_id'];
    $stmt = $conn->prepare("DELETE FROM facilities WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    header("Location: index.php");
    exit();
}

if (isset($_GET['approve_booking_id'])) {
    $booking_id = $_GET['approve_booking_id'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    
    // Record approval in penyetujuan_booking table
    $admin_id = $_SESSION['user_id'];
    $stmt2 = $conn->prepare("INSERT INTO penyetujuan_booking (booking_id, admin_id, action) VALUES (?, ?, 'approved')");
    $stmt2->bind_param("ii", $booking_id, $admin_id);
    $stmt2->execute();
    
    header("Location: index.php");
    exit();
}

if (isset($_GET['reject_booking_id'])) {
    $booking_id = $_GET['reject_booking_id'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    
    // Record rejection in penyetujuan_booking table
    $admin_id = $_SESSION['user_id'];
    $stmt2 = $conn->prepare("INSERT INTO penyetujuan_booking (booking_id, admin_id, action) VALUES (?, ?, 'rejected')");
    $stmt2->bind_param("ii", $booking_id, $admin_id);
    $stmt2->execute();
    
    header("Location: index.php");
    exit();
}

// Proses SETUJUI pengguna
if (isset($_GET['approve_user_id'])) {
    $userIdToApprove = $_GET['approve_user_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $userIdToApprove);
    $stmt->execute();
    header("Location: index.php");
    exit();
}

// Proses TOLAK pengguna
if (isset($_GET['reject_user_id'])) {
    $userIdToReject = $_GET['reject_user_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $userIdToReject);
    $stmt->execute();
    header("Location: index.php");
    exit();
}

$rooms_result = $conn->query("SELECT * FROM ruang ORDER BY id DESC");
$facilities_result = $conn->query("SELECT * FROM facilities ORDER BY id DESC");
$pending_bookings_result = $conn->query("
    SELECT b.*, u.username, u.email,
           COALESCE(r.name, f.name) as item_name,
           CASE WHEN b.ruangan_id IS NOT NULL THEN 'Room' ELSE 'Facility' END as item_type
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    LEFT JOIN ruang r ON b.ruangan_id = r.id 
    LEFT JOIN facilities f ON b.facility_id = f.id 
    WHERE b.status = 'pending' 
    ORDER BY b.created_at DESC
");
$pending_users_result = $conn->query("SELECT id, username, email, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$all_users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

$total_rooms = $conn->query("SELECT COUNT(*) as count FROM ruang")->fetch_assoc()['count'];
$total_facilities = $conn->query("SELECT COUNT(*) as count FROM facilities")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$pending_approvals = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch_assoc()['count'];
$pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$available_rooms = $conn->query("SELECT COUNT(*) as count FROM ruang WHERE status = 'available'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Room & Facility Booking Admin | Sneat</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="index.php" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <svg width="25" viewBox="0 0 25 42" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <path d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z" id="path-1"></path>
                                    <path d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z" id="path-3"></path>
                                    <path d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z" id="path-4"></path>
                                    <path d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z" id="path-5"></path>
                                </defs>
                                <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                                        <g id="Icon" transform="translate(27.000000, 15.000000)">
                                            <g id="Mask" transform="translate(0.000000, 8.000000)">
                                                <mask id="mask-2" fill="white">
                                                    <use xlink:href="#path-1"></use>
                                                </mask>
                                                <use fill="#696cff" xlink:href="#path-1"></use>
                                                <g id="Path-3" mask="url(#mask-2)">
                                                    <use fill="#696cff" xlink:href="#path-3"></use>
                                                    <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                                                </g>
                                                <g id="Path-4" mask="url(#mask-2)">
                                                    <use fill="#696cff" xlink:href="#path-4"></use>
                                                    <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                                                </g>
                                            </g>
                                            <g id="Triangle" transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) ">
                                                <use fill="#696cff" xlink:href="#path-5"></use>
                                                <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bolder ms-2">Admin panel</span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <li class="menu-item active">
                        <a href="index.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Analytics">Dashboard</div>
                        </a>
                    </li>
                    <!-- Replace Projects menu with Rooms and Facilities -->
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-building"></i>
                            <div data-i18n="Rooms">Rooms</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="add_room.php" class="menu-link">
                                    <div data-i18n="Add Room">Add Room</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="#rooms-section" class="menu-link">
                                    <div data-i18n="Manage Rooms">Manage Rooms</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-cog"></i>
                            <div data-i18n="Facilities">Facilities</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="add_facility.php" class="menu-link">
                                    <div data-i18n="Add Facility">Add Facility</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="#facilities-section" class="menu-link">
                                    <div data-i18n="Manage Facilities">Manage Facilities</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-calendar"></i>
                            <div data-i18n="Bookings">Bookings</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="#pending-bookings" class="menu-link">
                                    <div data-i18n="Pending Bookings">Pending Bookings</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <!-- Updated link to dedicated booking management page -->
                                <a href="manage_bookings.php" class="menu-link">
                                    <div data-i18n="All Bookings">All Bookings</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div data-i18n="Users">Users</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="#pending-users" class="menu-link">
                                    <div data-i18n="Pending Approvals">Pending Approvals</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="#all-users" class="menu-link">
                                    <div data-i18n="All Users">All Users</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </aside>

            <!-- Layout page -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="bx bx-search fs-4 lh-0"></i>
                                <input type="text" class="form-control border-0 shadow-none" placeholder="Search..." aria-label="Search..." />
                            </div>
                        </div>

                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                                    <small class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><div class="dropdown-divider"></div></li>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    <li><div class="dropdown-divider"></div></li>
                                    <li>
                                        <a class="dropdown-item" href="logout.php">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span class="align-middle">Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        
                        <!-- Welcome Section -->
                        <div class="row">
                            <div class="col-lg-8 mb-4 order-0">
                                <div class="card">
                                    <div class="d-flex align-items-end row">
                                        <div class="col-sm-7">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?>!</h5>
                                                <p class="mb-4">
                                                    You have <span class="fw-bold"><?php echo $pending_bookings; ?></span> pending booking approvals and 
                                                    <span class="fw-bold"><?php echo $pending_approvals; ?></span> pending user approvals. 
                                                    Manage rooms, facilities, and bookings from your admin panel.
                                                </p>
                                                <a href="#pending-bookings" class="btn btn-sm btn-outline-primary me-2">View Pending Bookings</a>
                                                <a href="#pending-users" class="btn btn-sm btn-outline-secondary">View Pending Users</a>
                                            </div>
                                        </div>
                                        <div class="col-sm-5 text-center text-sm-left">
                                            <div class="card-body pb-0 px-0 px-md-4">
                                                <img src="../assets/img/illustrations/man-with-laptop-light.png" height="140" alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png" data-app-light-img="illustrations/man-with-laptop-light.png" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Update Statistics Cards for booking system -->
                            <div class="col-lg-4 col-md-4 order-1">
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="../assets/img/icons/unicons/chart-success.png" alt="chart success" class="rounded" />
                                                    </div>
                                                </div>
                                                <span class="fw-semibold d-block mb-1">Total Rooms</span>
                                                <h3 class="card-title mb-2"><?php echo $total_rooms; ?></h3>
                                                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> Available: <?php echo $available_rooms; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="../assets/img/icons/unicons/wallet-info.png" alt="Credit Card" class="rounded" />
                                                    </div>
                                                </div>
                                                <span>Total Facilities</span>
                                                <h3 class="card-title text-nowrap mb-1"><?php echo $total_facilities; ?></h3>
                                                <small class="text-info fw-semibold"><i class="bx bx-cog"></i> Equipment</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="../assets/img/icons/unicons/cc-primary.png" alt="Credit Card" class="rounded" />
                                                    </div>
                                                </div>
                                                <span>Pending Bookings</span>
                                                <h3 class="card-title text-nowrap mb-1"><?php echo $pending_bookings; ?></h3>
                                                <small class="text-warning fw-semibold"><i class="bx bx-time"></i> Need Review</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="../assets/img/icons/unicons/paypal.png" alt="Credit Card" class="rounded" />
                                                    </div>
                                                </div>
                                                <span>Total Users</span>
                                                <h3 class="card-title text-nowrap mb-1"><?php echo $total_users; ?></h3>
                                                <small class="text-warning fw-semibold"><i class="bx bx-time"></i> Pending: <?php echo $pending_approvals; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Success Message -->
                        <div class="alert alert-success alert-dismissible" role="alert">
                            🎉 <strong>Login Berhasil!</strong> Selamat datang di Room & Facility Booking Admin Dashboard!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>

                        <!-- Replace Project Management with Pending Bookings -->
                        <div class="card mt-4" id="pending-bookings">
                            <h5 class="card-header">
                                Pending Booking Approvals
                                <?php if ($pending_bookings > 0): ?>
                                    <span class="badge bg-warning ms-2"><?php echo $pending_bookings; ?> Pending</span>
                                <?php endif; ?>
                            </h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>User</th>
                                            <th>Item</th>
                                            <th>Date & Time</th>
                                            <th>Purpose</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if ($pending_bookings_result->num_rows > 0): ?>
                                            <?php while($booking = $pending_bookings_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-3">
                                                            <span class="avatar-initial rounded-circle bg-secondary">
                                                                <?php echo strtoupper(substr($booking['username'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($booking['username']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['item_name']); ?></strong><br>
                                                        <span class="badge bg-label-<?php echo $booking['item_type'] == 'Room' ? 'primary' : 'info'; ?>">
                                                            <?php echo $booking['item_type']; ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></strong><br>
                                                        <small class="text-muted">
                                                            <?php echo date('H:i', strtotime($booking['start_time'])); ?> - 
                                                            <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($booking['purpose'], 0, 50)) . (strlen($booking['purpose']) > 50 ? '...' : ''); ?></small>
                                                </td>
                                                <td>
                                                    <a href="index.php?approve_booking_id=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-sm btn-success me-2"
                                                       onclick="return confirm('Approve this booking?');">
                                                        <i class="bx bx-check"></i> Approve
                                                    </a>
                                                    <a href="index.php?reject_booking_id=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Reject this booking?');">
                                                        <i class="bx bx-x"></i> Reject
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="py-4">
                                                        <i class="bx bx-calendar-check display-4 text-muted"></i>
                                                        <p class="text-muted mt-2">No pending booking approvals.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Added link to full booking management page -->
                            <div class="card-footer">
                                <a href="manage_bookings.php" class="btn btn-primary">
                                    <i class="bx bx-calendar"></i> View All Bookings
                                </a>
                            </div>
                        </div>

                        <!-- Added All Bookings section -->
                        <div class="card mt-4" id="all-bookings">
                            <h5 class="card-header">Recent Bookings Overview</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Item</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php 
                                        $recent_bookings = $conn->query("
                                            SELECT b.*, u.username,
                                                   COALESCE(r.name, f.name) as item_name,
                                                   CASE WHEN b.ruangan_id IS NOT NULL THEN 'Room' ELSE 'Facility' END as item_type
                                            FROM bookings b 
                                            LEFT JOIN users u ON b.user_id = u.id 
                                            LEFT JOIN ruang r ON b.ruangan_id = r.id 
                                            LEFT JOIN facilities f ON b.facility_id = f.id 
                                            ORDER BY b.created_at DESC 
                                            LIMIT 5
                                        ");
                                        
                                        if ($recent_bookings->num_rows > 0): 
                                            while($booking = $recent_bookings->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($booking['item_name']); ?>
                                                    <span class="badge bg-label-<?php echo $booking['item_type'] == 'Room' ? 'primary' : 'info'; ?> ms-1">
                                                        <?php echo $booking['item_type']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($booking['status']) {
                                                        case 'pending': $status_class = 'bg-warning'; break;
                                                        case 'approved': $status_class = 'bg-success'; break;
                                                        case 'rejected': $status_class = 'bg-danger'; break;
                                                        case 'cancelled': $status_class = 'bg-secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($booking['status']); ?></span>
                                                </td>
                                            </tr>
                                            <?php endwhile; 
                                        else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    <div class="py-3">
                                                        <p class="text-muted">No bookings yet.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <a href="manage_bookings.php" class="btn btn-outline-primary">
                                    <i class="bx bx-show"></i> View All Bookings
                                </a>
                            </div>
                        </div>

                        <!-- Add Room Management Section -->
                        <div class="card mt-4" id="rooms-section">
                            <h5 class="card-header">Room Management</h5>
                            <div class="card-body">
                                <a href="add_room.php" class="btn btn-primary mb-3">
                                    <span class="tf-icons bx bx-plus"></span> Add New Room
                                </a>
                                <div class="table-responsive text-nowrap">
                                    <table class="table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Room</th>
                                                <th>Type</th>
                                                <th>Capacity</th>
                                                <th>Location</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0">
                                            <?php if ($rooms_result->num_rows > 0): ?>
                                                <?php while($room = $rooms_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($room['image']): ?>
                                                                <img src="uploads/rooms/<?php echo $room['image']; ?>" alt="Room" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="avatar avatar-sm me-3">
                                                                    <span class="avatar-initial rounded bg-label-primary">
                                                                        <i class="bx bx-building"></i>
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($room['name']); ?></strong><br>
                                                                <small class="text-muted"><?php echo htmlspecialchars(substr($room['description'], 0, 30)) . '...'; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($room['type']); ?></td>
                                                    <td><?php echo $room['capacity']; ?> people</td>
                                                    <td><?php echo htmlspecialchars($room['location']); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        switch($room['status']) {
                                                            case 'available': $status_class = 'bg-label-success'; break;
                                                            case 'maintenance': $status_class = 'bg-label-warning'; break;
                                                            case 'unavailable': $status_class = 'bg-label-danger'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($room['status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="edit_room.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-primary me-2">
                                                            <i class="bx bx-edit-alt"></i> Edit
                                                        </a>
                                                        <a href="index.php?delete_room_id=<?php echo $room['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Are you sure you want to delete this room?');">
                                                            <i class="bx bx-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">
                                                        <div class="py-4">
                                                            <i class="bx bx-building display-4 text-muted"></i>
                                                            <p class="text-muted mt-2">No rooms found. <a href="add_room.php">Add your first room</a></p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Add Facility Management Section -->
                        <div class="card mt-4" id="facilities-section">
                            <h5 class="card-header">Facility Management</h5>
                            <div class="card-body">
                                <a href="add_facility.php" class="btn btn-primary mb-3">
                                    <span class="tf-icons bx bx-plus"></span> Add New Facility
                                </a>
                                <div class="table-responsive text-nowrap">
                                    <table class="table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Facility</th>
                                                <th>Type</th>
                                                <th>Capacity</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0">
                                            <?php if ($facilities_result->num_rows > 0): ?>
                                                <?php while($facility = $facilities_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($facility['image']): ?>
                                                                <img src="uploads/facilities/<?php echo $facility['image']; ?>" alt="Facility" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="avatar avatar-sm me-3">
                                                                    <span class="avatar-initial rounded bg-label-info">
                                                                        <i class="bx bx-cog"></i>
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($facility['name']); ?></strong><br>
                                                                <small class="text-muted"><?php echo htmlspecialchars(substr($facility['description'], 0, 30)) . '...'; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($facility['type']); ?></td>
                                                    <td><?php echo $facility['capacity'] ? $facility['capacity'] . ' units' : 'N/A'; ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        switch($facility['status']) {
                                                            case 'available': $status_class = 'bg-label-success'; break;
                                                            case 'maintenance': $status_class = 'bg-label-warning'; break;
                                                            case 'unavailable': $status_class = 'bg-label-danger'; break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($facility['status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="edit_facility.php?id=<?php echo $facility['id']; ?>" class="btn btn-sm btn-primary me-2">
                                                            <i class="bx bx-edit-alt"></i> Edit
                                                        </a>
                                                        <a href="index.php?delete_facility_id=<?php echo $facility['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Are you sure you want to delete this facility?');">
                                                            <i class="bx bx-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">
                                                        <div class="py-4">
                                                            <i class="bx bx-cog display-4 text-muted"></i>
                                                            <p class="text-muted mt-2">No facilities found. <a href="add_facility.php">Add your first facility</a></p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- User Approval -->
                        <div class="card mt-4" id="pending-users">
                            <h5 class="card-header">
                                User Approval 
                                <?php if ($pending_approvals > 0): ?>
                                    <span class="badge bg-warning ms-2"><?php echo $pending_approvals; ?> Pending</span>
                                <?php endif; ?>
                            </h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Registration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if ($pending_users_result->num_rows > 0): ?>
                                            <?php while($user = $pending_users_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-3">
                                                            <img src="../assets/img/avatars/1.png" alt="Avatar" class="rounded-circle">
                                                        </div>
                                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($user['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <a href="index.php?approve_user_id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-success me-2"
                                                       onclick="return confirm('Approve this user?');">
                                                        <i class="bx bx-check"></i> Approve
                                                    </a>
                                                    <a href="index.php?reject_user_id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Are you sure you want to reject this user?');">
                                                        <i class="bx bx-x"></i> Reject
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    <div class="py-4">
                                                        <i class="bx bx-user-check display-4 text-muted"></i>
                                                        <p class="text-muted mt-2">No pending user approvals.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- All Users Management -->
                        <div class="card mt-4" id="all-users">
                            <h5 class="card-header">
                                All Users Management
                                <span class="badge bg-info ms-2"><?php echo $all_users_result->num_rows; ?> Total</span>
                            </h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Registration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if ($all_users_result->num_rows > 0): ?>
                                            <?php while($user = $all_users_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-3">
                                                            <span class="avatar-initial rounded-circle <?php echo $user['role'] == 'admin' ? 'bg-primary' : 'bg-secondary'; ?>">
                                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-primary' : 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($user['status']) {
                                                        case 'approved': $status_class = 'bg-success'; break;
                                                        case 'pending': $status_class = 'bg-warning'; break;
                                                        case 'rejected': $status_class = 'bg-danger'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($user['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo date('d M Y', strtotime($user['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($user['role'] != 'admin'): ?>
                                                        <a href="index.php?delete_user_id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Are you sure you want to delete this user?');">
                                                            <i class="bx bx-trash"></i> Delete
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Protected</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <div class="py-4">
                                                        <i class="bx bx-user display-4 text-muted"></i>
                                                        <p class="text-muted mt-2">No users found.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0">
                                © <script>document.write(new Date().getFullYear());</script>, made with ❤️ by <a href="#" target="_blank" class="footer-link fw-bolder">Admin Team</a>
                            </div>
                            <div>
                                <a href="#" class="footer-link me-4">License</a>
                                <a href="#" target="_blank" class="footer-link me-4">More Themes</a>
                                <a href="#" target="_blank" class="footer-link me-4">Documentation</a>
                                <a href="#" target="_blank" class="footer-link me-4">Support</a>
                            </div>
                        </div>
                    </footer>

                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboards-analytics.js"></script>
    
    <script>
        // Auto-hide success alert after 5 seconds
        setTimeout(function() {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 5000);

        // Smooth scroll to sections
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>

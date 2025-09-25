<?php
require_once 'db_connect.php';

// Proteksi halaman: user harus login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $user_id = $_SESSION['user_id'];
    $facility_id = $_POST['facility_id'] ? $_POST['facility_id'] : null;
    $ruangan_id = $_POST['ruangan_id'] ? $_POST['ruangan_id'] : null;
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = $_POST['purpose'];
    
    // Check for conflicts
    $conflict_query = "SELECT * FROM bookings WHERE 
        (facility_id = ? OR ruangan_id = ?) AND 
        booking_date = ? AND 
        status IN ('pending', 'approved') AND
        ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))";
    
    $stmt = $conn->prepare($conflict_query);
    $stmt->bind_param("iisssssss", $facility_id, $ruangan_id, $booking_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
    $stmt->execute();
    $conflict_result = $stmt->get_result();
    
    if ($conflict_result->num_rows > 0) {
        $error_message = "Time slot is already booked. Please choose a different time.";
    } else {
        $sql = "INSERT INTO bookings (user_id, facility_id, ruangan_id, booking_date, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiissss", $user_id, $facility_id, $ruangan_id, $booking_date, $start_time, $end_time, $purpose);
        
        if ($stmt->execute()) {
            $success_message = "Booking request submitted successfully! Please wait for admin approval.";
        } else {
            $error_message = "Error submitting booking. Please try again.";
        }
    }
}

// Get available rooms and facilities
$rooms_result = $conn->query("SELECT * FROM ruang WHERE status = 'available' ORDER BY name ASC");
$facilities_result = $conn->query("SELECT * FROM facilities WHERE status = 'available' ORDER BY name ASC");

// Get user's bookings
$user_bookings_result = $conn->query("SELECT b.*, COALESCE(r.name, f.name) as item_name, CASE WHEN b.ruangan_id IS NOT NULL THEN 'Room' ELSE 'Facility' END as item_type FROM bookings b LEFT JOIN ruang r ON b.ruangan_id = r.id LEFT JOIN facilities f ON b.facility_id = f.id WHERE b.user_id = {$_SESSION['user_id']} ORDER BY b.created_at DESC");

// Statistics
$total_rooms = $conn->query("SELECT COUNT(*) as count FROM ruang WHERE status = 'available'")->fetch_assoc()['count'];
$total_facilities = $conn->query("SELECT COUNT(*) as count FROM facilities WHERE status = 'available'")->fetch_assoc()['count'];
$user_bookings_count = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = {$_SESSION['user_id']}")->fetch_assoc()['count'];
$pending_bookings_count = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = {$_SESSION['user_id']} AND status = 'pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Room & Facility Booking | User Dashboard</title>
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
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="user_dashboard.php" class="app-brand-link">
                        <span class="app-brand-text demo menu-text fw-bolder ms-2">Room Booking</span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <li class="menu-item active">
                        <a href="user_dashboard.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#rooms-section" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-building"></i>
                            <div data-i18n="Rooms">Rooms</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#facilities-section" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-cog"></i>
                            <div data-i18n="Facilities">Facilities</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#my-bookings" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-calendar"></i>
                            <div data-i18n="My Bookings">My Bookings</div>
                        </a>
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
                                <input type="text" class="form-control border-0 shadow-none" placeholder="Search rooms or facilities..." aria-label="Search..." id="searchInput" />
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
                        
                        <!-- Welcome Card -->
                        <div class="row">
                            <div class="col-lg-8 mb-4 order-0">
                                <div class="card">
                                    <div class="d-flex align-items-end row">
                                        <div class="col-sm-7">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?>!</h5>
                                                <p class="mb-4">
                                                    Book rooms and facilities for your meetings, events, and activities. 
                                                    Browse available options and submit your booking requests.
                                                </p>
                                                <a href="#rooms-section" class="btn btn-sm btn-outline-primary me-2">Browse Rooms</a>
                                                <a href="#facilities-section" class="btn btn-sm btn-outline-secondary">Browse Facilities</a>
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
                            
                            <!-- Quick Stats -->
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
                                                <span class="fw-semibold d-block mb-1">Available Rooms</span>
                                                <h3 class="card-title mb-2"><?php echo $total_rooms; ?></h3>
                                                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> Ready to Book</small>
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
                                                <span>Available Facilities</span>
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
                                                <span>My Bookings</span>
                                                <h3 class="card-title text-nowrap mb-1"><?php echo $user_bookings_count; ?></h3>
                                                <small class="text-warning fw-semibold"><i class="bx bx-time"></i> Pending: <?php echo $pending_bookings_count; ?></small>
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
                                                <span>Status</span>
                                                <h3 class="card-title text-nowrap mb-1"><?php echo ucfirst($_SESSION['role']); ?></h3>
                                                <small class="text-success fw-semibold"><i class="bx bx-user"></i> Active</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Success/Error Messages -->
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <strong>Success!</strong> <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <strong>Error!</strong> <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Available Rooms -->
                        <div class="card mt-4" id="rooms-section">
                            <h5 class="card-header">Available Rooms</h5>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($rooms_result->num_rows > 0): ?>
                                        <?php while($room = $rooms_result->fetch_assoc()): ?>
                                        <div class="col-md-6 col-lg-4 mb-4 room-item">
                                            <div class="card h-100">
                                                <?php if ($room['image']): ?>
                                                    <img src="uploads/rooms/<?php echo $room['image']; ?>" class="card-img-top" alt="Room Image" style="height: 200px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                                        <i class="bx bx-building display-4 text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-body d-flex flex-column">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                                                    <p class="card-text text-muted"><?php echo htmlspecialchars($room['description']); ?></p>
                                                    <div class="mb-2">
                                                        <span class="badge bg-label-primary me-1"><?php echo htmlspecialchars($room['type']); ?></span>
                                                        <span class="badge bg-label-info me-1"><?php echo $room['capacity']; ?> people</span>
                                                    </div>
                                                    <p class="text-muted mb-2"><i class="bx bx-map me-1"></i><?php echo htmlspecialchars($room['location']); ?></p>
                                                    <div class="mt-auto">
                                                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#bookingModal" 
                                                                onclick="setBookingItem('room', <?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>')">
                                                            <i class="bx bx-calendar-plus me-1"></i>Book Room
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="text-center py-4">
                                                <i class="bx bx-building display-4 text-muted"></i>
                                                <p class="text-muted mt-2">No rooms available at the moment.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Available Facilities -->
                        <div class="card mt-4" id="facilities-section">
                            <h5 class="card-header">Available Facilities</h5>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($facilities_result->num_rows > 0): ?>
                                        <?php while($facility = $facilities_result->fetch_assoc()): ?>
                                        <div class="col-md-6 col-lg-4 mb-4 facility-item">
                                            <div class="card h-100">
                                                <?php if ($facility['image']): ?>
                                                    <img src="uploads/facilities/<?php echo $facility['image']; ?>" class="card-img-top" alt="Facility Image" style="height: 200px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                                        <i class="bx bx-cog display-4 text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-body d-flex flex-column">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($facility['name']); ?></h5>
                                                    <p class="card-text text-muted"><?php echo htmlspecialchars($facility['description']); ?></p>
                                                    <div class="mb-2">
                                                        <span class="badge bg-label-info me-1"><?php echo htmlspecialchars($facility['type']); ?></span>
                                                        <?php if ($facility['capacity']): ?>
                                                            <span class="badge bg-label-secondary"><?php echo $facility['capacity']; ?> units</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mt-auto">
                                                        <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#bookingModal" 
                                                                onclick="setBookingItem('facility', <?php echo $facility['id']; ?>, '<?php echo htmlspecialchars($facility['name']); ?>')">
                                                            <i class="bx bx-calendar-plus me-1"></i>Book Facility
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="text-center py-4">
                                                <i class="bx bx-cog display-4 text-muted"></i>
                                                <p class="text-muted mt-2">No facilities available at the moment.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- My Bookings -->
                        <div class="card mt-4" id="my-bookings">
                            <h5 class="card-header">My Bookings</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Item</th>
                                            <th>Date & Time</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if ($user_bookings_result->num_rows > 0): ?>
                                            <?php while($booking = $user_bookings_result->fetch_assoc()): ?>
                                            <tr>
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
                                                <td>
                                                    <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($booking['created_at'])); ?></small>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="py-4">
                                                        <i class="bx bx-calendar display-4 text-muted"></i>
                                                        <p class="text-muted mt-2">No bookings yet. Start by booking a room or facility!</p>
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
                                © <script>document.write(new Date().getFullYear());</script>, made with ❤️ by <a href="#" target="_blank" class="footer-link fw-bolder">Team</a>
                            </div>
                        </div>
                    </footer>

                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalTitle">Book Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="user_dashboard.php">
                    <div class="modal-body">
                        <input type="hidden" name="facility_id" id="modal_facility_id">
                        <input type="hidden" name="ruangan_id" id="modal_ruangan_id">
                        
                        <div class="mb-3">
                            <label for="booking_date" class="form-label">Booking Date</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="purpose" class="form-label">Purpose/Description</label>
                            <textarea class="form-control" id="purpose" name="purpose" rows="3" placeholder="Describe the purpose of your booking..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_booking" class="btn btn-primary">Submit Booking Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>

    <script>
        // Set booking item in modal
        function setBookingItem(type, id, name) {
            document.getElementById('bookingModalTitle').textContent = 'Book ' + name;
            
            if (type === 'room') {
                document.getElementById('modal_ruangan_id').value = id;
                document.getElementById('modal_facility_id').value = '';
            } else {
                document.getElementById('modal_facility_id').value = id;
                document.getElementById('modal_ruangan_id').value = '';
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const roomItems = document.querySelectorAll('.room-item');
            const facilityItems = document.querySelectorAll('.facility-item');
            
            roomItems.forEach(item => {
                const title = item.querySelector('.card-title').textContent.toLowerCase();
                const description = item.querySelector('.card-text').textContent.toLowerCase();
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            facilityItems.forEach(item => {
                const title = item.querySelector('.card-title').textContent.toLowerCase();
                const description = item.querySelector('.card-text').textContent.toLowerCase();
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

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

        // Validate time inputs
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = this.value;
            const endTimeInput = document.getElementById('end_time');
            
            if (startTime) {
                endTimeInput.min = startTime;
                if (endTimeInput.value && endTimeInput.value <= startTime) {
                    endTimeInput.value = '';
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert) {
                    alert.style.display = 'none';
                }
            });
        }, 5000);
    </script>
</body>
</html>

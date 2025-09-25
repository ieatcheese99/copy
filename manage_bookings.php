<?php
require_once 'db_connect.php';
require_once 'check_session.php';

// Proteksi halaman: hanya admin yang bisa akses
checkLogin('admin');

// Handle booking status updates
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
    
    $success_message = "Booking approved successfully!";
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
    
    $success_message = "Booking rejected successfully!";
}

if (isset($_GET['cancel_booking_id'])) {
    $booking_id = $_GET['cancel_booking_id'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    
    $success_message = "Booking cancelled successfully!";
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query based on filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter != 'all') {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($date_filter) {
    $where_conditions[] = "b.booking_date = ?";
    $params[] = $date_filter;
    $param_types .= 's';
}

if ($type_filter != 'all') {
    if ($type_filter == 'room') {
        $where_conditions[] = "b.ruangan_id IS NOT NULL";
    } else {
        $where_conditions[] = "b.facility_id IS NOT NULL";
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$query = "
    SELECT b.*, u.username, u.email,
           COALESCE(r.name, f.name) as item_name,
           CASE WHEN b.ruangan_id IS NOT NULL THEN 'Room' ELSE 'Facility' END as item_type,
           COALESCE(r.location, 'N/A') as location,
           pb.admin_id, pb.action as admin_action, pb.created_at as action_date,
           admin.username as admin_name
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    LEFT JOIN ruang r ON b.ruangan_id = r.id 
    LEFT JOIN facilities f ON b.facility_id = f.id 
    LEFT JOIN penyetujuan_booking pb ON b.id = pb.booking_id
    LEFT JOIN users admin ON pb.admin_id = admin.id
    $where_clause
    ORDER BY b.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $bookings_result = $stmt->get_result();
} else {
    $bookings_result = $conn->query($query);
}

// Get statistics
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'approved'")->fetch_assoc()['count'];
$rejected_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'rejected'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Manage Bookings | Room Booking Admin</title>
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
                    <a href="index.php" class="app-brand-link">
                        <span class="app-brand-text demo menu-text fw-bolder ms-2">Room Booking Admin</span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <li class="menu-item">
                        <a href="index.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
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
                        </ul>
                    </li>
                    <li class="menu-item active">
                        <a href="manage_bookings.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-calendar"></i>
                            <div data-i18n="Manage Bookings">Manage Bookings</div>
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
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
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
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                                    <small class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></small>
                                                </div>
                                            </div>
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
                        <h4 class="fw-bold py-3 mb-4">Manage Bookings</h4>

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 col-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <img src="../assets/img/icons/unicons/chart-success.png" alt="chart success" class="rounded" />
                                            </div>
                                        </div>
                                        <span class="fw-semibold d-block mb-1">Total Bookings</span>
                                        <h3 class="card-title mb-2"><?php echo $total_bookings; ?></h3>
                                        <small class="text-success fw-semibold"><i class="bx bx-calendar"></i> All Time</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <img src="../assets/img/icons/unicons/wallet-info.png" alt="Credit Card" class="rounded" />
                                            </div>
                                        </div>
                                        <span>Pending</span>
                                        <h3 class="card-title text-nowrap mb-1"><?php echo $pending_bookings; ?></h3>
                                        <small class="text-warning fw-semibold"><i class="bx bx-time"></i> Need Review</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <img src="../assets/img/icons/unicons/cc-primary.png" alt="Credit Card" class="rounded" />
                                            </div>
                                        </div>
                                        <span>Approved</span>
                                        <h3 class="card-title text-nowrap mb-1"><?php echo $approved_bookings; ?></h3>
                                        <small class="text-success fw-semibold"><i class="bx bx-check"></i> Confirmed</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-title d-flex align-items-start justify-content-between">
                                            <div class="avatar flex-shrink-0">
                                                <img src="../assets/img/icons/unicons/paypal.png" alt="Credit Card" class="rounded" />
                                            </div>
                                        </div>
                                        <span>Rejected</span>
                                        <h3 class="card-title text-nowrap mb-1"><?php echo $rejected_bookings; ?></h3>
                                        <small class="text-danger fw-semibold"><i class="bx bx-x"></i> Declined</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" action="manage_bookings.php" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date" class="form-label">Booking Date</label>
                                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="type" class="form-label">Type</label>
                                        <select class="form-select" id="type" name="type">
                                            <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                            <option value="room" <?php echo $type_filter == 'room' ? 'selected' : ''; ?>>Rooms</option>
                                            <option value="facility" <?php echo $type_filter == 'facility' ? 'selected' : ''; ?>>Facilities</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bx bx-search"></i> Filter
                                            </button>
                                            <a href="manage_bookings.php" class="btn btn-secondary">
                                                <i class="bx bx-refresh"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Bookings Table -->
                        <div class="card">
                            <h5 class="card-header">All Bookings</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>User</th>
                                            <th>Item</th>
                                            <th>Date & Time</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Admin Action</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        <?php if ($bookings_result->num_rows > 0): ?>
                                            <?php while($booking = $bookings_result->fetch_assoc()): ?>
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
                                                        <?php if ($booking['location'] != 'N/A'): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($booking['location']); ?></small>
                                                        <?php endif; ?>
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
                                                    <?php if ($booking['admin_name']): ?>
                                                        <div>
                                                            <small class="text-muted">By: <?php echo htmlspecialchars($booking['admin_name']); ?></small><br>
                                                            <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($booking['action_date'])); ?></small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($booking['status'] == 'pending'): ?>
                                                        <a href="manage_bookings.php?approve_booking_id=<?php echo $booking['id']; ?>" 
                                                           class="btn btn-sm btn-success me-1"
                                                           onclick="return confirm('Approve this booking?');">
                                                            <i class="bx bx-check"></i>
                                                        </a>
                                                        <a href="manage_bookings.php?reject_booking_id=<?php echo $booking['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Reject this booking?');">
                                                            <i class="bx bx-x"></i>
                                                        </a>
                                                    <?php elseif ($booking['status'] == 'approved'): ?>
                                                        <a href="manage_bookings.php?cancel_booking_id=<?php echo $booking['id']; ?>" 
                                                           class="btn btn-sm btn-warning"
                                                           onclick="return confirm('Cancel this booking?');">
                                                            <i class="bx bx-x-circle"></i> Cancel
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No actions</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <div class="py-4">
                                                        <i class="bx bx-calendar display-4 text-muted"></i>
                                                        <p class="text-muted mt-2">No bookings found with the current filters.</p>
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
                                © <script>document.write(new Date().getFullYear());</script>, made with ❤️ by <a href="#" target="_blank" class="footer-link fw-bolder">Admin</a>
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
    <script src="../assets/js/main.js"></script>

    <script>
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

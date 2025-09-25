<?php
require_once 'db_connect.php';

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil ID facility dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$facility_id = $_GET['id'];

// Ambil data facility
$stmt = $conn->prepare("SELECT * FROM facilities WHERE id = ?");
$stmt->bind_param("i", $facility_id);
$stmt->execute();
$result = $stmt->get_result();
$facility = $result->fetch_assoc();

if (!$facility) {
    header("Location: index.php");
    exit();
}

// Proses update facility
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $capacity = $_POST['capacity'] ? $_POST['capacity'] : null;
    $status = $_POST['status'];
    
    // Handle image upload
    $image = $facility['image']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/facilities/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Validate image
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if ($facility['image'] && file_exists($upload_dir . $facility['image'])) {
                    unlink($upload_dir . $facility['image']);
                }
                $image = $new_image;
            } else {
                $error_message = "Error uploading image.";
            }
        } else {
            $error_message = "Invalid image file. Please upload JPEG, PNG, GIF, or WebP files under 5MB.";
        }
    }

    if (!isset($error_message)) {
        $sql = "UPDATE facilities SET name = ?, description = ?, type = ?, capacity = ?, status = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssissi", $name, $description, $type, $capacity, $status, $image, $facility_id);

        if ($stmt->execute()) {
            $success_message = "Facility updated successfully!";
            header("refresh:2;url=index.php");
        } else {
            $error_message = "Error updating facility. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Edit Facility | Room Booking</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
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
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <li class="menu-item">
                        <a href="index.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="#" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-edit"></i>
                            <div data-i18n="Edit Facility">Edit Facility</div>
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
                        <h4 class="fw-bold py-3 mb-4">Edit Facility</h4>

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Facility Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="edit_facility.php?id=<?php echo $facility_id; ?>" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Facility Name</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($facility['name']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($facility['description']); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="type" class="form-label">Facility Type</label>
                                                <select class="form-select" id="type" name="type" required>
                                                    <option value="">Select Facility Type</option>
                                                    <option value="Audio Visual" <?php echo ($facility['type'] == 'Audio Visual') ? 'selected' : ''; ?>>Audio Visual</option>
                                                    <option value="Computer" <?php echo ($facility['type'] == 'Computer') ? 'selected' : ''; ?>>Computer</option>
                                                    <option value="Office Equipment" <?php echo ($facility['type'] == 'Office Equipment') ? 'selected' : ''; ?>>Office Equipment</option>
                                                    <option value="Sports Equipment" <?php echo ($facility['type'] == 'Sports Equipment') ? 'selected' : ''; ?>>Sports Equipment</option>
                                                    <option value="Laboratory Equipment" <?php echo ($facility['type'] == 'Laboratory Equipment') ? 'selected' : ''; ?>>Laboratory Equipment</option>
                                                    <option value="Other" <?php echo ($facility['type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="capacity" class="form-label">Capacity/Quantity (Optional)</label>
                                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" 
                                                       value="<?php echo $facility['capacity']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="">Select Status</option>
                                                    <option value="available" <?php echo ($facility['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                                    <option value="maintenance" <?php echo ($facility['status'] == 'maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                                                    <option value="unavailable" <?php echo ($facility['status'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="image" class="form-label">Facility Image</label>
                                                <?php if ($facility['image']): ?>
                                                    <div class="mb-2">
                                                        <img src="uploads/facilities/<?php echo $facility['image']; ?>" alt="Current Image" class="img-thumbnail" style="max-width: 200px;">
                                                        <p class="text-muted mt-1">Current image</p>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                <small class="text-muted">Upload a new image to replace the current one (optional). Max size: 5MB. Formats: JPEG, PNG, GIF, WebP</small>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between">
                                                <a href="index.php" class="btn btn-secondary">
                                                    <i class="bx bx-arrow-back"></i> Back to Dashboard
                                                </a>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bx bx-save"></i> Update Facility
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Current Preview</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="preview">
                                            <div class="d-flex align-items-center justify-content-start">
                                                <?php if ($facility['image']): ?>
                                                    <img src="uploads/facilities/<?php echo $facility['image']; ?>" alt="Facility" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="avatar avatar-sm me-3">
                                                        <span class="avatar-initial rounded bg-label-info">
                                                            <i class="bx bx-cog"></i>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-start">
                                                    <strong><?php echo htmlspecialchars($facility['name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($facility['type']); ?><?php echo $facility['capacity'] ? ' • ' . $facility['capacity'] . ' units' : ''; ?></small><br>
                                                    <span class="badge bg-label-<?php echo $facility['status'] == 'available' ? 'success' : ($facility['status'] == 'maintenance' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($facility['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        // Preview functionality
        function updatePreview() {
            const name = document.getElementById('name').value;
            const type = document.getElementById('type').value;
            const capacity = document.getElementById('capacity').value;
            const status = document.getElementById('status').value;
            
            const preview = document.getElementById('preview');
            
            if (name && type && status) {
                let statusClass = '';
                switch(status) {
                    case 'available': statusClass = 'bg-label-success'; break;
                    case 'maintenance': statusClass = 'bg-label-warning'; break;
                    case 'unavailable': statusClass = 'bg-label-danger'; break;
                }
                
                preview.innerHTML = `
                    <div class="d-flex align-items-center justify-content-start">
                        <?php if ($facility['image']): ?>
                            <img src="uploads/facilities/<?php echo $facility['image']; ?>" alt="Facility" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-cog"></i>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="text-start">
                            <strong>${name}</strong><br>
                            <small class="text-muted">${type}${capacity ? ' • ' + capacity + ' units' : ''}</small><br>
                            <span class="badge ${statusClass}">${status}</span>
                        </div>
                    </div>
                `;
            }
        }
        
        // Add event listeners
        document.getElementById('name').addEventListener('input', updatePreview);
        document.getElementById('type').addEventListener('change', updatePreview);
        document.getElementById('capacity').addEventListener('input', updatePreview);
        document.getElementById('status').addEventListener('change', updatePreview);
    </script>
</body>
</html>

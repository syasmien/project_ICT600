<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$error_msg = "";

if (isset($_POST['add_hall'])) {

    $hall_name = mysqli_real_escape_string($conn, $_POST['hall_name']);
    $hall_capacity = intval($_POST['hall_capacity']);
    $price = floatval($_POST['price']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // --- FILE UPLOAD PROCESSING LOGIC ---
    $image_name = ""; // Default empty value if no image is uploaded
    
    if (isset($_FILES['hall_image']) && $_FILES['hall_image']['error'] == 0) {
        $target_dir = "../assets/images/";
        
        // Create unique name to avoid overwriting existing files with the same name
        $file_extension = pathinfo($_FILES["hall_image"]["name"], PATHINFO_EXTENSION);
        $image_name = time() . '_' . uniqid() . '.' . $file_extension; 
        $target_file = $target_dir . $image_name;
        
        // Validate file type (optional but highly recommended)
        $allowed_types = array('jpg', 'jpeg', 'png', 'webp');
        if (in_array(strtolower($file_extension), $allowed_types)) {
            // Move uploaded temporary file to your assets target directory
            if (!move_uploaded_file($_FILES["hall_image"]["tmp_name"], $target_file)) {
                $error_msg = "Failed to upload image to directory.";
            }
        } else {
            $error_msg = "Only JPG, JPEG, PNG, and WEBP formats are allowed.";
        }
    }

    // Only save to database if no upload errors occurred
    if (empty($error_msg)) {
        mysqli_query($conn, "
            INSERT INTO hall (hall_name, hall_capacity, price, status, image)
            VALUES ('$hall_name', '$hall_capacity', '$price', '$status', '$image_name')
        ");

        header("Location: manage_hall.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Hall</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/staff_style.css">
    
    <style>
        /* Small styling touch-up for file input field layout */
        .form-card input[type="file"] {
            padding: 8px 0;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #ef4444;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">◆</div>
            <span class="brand-name">HallBook</span>
        </div>

        <nav class="sidebar-nav">
            <a href="staff_dashboard.php" class="nav-item">
                <span class="nav-icon">&#9783;</span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="manage_hall.php" class="nav-item active">
                <span class="nav-icon">&#9962;</span>
                <span class="nav-label">Manage Halls</span>
            </a>
            <a href="manage_bookings.php" class="nav-item">
                <span class="nav-icon">&#128203;</span>
                <span class="nav-label">Manage Bookings</span>
            </a>
            <a href="manage_users.php" class="nav-item">
                <span class="nav-icon">👤</span>
                <span class="nav-label">Manage Users</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="staff-info">
                <div class="staff-avatar"><?php echo strtoupper(substr($_SESSION['name'],0,1)); ?></div>
                <div>
                    <div class="staff-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="staff-role">Staff</div>
                </div>
            </div>

            <a class="logout-btn" href="../auth/logout.php">Logout</a>
        </div>
    </aside>

    <main class="main-content">

        <div class="topbar">
            <h1 class="page-title">Add New Hall</h1>
            <p class="page-subtitle">Fill in hall details below</p>
        </div>

        <div class="form-wrapper">

            <form method="POST" enctype="multipart/form-data" class="form-card">

                <?php if (!empty($error_msg)): ?>
                    <div class="alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <label>Hall Name<span class="required">*</span></label>
                <input type="text" name="hall_name" placeholder="e.g Grand Ballroom" required>

                <label>Capacity<span class="required">*</span></label>
                <input type="number" name="hall_capacity" placeholder="e.g 300" required>

                <label>Price (RM)<span class="required">*</span></label>
                <input type="number" name="price" placeholder="e.g 1500" required>

                <label>Hall Image Preview</label>
                <input type="file" name="hall_image" accept="image/*">

                <label>Status<span class="required">*</span></label>
                <select name="status">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                    <option value="Maintenance">Maintenance</option>
                </select>

                <button type="submit" name="add_hall" class="btn-primary full-btn">
                    Save Hall
                </button>

                <a href="manage_hall.php" class="back-link">← Back</a>

            </form>

        </div>

    </main>
</div>

</body>
</html>
<?php
include('../config/db.php');
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_hall.php");
    exit();
}

$hall_id = intval($_GET['id']);

$result = mysqli_query($conn, "SELECT * FROM hall WHERE hall_id = $hall_id");
$hall = mysqli_fetch_assoc($result);

if (!$hall) {
    echo "Hall not found!";
    exit();
}

$error_msg = "";

if (isset($_POST['update'])) {

    $hall_name = mysqli_real_escape_string($conn, $_POST['hall_name']);
    $hall_capacity = intval($_POST['hall_capacity']);
    $price = floatval($_POST['price']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Maintain existing image by default
    $image_name = $hall['image']; 

    // --- UPLOAD NEW IMAGE PROCESSOR LOGIC ---
    if (isset($_FILES['hall_image']) && $_FILES['hall_image']['error'] == 0) {
        $target_dir = "../assets/images/";
        
        $file_extension = pathinfo($_FILES["hall_image"]["name"], PATHINFO_EXTENSION);
        $new_generated_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_generated_name;
        
        $allowed_types = array('jpg', 'jpeg', 'png', 'webp');
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES["hall_image"]["tmp_name"], $target_file)) {
                
                // Optional Cleanup: Remove previous old image from folder if it exists
                if (!empty($hall['image']) && file_exists($target_dir . $hall['image'])) {
                    unlink($target_dir . $hall['image']);
                }
                
                $image_name = $new_generated_name; // Set name string references to insert
            } else {
                $error_msg = "Failed to upload new image to server directory.";
            }
        } else {
            $error_msg = "Only JPG, JPEG, PNG, and WEBP file formats are allowed.";
        }
    }

    if (empty($error_msg)) {
        mysqli_query($conn, "
            UPDATE hall SET
                hall_name='$hall_name',
                hall_capacity='$hall_capacity',
                price='$price',
                status='$status',
                image='$image_name'
            WHERE hall_id=$hall_id
        ");

        header("Location: manage_hall.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Hall</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/staff_style.css">
    <style>
        .form-card input[type="file"] {
            padding: 8px 0;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        .current-preview-box {
            width: 100%;
            height: 140px;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
        }
        .current-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .current-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, #e0f2fe, #bcbeff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-size: 0.85rem;
            font-weight: 500;
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
            <div class="brand-icon">&#9670;</div>
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
                <div class="staff-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
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
            <div class="topbar-inner">
                <div>
                    <h1 class="page-title">Edit Hall Information</h1>
                    <p class="page-subtitle">Update hall parameters or replace image fields</p>
                </div>
            </div>
        </div>

        <div class="form-wrapper">

            <form method="POST" enctype="multipart/form-data" class="form-card">

                <?php if (!empty($error_msg)): ?>
                    <div class="alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <label>Hall Name</label>
                <input type="text" name="hall_name" value="<?php echo htmlspecialchars($hall['hall_name']); ?>" required>

                <label>Capacity</label>
                <input type="number" name="hall_capacity" value="<?php echo htmlspecialchars($hall['hall_capacity']); ?>" required>

                <label>Price (RM)</label>
                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($hall['price']); ?>" required>

                <label>Current Hall Image</label>
                <div class="current-preview-box">
                    <?php if (!empty($hall['image']) && file_exists('../assets/images/' . $hall['image'])): ?>
                        <img src="../assets/images/<?php echo $hall['image']; ?>" alt="Preview">
                    <?php else: ?>
                        <div class="current-placeholder">No Graphic Image Found Placeholder</div>
                    <?php endif; ?>
                </div>

                <label>Upload New Cover Image <span style="font-weight: normal; color: #64748b; font-size: 0.8rem;">(Leaves original if left empty)</span></label>
                <input type="file" name="hall_image" accept="image/*">

                <label>Status</label>
                <select name="status">
                    <option value="Available" <?php if($hall['status']=="Available") echo "selected"; ?>>Available</option>
                    <option value="Unavailable" <?php if($hall['status']=="Unavailable") echo "selected"; ?>>Unavailable</option>
                    <option value="Maintenance" <?php if($hall['status']=="Maintenance") echo "selected"; ?>>Maintenance</option>
                </select>

                <button type="submit" name="update" class="btn-primary" style="width:100%; margin-top:15px;">
                    Update Hall
                </button>

                <a href="manage_hall.php" class="back-link">← Back</a>

            </form>

        </div>

    </main>
</div>

</body>
</html>
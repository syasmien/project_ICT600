<?php
// 1. Database Connection Configuration
$host = "localhost";
$username = "root"; 
$password = "";     
$dbname = "event_hall_booking"; 

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Fixed mock account customer identifier matching system testing profiles
$current_cust_id = 1; 

$message = "";

// 2. Fetch Existing Profile Information
$fetch_query = "SELECT full_name, email, phone_num, username, profile_pic FROM customer WHERE cust_id = ?";
$stmt = $conn->prepare($fetch_query);
$stmt->bind_param("i", $current_cust_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Fallback in case customer record does not exist in your database yet
if (!$user_data) {
    $user_data = [
        'full_name' => '',
        'email' => '',
        'phone' => '',
        'username' => '',
        'profile_pic' => ''
    ];
}

// 3. Handle Form Submission (Text & Profile Picture Upload)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $username_val = mysqli_real_escape_string($conn, $_POST['username']);
    
    $profile_pic_path = $user_data['profile_pic']; // Default to current picture
    
    // Check if an image was uploaded
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Validation Safeguards
        if (in_array($file_ext, $allowed_extensions)) {
            if ($file_size <= 2 * 1024 * 1024) { // 2MB Max Limit
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename to prevent collisions
                $new_filename = 'cust_' . $current_cust_id . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $target_file)) {
                    // Delete old file if exists and is not the default
                    if (!empty($user_data['profile_pic']) && file_exists('../uploads/' . $user_data['profile_pic'])) {
                        @unlink('../uploads/' . $user_data['profile_pic']);
                    }
                    $profile_pic_path = $new_filename;
                } else {
                    $message = "<div class='alert error'>❌ Failed to save uploaded image. Check folder permissions.</div>";
                }
            } else {
                $message = "<div class='alert error'>❌ Image too large! Max file size is 2MB.</div>";
            }
        } else {
            $message = "<div class='alert error'>❌ Invalid file type. Only JPG, PNG, and GIF allowed.</div>";
        }
    }
    
    // Save to Database
    if (empty($message)) {
        // Double-check if the profile record needs to be created or updated
        $check_exist = $conn->query("SELECT cust_id FROM customer WHERE cust_id = $current_cust_id");
        
        if ($check_exist->num_rows > 0) {
            $update_query = "UPDATE customer SET full_name = ?, email = ?, phone = ?, username = ?, profile_pic = ? WHERE cust_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssssi", $full_name, $email, $phone, $username_val, $profile_pic_path, $current_cust_id);
            $success = $update_stmt->execute();
            $update_stmt->close();
        } else {
            // INSERT fallback if record doesn't exist
            $insert_query = "INSERT INTO customer (cust_id, full_name, email, phone, username, profile_pic) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("isssss", $current_cust_id, $full_name, $email, $phone, $username_val, $profile_pic_path);
            $success = $insert_stmt->execute();
            $insert_stmt->close();
        }
        
        if ($success) {
            $message = "<div class='alert success'>🎉 Profile updated successfully!</div>";
            // Refresh variables with newly saved info
            $user_data['full_name'] = $full_name;
            $user_data['email'] = $email;
            $user_data['phone'] = $phone;
            $user_data['username'] = $username_val;
            $user_data['profile_pic'] = $profile_pic_path;
        } else {
            $message = "<div class='alert error'>❌ Error updating database profile: " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Event Hall Booking System</title>
    <!-- Use your main style sheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- SAFEGUARD: Inline rules to prevent SVGs from expanding and layout formatting -->
    <style>
        svg {
            max-width: 50px !important;
            max-height: 50px !important;
            display: inline-block;
        }
        .avatar-placeholder svg {
            max-width: 45px !important;
            max-height: 45px !important;
        }
        .avatar-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .nav-item a svg {
            max-width: 20px !important;
            max-height: 20px !important;
        }

        /* Centered Profile Card Layout */
        .centered-form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 10px 0 30px 0;
        }
        .profile-card {
            background-color: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .profile-card-title {
            color: #0f172a;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Upload Avatar Section (Interactive Preview) */
        .profile-image-upload-wrapper {
            position: relative;
            margin: 0 auto 30px auto;
            width: 120px;
            height: 120px;
        }
        .profile-avatar-preview {
            width: 120px;
            height: 120px;
            border: 3px solid #0f172a;
            border-radius: 50%;
            background-color: #bae6fd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .profile-avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-avatar-preview svg {
            width: 60px !important;
            height: 60px !important;
            fill: #0f172a;
        }
        .upload-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #bae6fd;
            border: 2px solid #0f172a;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .upload-badge:hover {
            background-color: #38bdf8;
            transform: scale(1.1);
        }
        .upload-badge svg {
            width: 18px !important;
            height: 18px !important;
            fill: #0f172a;
        }
        .file-input-hidden {
            display: none;
        }

        /* Form Layout */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input {
            width: 100%;
            padding: 13px;
            border: 2px solid #0f172a;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            background-color: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* Button Custom Styles */
        .btn-save-profile {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #0f172a; /* Premium Dark Navy */
            color: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.15);
        }
        .btn-save-profile:hover {
            background-color: #1e293b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="app-container">
    
    <!-- LEFT SIDEBAR COMPONENT (Consistent customer side navigation) -->
    <aside class="sidebar">
        <div class="profile-section">
            <div class="avatar-placeholder">
                <?php if (!empty($user_data['profile_pic']) && file_exists('../uploads/' . $user_data['profile_pic'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($user_data['profile_pic']); ?>" alt="Avatar">
                <?php else: ?>
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5-4-8-4z"/>
                    </svg>
                <?php endif; ?>
            </div>
            <!-- Live username/role display -->
            <div class="profile-role"><?php echo !empty($user_data['username']) ? htmlspecialchars($user_data['username']) : 'Customer'; ?></div>
        </div>
        
        <nav class="nav-menu">
            <ul>
                <li class="nav-item">
                    <a href="dashboard.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                        DASHBOARD
                    </a>
                </li>
                <li class="nav-item">
                    <a href="add_booking.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        ADD BOOKING
                    </a>
                </li>
                <li class="nav-item">
                    <a href="booking_status.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                        BOOKING STATUS
                    </a>
                </li>
                <li class="nav-item active">
                    <a href="profile.php">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                        </svg>
                        PROFILE
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <svg viewBox="0 0 24 24">
                            <path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/>
                        </svg>
                        LOGOUT
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <!-- MAIN APPLICATION WORKSPACE -->
    <main class="main-content">
        
        <h2 class="section-title">Modify Account Profile</h2>
        
        <?php echo $message; ?>
        
        <div class="centered-form-container">
            <div class="profile-card">
                <h3 class="profile-card-title">Update Your Profile</h3>
                
                <!-- multipart/form-data configuration allows file transfers -->
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    
                    <!-- Circular Avatar Profile Picture Section -->
                    <div class="profile-image-upload-wrapper">
                        <div class="profile-avatar-preview" id="image_preview">
                            <?php if (!empty($user_data['profile_pic']) && file_exists('../uploads/' . $user_data['profile_pic'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($user_data['profile_pic']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5-4-8-4z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <label for="profile_image" class="upload-badge" title="Change Profile Picture">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 4V1h2v3h3v2H5v3H3V6H0V4h3zm3 6V7h3V4h7l1.83 2H21c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V10h3zm7 9c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0-2c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/>
                            </svg>
                        </label>
                        <input type="file" id="profile_image" name="profile_image" class="file-input-hidden" accept="image/*" onchange="previewProfileImage(event)">
                    </div>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="e.g. John Doe" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="e.g. johndoe@gmail.com" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    
                    <!-- Phone Number -->
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" placeholder="e.g. +6012345678" value="<?php echo htmlspecialchars($user_data['phone_num']); ?>" required>
                    </div>
                    
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="e.g. johndoe99" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    
                    <!-- Submit -->
                    <button type="submit" name="save_profile" class="btn-save-profile">Save Changes</button>
                    
                </form>
            </div>
        </div>
        
    </main>
</div>

<!-- CLIENT JAVASCRIPT: Immediate Image Preview Engine -->
<script>
function previewProfileImage(event) {
    var reader = new FileReader();
    reader.onload = function() {
        var output = document.getElementById('image_preview');
        output.innerHTML = '<img src="' + reader.result + '" alt="Profile Preview">';
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>
<?php
$conn->close();
?>
<?php
include('../config/db.php');

$error = "";
$success = "";
$redirect = false;
$form_data = array();

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && 
           preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
}

// Function to validate phone number (Malaysian format)
function validate_phone($phone) {
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    return preg_match('/^(\+?6?01[0-9]{8,9}|0[0-9]{9,10})$/', $phone);
}

if (isset($_POST['register'])) {

    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone_num = sanitize_input($_POST['phone_num'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $form_data = compact('full_name', 'email', 'phone_num', 'username');
    $validation_errors = array();

    // 1. Validate Full Name
    if (empty($full_name)) {
        $validation_errors['full_name'] = "Full name is required";
    } elseif (strlen($full_name) < 2) {
        $validation_errors['full_name'] = "Full name must be at least 2 characters";
    } elseif (strlen($full_name) > 50) {
        $validation_errors['full_name'] = "Full name must be less than 50 characters";
    } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $full_name)) {
        $validation_errors['full_name'] = "Full name can only contain letters, spaces, hyphens, and apostrophes";
    }
    
    // 2. Validate Email
    if (empty($email)) {
        $validation_errors['email'] = "Email is required";
    } elseif (!validate_email($email)) {
        $validation_errors['email'] = "Please enter a valid email address";
    }
    
    // 3. Validate Phone Number
    if (empty($phone_num)) {
        $validation_errors['phone_num'] = "Phone number is required";
    } elseif (!validate_phone($phone_num)) {
        $validation_errors['phone_num'] = "Please enter a valid phone number (e.g., 0123456789 or +60123456789)";
    }
    
    // 4. Validate Username
    if (empty($username)) {
        $validation_errors['username'] = "Username is required";
    } elseif (strlen($username) < 3) {
        $validation_errors['username'] = "Username must be at least 3 characters";
    } elseif (strlen($username) > 20) {
        $validation_errors['username'] = "Username must be less than 20 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $validation_errors['username'] = "Username can only contain letters, numbers, and underscores";
    }
    
    // 5. Validate Password
    if (empty($password)) {
        $validation_errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $validation_errors['password'] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $validation_errors['password'] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $validation_errors['password'] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $validation_errors['password'] = "Password must contain at least one number";
    } elseif (!preg_match('/[`!@#$%^&*()_+\-=\[\]{};:\'\"\\|,.<>\/?~]/', $password)) {
        $validation_errors['password'] = "Password must contain at least one special character";
    }
    
    // 6. Validate Confirm Password
    if (empty($confirm_password)) {
        $validation_errors['confirm_password'] = "Please confirm your password";
    } elseif ($password !== $confirm_password) {
        $validation_errors['confirm_password'] = "Passwords do not match";
    }
    
    // If no validation errors, proceed with registration
    if (empty($validation_errors)) {

        // check email exists (use prepared statement for security)
        $check = "SELECT * FROM customer WHERE email=?";
        $stmt = mysqli_prepare($conn, $check);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email already exists! Please login.";
        } else {
            // Check username exists
            $check_username = "SELECT * FROM customer WHERE username=?";
            $stmt2 = mysqli_prepare($conn, $check_username);
            mysqli_stmt_bind_param($stmt2, "s", $username);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            
            if (mysqli_num_rows($result2) > 0) {
                $error = "Username already taken! Please choose another.";
            } else {
                // FIX: Hash the password before storing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert data with hashed password and status
                $sql = "INSERT INTO customer (full_name, email, username, password, phone_num, status) 
                        VALUES (?, ?, ?, ?, ?, 'Active')";
                
                $stmt3 = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt3, "sssss", $full_name, $email, $username, $hashed_password, $phone_num);

                if (mysqli_stmt_execute($stmt3)) {
                    $success = "Registration successful! Redirecting to login...";
                    $redirect = true;
                } else {
                    $error = "Error: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt3);
            }
            mysqli_stmt_close($stmt2);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HallBook</title>
    <?php if ($redirect): ?>
    <!-- Meta refresh for redirect (WORKS 100% of the time) -->
    <meta http-equiv="refresh" content="2;url=login.php">
    <?php endif; ?>
    <style>
        :root {
            --primary-color: #3b82f6;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --error-color: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .register-container {
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 440px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-wrapper {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background-color: var(--primary-color);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transform: rotate(45deg);
            margin-bottom: 16px;
            transition: transform 0.3s ease;
        }

        .logo-icon:hover {
            transform: rotate(135deg);
        }

        .logo-icon::before {
            content: '';
            width: 12px;
            height: 12px;
            background-color: #ffffff;
            transform: rotate(-45deg);
            border-radius: 2px;
        }

        h2 {
            margin: 0 0 6px 0;
            font-size: 26px;
            font-weight: 700;
            color: var(--text-main);
            text-align: center;
        }

        .login-link {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 28px;
            text-align: center;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: var(--error-color);
            margin-left: 4px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 15px;
            background-color: #f8fafc;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        input.error {
            border-color: var(--error-color);
            background-color: #fef2f2;
        }

        input.error:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        input.success {
            border-color: var(--success-color);
            background-color: #f0fdf4;
        }

        input.success:focus {
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        input::placeholder {
            color: #94a3b8;
        }

        .error-message {
            color: var(--error-color);
            font-size: 13px;
            margin-top: 6px;
            display: none;
            font-weight: 500;
        }

        .error-message.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .validation-list {
            margin-top: 8px;
            padding-left: 0;
            list-style: none;
            font-size: 13px;
        }

        .validation-list li {
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .validation-list li.valid {
            color: var(--success-color);
        }

        .validation-list li.invalid {
            color: var(--error-color);
        }

        .match-hint {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s ease;
        }

        button:hover {
            background-color: #2563eb;
        }

        .message {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
            padding: 10px;
            border-radius: 6px;
        }

        .message.error {
            color: var(--error-color);
            background-color: #fee2e2;
        }

        .message.success {
            color: var(--success-color);
            background-color: #f0fdf4;
            border: 1px solid #86efac;
        }

        .redirect-progress {
            margin-top: 10px;
            text-align: center;
        }

        .redirect-progress .progress-bar {
            width: 100%;
            height: 4px;
            background-color: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }

        .redirect-progress .progress-fill {
            height: 100%;
            background-color: var(--success-color);
            width: 0%;
            animation: progress 2s linear forwards;
        }

        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="register-container">

    <div class="logo-wrapper">
        <div class="logo-icon"></div>
        <h2>Create Account</h2>
    </div>
    
    <div class="login-link">
        Already Registered? <a href="login.php">Log in here</a>
    </div>

    <form method="POST" id="registerForm" novalidate>

        <div class="form-group">
            <label>Full Name <span class="required">*</span></label>
            <input type="text" name="full_name" id="full_name" placeholder="Enter your full name" 
                   value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>" required>
            <div class="error-message" id="full_name_error">
                <?php echo isset($validation_errors['full_name']) ? $validation_errors['full_name'] : ''; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Email Address <span class="required">*</span></label>
            <input type="email" name="email" id="email" placeholder="Enter your email" 
                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
            <div class="error-message" id="email_error">
                <?php echo isset($validation_errors['email']) ? $validation_errors['email'] : ''; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Phone Number <span class="required">*</span></label>
            <input type="text" name="phone_num" id="phone_num" placeholder="Enter your phone number" 
                   value="<?php echo htmlspecialchars($form_data['phone_num'] ?? ''); ?>" required>
            <div class="error-message" id="phone_num_error">
                <?php echo isset($validation_errors['phone_num']) ? $validation_errors['phone_num'] : ''; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Username <span class="required">*</span></label>
            <input type="text" name="username" id="username" placeholder="Choose a username" 
                   value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required>
            <div class="error-message" id="username_error">
                <?php echo isset($validation_errors['username']) ? $validation_errors['username'] : ''; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Password <span class="required">*</span></label>
            <input type="password" id="password" name="password" placeholder="Create a password" required>
            <ul class="validation-list" id="password-checklist">
                <li id="length-check" class="invalid">✗ At least 8 characters</li>
                <li id="upper-check" class="invalid">✗ One uppercase letter</li>
                <li id="lower-check" class="invalid">✗ One lowercase letter</li>
                <li id="num-check" class="invalid">✗ One number</li>
                <li id="special-check" class="invalid">✗ One special character</li>
            </ul>
            <div class="error-message" id="password_error">
                <?php 
                if (isset($validation_errors['password']) && is_string($validation_errors['password'])) {
                    echo $validation_errors['password'];
                }
                ?>
            </div>
        </div>

        <div class="form-group">
            <label>Confirm Password <span class="required">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            <div id="match-status" class="match-hint"></div>
            <div class="error-message" id="confirm_password_error">
                <?php echo isset($validation_errors['confirm_password']) ? $validation_errors['confirm_password'] : ''; ?>
            </div>
        </div>

        <button type="submit" name="register" id="submitBtn">Sign Up</button>

    </form>

    <?php if (!empty($error)): ?>
        <p class="message error"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p class="message success"><?php echo $success; ?></p>
        <div class="redirect-progress">
            <p style="font-size: 13px; color: var(--text-muted);">Redirecting to login page...</p>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirm_password");
    const form = document.getElementById("registerForm");
    const submitBtn = document.getElementById("submitBtn");

    // Get all inputs for validation
    const fullName = document.getElementById("full_name");
    const email = document.getElementById("email");
    const phoneNum = document.getElementById("phone_num");
    const username = document.getElementById("username");

    // Error elements
    const fullNameError = document.getElementById("full_name_error");
    const emailError = document.getElementById("email_error");
    const phoneNumError = document.getElementById("phone_num_error");
    const usernameError = document.getElementById("username_error");
    const passwordError = document.getElementById("password_error");
    const confirmPasswordError = document.getElementById("confirm_password_error");

    const lengthCheck = document.getElementById("length-check");
    const upperCheck = document.getElementById("upper-check");
    const lowerCheck = document.getElementById("lower-check");
    const numCheck = document.getElementById("num-check");
    const specialCheck = document.getElementById("special-check");
    const matchStatus = document.getElementById("match-status");

    // Helper function to show/hide error
    function showError(input, errorElement, message) {
        if (message) {
            input.classList.add("error");
            input.classList.remove("success");
            errorElement.textContent = message;
            errorElement.classList.add("show");
        } else {
            input.classList.remove("error");
            input.classList.add("success");
            errorElement.classList.remove("show");
        }
    }

    // Validate Full Name
    function validateFullName() {
        const value = fullName.value.trim();
        let error = "";
        
        if (value.length === 0) {
            error = "Full name is required";
        } else if (value.length < 2) {
            error = "Full name must be at least 2 characters";
        } else if (value.length > 50) {
            error = "Full name must be less than 50 characters";
        } else if (!/^[a-zA-Z\s\-']+$/.test(value)) {
            error = "Full name can only contain letters, spaces, hyphens, and apostrophes";
        }
        
        showError(fullName, fullNameError, error);
        return !error;
    }

    // Validate Email
    function validateEmail() {
        const value = email.value.trim();
        let error = "";
        
        if (value.length === 0) {
            error = "Email is required";
        } else if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value)) {
            error = "Please enter a valid email address";
        }
        
        showError(email, emailError, error);
        return !error;
    }

    // Validate Phone Number
    function validatePhone() {
        const value = phoneNum.value.trim();
        let error = "";
        
        if (value.length === 0) {
            error = "Phone number is required";
        } else {
            const clean = value.replace(/[\s\-\(\)]/g, '');
            if (!/^(\+?6?01[0-9]{8,9}|0[0-9]{9,10})$/.test(clean)) {
                error = "Please enter a valid phone number (e.g., 0123456789 or +60123456789)";
            }
        }
        
        showError(phoneNum, phoneNumError, error);
        return !error;
    }

    // Validate Username
    function validateUsername() {
        const value = username.value.trim();
        let error = "";
        
        if (value.length === 0) {
            error = "Username is required";
        } else if (value.length < 3) {
            error = "Username must be at least 3 characters";
        } else if (value.length > 20) {
            error = "Username must be less than 20 characters";
        } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
            error = "Username can only contain letters, numbers, and underscores";
        }
        
        showError(username, usernameError, error);
        return !error;
    }

    function validatePassword() {
        const val = passwordInput.value;
        let hasError = false;

        // 1. Length rule
        if (val.length >= 8) {
            lengthCheck.innerHTML = "✅ At least 8 characters";
            lengthCheck.className = "valid";
        } else {
            lengthCheck.innerHTML = "✗ At least 8 characters";
            lengthCheck.className = "invalid";
            hasError = true;
        }

        // 2. Uppercase rule
        if (/[A-Z]/.test(val)) {
            upperCheck.innerHTML = "✅ One uppercase letter";
            upperCheck.className = "valid";
        } else {
            upperCheck.innerHTML = "✗ One uppercase letter";
            upperCheck.className = "invalid";
            hasError = true;
        }

        // 3. Lowercase rule
        if (/[a-z]/.test(val)) {
            lowerCheck.innerHTML = "✅ One lowercase letter";
            lowerCheck.className = "valid";
        } else {
            lowerCheck.innerHTML = "✗ One lowercase letter";
            lowerCheck.className = "invalid";
            hasError = true;
        }

        // 4. Number rule
        if (/[0-9]/.test(val)) {
            numCheck.innerHTML = "✅ One number";
            numCheck.className = "valid";
        } else {
            numCheck.innerHTML = "✗ One number";
            numCheck.className = "invalid";
            hasError = true;
        }

        // 5. Special character rule
        if (/[`!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/.test(val)) {
            specialCheck.innerHTML = "✅ One special character";
            specialCheck.className = "valid";
        } else {
            specialCheck.innerHTML = "✗ One special character";
            specialCheck.className = "invalid";
            hasError = true;
        }

        // Show/hide password error
        if (hasError && val.length > 0) {
            passwordError.textContent = "Password does not meet all requirements";
            passwordError.classList.add("show");
        } else {
            passwordError.classList.remove("show");
        }

        validateConfirmPassword();
        return !hasError;
    }

    function validateConfirmPassword() {
        const passVal = passwordInput.value;
        const confirmVal = confirmInput.value;

        if (confirmVal === "") {
            matchStatus.innerHTML = "";
            confirmPasswordError.classList.remove("show");
            confirmInput.classList.remove("error");
            confirmInput.classList.remove("success");
            return true;
        }

        if (passVal === confirmVal) {
            matchStatus.innerHTML = "✅ Passwords match";
            matchStatus.style.color = "#10b981";
            confirmInput.classList.remove("error");
            confirmInput.classList.add("success");
            confirmPasswordError.classList.remove("show");
            return true;
        } else {
            matchStatus.innerHTML = "✗ Passwords do not match";
            matchStatus.style.color = "#ef4444";
            confirmInput.classList.add("error");
            confirmInput.classList.remove("success");
            confirmPasswordError.textContent = "Passwords do not match";
            confirmPasswordError.classList.add("show");
            return false;
        }
    }

    // Form validation on submit
    form.addEventListener('submit', function(e) {
        const isFullNameValid = validateFullName();
        const isEmailValid = validateEmail();
        const isPhoneValid = validatePhone();
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        const isConfirmValid = validateConfirmPassword();

        if (!(isFullNameValid && isEmailValid && isPhoneValid && 
              isUsernameValid && isPasswordValid && isConfirmValid)) {
            e.preventDefault();
            
            // Scroll to first error
            const firstError = document.querySelector('.error-message.show');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const input = firstError.closest('.form-group').querySelector('input');
                if (input) input.focus();
            }
        }
    });

    // Real-time validation
    fullName.addEventListener('input', validateFullName);
    fullName.addEventListener('blur', validateFullName);
    
    email.addEventListener('input', validateEmail);
    email.addEventListener('blur', validateEmail);
    
    phoneNum.addEventListener('input', validatePhone);
    phoneNum.addEventListener('blur', validatePhone);
    
    username.addEventListener('input', validateUsername);
    username.addEventListener('blur', validateUsername);
    
    passwordInput.addEventListener('input', function() {
        validatePassword();
        if (confirmInput.value) {
            validateConfirmPassword();
        }
    });
    
    confirmInput.addEventListener('input', validateConfirmPassword);

    // Initial validation for fields that already have values
    if (fullName.value) validateFullName();
    if (email.value) validateEmail();
    if (phoneNum.value) validatePhone();
    if (username.value) validateUsername();
    if (passwordInput.value) validatePassword();
    if (confirmInput.value) validateConfirmPassword();
});
</script>

</body>
</html>
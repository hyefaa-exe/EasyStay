<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ulugarden";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = "";
$error_message = "";

$created_at = date('Y-m-d H:i:s');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // print_r($_POST);exit;

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate inputs
    if ( empty($fullname) || empty($email) || empty($phone_no) || empty($username) || empty($password) ) {
        $error_message = "Please fill in all fields with valid values.";
    } else {
        // Use prepared statement for security
        $sql = "INSERT INTO users (full_name, email, phone, password, created_at, username) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $fullname, $email, $phone_no, $password, $created_at, $username);

        if ($stmt->execute()) {
            $success_message = "User added successfully!";
            // Clear form data
            $_POST = array();
            // Redirect after 2 seconds
            header("refresh:2;url=view_users.php");
        } else {
            $error_message = "Error adding package: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Package - UluGarden Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

       

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            color: #333333;
            min-height: 100vh;
            line-height: 1.6;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,77,0,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            z-index: -1;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            border-bottom: 2px solid #FF4D00;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header-content {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo i {
            font-size: 2.5rem;
            color: #FF4D00;
        }

        .logo h1 {
            color: #333333;
            font-size: 2rem;
            font-weight: 300;
            letter-spacing: 1px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        .container {
            width: 90%;
            max-width: 700px;
            margin: 0 auto 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 0 30px rgba(255, 77, 0, 0.1);
            border: 1px solid rgba(255, 77, 0, 0.2);
        }

        .container-header {
            background: linear-gradient(135deg, #FF4D00, #ff6b2d);
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .container-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="10" height="10" patternUnits="userSpaceOnUse"><circle cx="5" cy="5" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            opacity: 0.3;
        }

        .container-header h2 {
            color: #ffffff;
            font-size: 2.2rem;
            font-weight: 300;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .container-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .form-section {
            background: rgba(248, 248, 248, 0.5);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid rgba(255, 77, 0, 0.1);
        }

        .form-section h3 {
            color: #333333;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            color: #333333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
            color: #333333;
            font-family: inherit;
            resize: vertical;
        }

        .form-control:focus {
            outline: none;
            border-color: #FF4D00;
            box-shadow: 0 0 20px rgba(255, 77, 0, 0.1);
            transform: translateY(-2px);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .input-group {
            position: relative;
        }

        .input-group .currency {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #FF4D00;
            font-weight: bold;
            pointer-events: none;
        }

        .input-group .form-control {
            padding-left: 45px;
        }

        .form-help {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 180px;
            justify-content: center;
        }

        .btn-add {
            background: linear-gradient(135deg, #FF4D00, #ff6b2d);
            color: white;
            box-shadow: 0 6px 20px rgba(255, 77, 0, 0.3);
        }

        .btn-add:hover:not(.loading) {
            background: linear-gradient(135deg, #ff6b2d, #FF4D00);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 77, 0, 0.4);
        }

        .btn-cancel {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        .btn-cancel:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }

        .btn-reset {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #333;
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.3);
        }

        .btn-reset:hover {
            background: linear-gradient(135deg, #e0a800, #ffc107);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
        }

        /* Loading state */
        .loading {
            pointer-events: none;
            opacity: 0.7;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form validation styles */
        .form-control.valid {
            border-color: #28a745;
        }

        .form-control.invalid {
            border-color: #dc3545;
        }

        .validation-message {
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .validation-message.error {
            color: #dc3545;
        }

        .validation-message.success {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .logo h1 {
                font-size: 1.5rem;
            }

            .container-header h2 {
                font-size: 1.8rem;
            }

            .content {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-home"></i>
                <h1>UluGarden Admin</h1>
            </div>
            <div class="nav-buttons">
                <a class="back-btn" href="view_users.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Users
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="container-header">
            <h2><i class="fas fa-plus-circle"></i> Add New User</h2>
            <p>Create a new user</p>
        </div>

        <div class="content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <span style="margin-left: auto; font-size: 0.9rem;">Redirecting in 2 seconds...</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="form-section">
                <h3>
                    <i class="fas fa-edit"></i>
                    User Information
                </h3>
                
                <form method="POST" action="" id="addUserForm">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="fullname">
                                Fullname <span style="color: #dc3545;">*</span>
                            </label>
                            <input type="text" 
                                   id="fullname" 
                                   name="fullname" 
                                   class="form-control" 
                                   value="<?php echo isset($_POST['package_name']) ? htmlspecialchars($_POST['package_name']) : ''; ?>" 
                                   placeholder="Enter fullname"
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="email">
                                Email <span style="color: #dc3545;">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?php echo isset($_POST['package_name']) ? htmlspecialchars($_POST['package_name']) : ''; ?>" 
                                   placeholder="Enter email"
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="phone_no">
                                Phone Number <span style="color: #dc3545;">*</span>
                            </label>
                            <input type="text" 
                                   id="phone_no" 
                                   name="phone_no" 
                                   class="form-control" 
                                   value="<?php echo isset($_POST['package_name']) ? htmlspecialchars($_POST['package_name']) : ''; ?>" 
                                   placeholder="Enter phone number"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="username">
                                Username <span style="color: #dc3545;">*</span>
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   value="<?php echo isset($_POST['availability']) ? $_POST['availability'] : ''; ?>" 
                                   placeholder="Username"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                Password <span style="color: #dc3545;">*</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   value="<?php echo isset($_POST['availability']) ? $_POST['availability'] : ''; ?>" 
                                   placeholder="Password"
                                   required>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-add" id="addBtn">
                            <i class="fas fa-plus"></i>
                            Add User
                        </button>
                        <button type="button" class="btn btn-reset" onclick="resetForm()">
                            <i class="fas fa-undo"></i>
                            Reset Form
                        </button>
                        <a href="view_users.php" class="btn btn-cancel">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add loading state to add button
        document.getElementById('addUserForm').addEventListener('submit', function() {
            const btn = document.getElementById('addBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Package...';
        });

        // Auto-hide alerts after 5 seconds (except redirect message)
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (!alert.textContent.includes('Redirecting')) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }
            });
        }, 5000);

        // Form validation feedback
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                validateField(this);
            });

            input.addEventListener('blur', function() {
                validateField(this);
            });
        });

        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            let message = '';

            // Remove existing validation messages
            const existingMessage = field.parentNode.querySelector('.validation-message');
            if (existingMessage) {
                existingMessage.remove();
            }

            // Validate based on field type
            switch(field.name) {
                case 'password':
                    isValid = value.length >= 6;
                    message = isValid ? '' : 'Password must be at least 6 characters long';
                    break;
                // case 'fullname':
                //     isValid = parseFloat(value) > 0;
                //     message = isValid ? '' : 'Price must be greater than 0';
                //     break;
                // case 'availability':
                //     isValid = parseInt(value) >= 0;
                //     message = isValid ? '' : 'Availability cannot be negative';
                //     break;
                // case 'description':
                //     isValid = value.length >= 10;
                //     message = isValid ? '' : 'Description must be at least 10 characters long';
                //     break;
            }

            // Apply validation styling
            if (value !== '') {
                field.classList.remove('valid', 'invalid');
                field.classList.add(isValid ? 'valid' : 'invalid');

                // Add validation message
                if (message) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `validation-message ${isValid ? 'success' : 'error'}`;
                    messageDiv.innerHTML = `<i class="fas fa-${isValid ? 'check' : 'exclamation'}-circle"></i> ${message}`;
                    field.parentNode.appendChild(messageDiv);
                }
            } else {
                field.classList.remove('valid', 'invalid');
            }
        }

        // Reset form function
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                document.getElementById('addUserForm').reset();
                document.querySelectorAll('.form-control').forEach(field => {
                    field.classList.remove('valid', 'invalid');
                });
                document.querySelectorAll('.validation-message').forEach(msg => {
                    msg.remove();
                });
            }
        }

        // Price input formatting
        document.getElementById('price').addEventListener('blur', function() {
            if (this.value && !isNaN(this.value)) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });

        // Character counter for description
        const descriptionField = document.getElementById('description');
        const maxLength = 1000;
        
        descriptionField.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            let counter = this.parentNode.querySelector('.char-counter');
            
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'char-counter';
                counter.style.cssText = 'font-size: 0.8rem; color: #666; text-align: right; margin-top: 5px;';
                this.parentNode.appendChild(counter);
            }
            
            counter.textContent = `${this.value.length}/${maxLength} characters`;
            counter.style.color = remaining < 100 ? '#dc3545' : '#666';
        });
    </script>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';

$success_message = "";
$error_message = "";

$created_at = date('Y-m-d H:i:s');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate inputs
    if ( empty($fullname) || empty($email) || empty($phone_no) || empty($username) || empty($password) ) {
        $error_message = "Please fill in all fields with valid values.";
    } else {
        // Securely hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Use prepared statement for security
        $sql = "INSERT INTO users (full_name, email, phone, password, created_at, username) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $fullname, $email, $phone_no, $hashed_password, $created_at, $username);

        if ($stmt->execute()) {
            $success_message = "Customer added successfully!";
            // Clear form data
            $_POST = array();
            // Redirect after 2 seconds
            header("refresh:2;url=view_users.php");
        } else {
            $error_message = "Error adding customer: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Customer - EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    
    <style>
        .container-header {
            background: linear-gradient(135deg, var(--easy-gold), var(--easy-gold-dark));
            padding: 30px;
            text-align: center;
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(197, 168, 128, 0.15);
        }

        .container-header h2 {
            color: #ffffff;
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .container-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 500;
        }

        .content {
            padding: 40px;
            background: #ffffff;
            border-radius: 0 0 20px 20px;
            box-shadow: var(--card-shadow);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
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
            background: #fafafa;
            border-radius: 15px;
            padding: 30px;
            border: 1px solid #f0f0f0;
        }

        .form-section h3 {
            color: var(--easy-charcoal);
            font-size: 1.25rem;
            font-weight: 700;
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

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            color: var(--easy-charcoal);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
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
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            justify-content: center;
        }

        .btn-add {
            background: var(--easy-gold);
            color: white;
        }

        .btn-add:hover:not(.loading) {
            background: var(--easy-gold-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
        }

        .btn-reset {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-reset:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
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
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-area">
            <a href="admin_dashboard.php" style="text-decoration: none;">
                <h1><span class="logo-ulu">Easy</span><span class="logo-garden">Stay</span></h1>
            </a>
            <span class="brand-sub">Management Portal</span>
        </div>
        <div class="nav-actions">
            <a class="nav-btn btn-profile" href="view_users.php">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </header>

    <div class="container" style="max-width: 800px;">
        <div class="container-header">
            <h2><i class="fas fa-user-plus"></i> Add New Customer</h2>
            <p>Register a new customer account in the system</p>
        </div>

        <div class="content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
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
                    Customer Information
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
                                   value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" 
                                   placeholder="Enter full name"
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
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   placeholder="Enter email address"
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
                                   value="<?php echo isset($_POST['phone_no']) ? htmlspecialchars($_POST['phone_no']) : ''; ?>" 
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
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
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
                                   value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>" 
                                   placeholder="Password"
                                   required>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-add" id="addBtn">
                            <i class="fas fa-plus"></i>
                            Add Customer
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
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Customer...';
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
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

            // Validate password length
            if (field.name === 'password') {
                isValid = value.length >= 6;
                message = isValid ? '' : 'Password must be at least 6 characters long';
            }

            // Apply validation styling
            if (value !== '') {
                field.classList.remove('valid', 'invalid');
                field.classList.add(isValid ? 'valid' : 'invalid');

                // Add validation message
                if (message) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'validation-message error';
                    messageDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
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
    </script>
</body>
</html>
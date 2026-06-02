<?php
// Database connection
require 'db_connect.php';

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginform.html");
    exit();
}

// Validate User ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['user_id']) ? intval($_POST['user_id']) : 0);

if ($user_id <= 0) {
    die("Invalid User ID.");
}

$success_message = "";
$error_message = "";

// Fetch user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);

    if (empty($fullname) || empty($email) || empty($phone_no)) {
        $error_message = "All fields are required.";
    } else {
        $update = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $update->bind_param("sssi", $fullname, $email, $phone_no, $user_id);

        if ($update->execute()) {
            $success_message = "User information updated successfully!";
            // Update local data for immediate display
            $user['full_name'] = $fullname;
            $user['email'] = $email;
            $user['phone'] = $phone_no;
            header("refresh:2;url=view_users.php");
        } else {
            $error_message = "Error updating user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | UluGarden Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ulu-orange: #FF7F32;
            --garden-black: #1A1A1A;
            --soft-orange-bg: #FFF5E9;
            --white: #ffffff;
            --text-gray: #8E8E8E;
            --danger: #e74c3c;
            --success: #2ecc71;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--soft-orange-bg); color: var(--garden-black); }

        /* --- HEADER --- */
        .header {
            background: var(--white);
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(255, 127, 50, 0.08);
            position: sticky; top: 0; z-index: 1000;
        }
        .logo-area h1 { font-size: 1.7rem; font-weight: 800; }
        .logo-ulu { color: var(--ulu-orange); }
        .logo-garden { color: var(--garden-black); }
        .brand-sub { font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; display: block; margin-top: -3px; }

        .nav-btn {
            text-decoration: none; padding: 10px 22px; border-radius: 12px;
            font-weight: 600; font-size: 0.85rem; transition: 0.3s;
            display: flex; align-items: center; gap: 8px;
            border: 1.5px solid var(--ulu-orange); color: var(--ulu-orange);
        }
        .nav-btn:hover { background: var(--ulu-orange); color: white; transform: translateY(-2px); }

        /* --- CONTAINER --- */
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        
        .card {
            background: var(--white);
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            overflow: hidden;
            border: 1px solid rgba(255, 127, 50, 0.1);
        }

        .card-header {
            background: var(--garden-black);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-body { padding: 40px; }

        /* --- INFO GRID --- */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: #fdfdfd;
            padding: 15px 20px;
            border-radius: 15px;
            border: 1px solid #f0f0f0;
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-gray);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .info-value { font-weight: 700; font-size: 1.1rem; color: var(--garden-black); }
        .highlight { color: var(--ulu-orange); }

        /* --- FORM --- */
        .form-section {
            background: #fafafa;
            padding: 25px;
            border-radius: 20px;
            margin-top: 10px;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 700; margin-bottom: 10px; color: var(--garden-black); font-size: 0.9rem; }

        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute; left: 15px; top: 50%;
            transform: translateY(-50%); color: var(--text-gray);
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border-radius: 12px;
            border: 2px solid #eee;
            font-size: 1rem;
            font-weight: 600;
            outline: none;
            transition: 0.3s;
        }
        .form-control:focus { border-color: var(--ulu-orange); background: #fff; }

        .btn-submit {
            width: 100%;
            background: var(--ulu-orange);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }

        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .header { padding: 15px 20px; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo-area">
            <a href="admin_dashboard.php" style="text-decoration: none;">
                <h1><span class="logo-ulu">Ulu</span><span class="logo-garden">Garden</span></h1>
            </a>
            <span class="brand-sub">Management Portal</span>
        </div>
        <div class="nav-actions">
            <a href="view_users.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </header>

    <main class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 style="font-weight: 800; letter-spacing: 1px;">USER SETTINGS</h2>
                <p style="opacity: 0.7; font-size: 0.9rem;">Account ID: #<?php echo $user['user_id']; ?></p>
            </div>

            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <p class="info-label">Current Full Name</p>
                        <p class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Current Email</p>
                        <p class="info-value highlight"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <div class="form-section">
                    <form method="POST" id="editForm">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" name="fullname" id="fullname" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="phone_no" id="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="update_user" class="btn-submit" id="submitBtn">
                            <i class="fas fa-save"></i> UPDATE USER DATA
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Loading effect on submit
        document.getElementById('editForm').onsubmit = function() {
            const btn = document.getElementById('submitBtn');
            btn.style.opacity = '0.7';
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> UPDATING...';
        };
    </script>
</body>
</html>
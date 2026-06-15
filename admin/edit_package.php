<?php
session_start();
require 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get package ID
$package_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['package_id']) ? intval($_POST['package_id']) : 0);

$success_message = "";
$error_message = "";

// Fetch existing package data
$sql = "SELECT * FROM packages WHERE package_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();

if (!$package) {
    die("Package not found.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = trim($_POST['package_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $availability = intval($_POST['availability']);
    $image_name = $package['image']; // Default to old image

    // Validate inputs
    if (empty($package_name) || empty($description) || $price <= 0 || $availability < 0) {
        $error_message = "Please fill in all fields with valid values.";
    } else {
        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image_name = time() . "_" . uniqid() . "." . $file_ext;
            $image_path = $upload_dir . $new_image_name;

            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error_message = "Only JPG and PNG images are allowed.";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error_message = "Image must be smaller than 5MB.";
            } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $image_name = $new_image_name;
            }
        }

        if (empty($error_message)) {
            $update_sql = "UPDATE packages SET package_name = ?, description = ?, price = ?, availability = ?, image = ? WHERE package_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssdisi", $package_name, $description, $price, $availability, $image_name, $package_id);

            if ($update_stmt->execute()) {
                require_once 'admin_logger.php';
                logAdminAction($conn, $_SESSION['admin_id'], 'UPDATE_PACKAGE', "Updated package '$package_name'", $package_id, 'package');
                $success_message = "Package updated successfully!";
                header("refresh:2;url=manage_packages.php");
            } else {
                $error_message = "Error updating package: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Package | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
/* --- HEADER (IDENTIKAL DENGAN ADD_PACKAGE) --- */
        

        

        
        
        
        
        

        
        

        /* --- CONTAINER --- */
        

        .card {
            background: var(--white);
            border-radius: 35px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(0,0,0,0.04);
        }

        .card-header {
            background: var(--garden-black);
            color: white;
            padding: 22px;
            text-align: center;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .card-

        /* --- FORM STYLING --- */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-wid

        .form-group label {
            display: block;
            font-weight: 800;
            font-size: 0.7rem;
            color: var(--garden-black);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border-radius: 12px;
            border: 1.5px solid #F0F0F0;
            background: #F9FAFB;
            font-size: 0.95rem;
            transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--ulu-orange); background: white; }

        textarea.form-control { min-height: 120px; resize: none; }

        /* --- IMAGE SECTION --- */
        .image-preview-area {
            border: 2px dashed #E5E7EB;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            background: #F9FAFB;
            margin-top: 5px;
        }
        
        #preview-img {
            max-width: 100%;
            max-height: 250px;
            border-radius: 12px;
            margin-top: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            object-fit: cover;
        }

        /* --- BUTTONS --- */
        .button-group { display: flex; gap: 15px; margin-top: 25px; }
        
        .btn {
            flex: 1;
            padding: 18px;
            border-radius: 15px;
            border: none;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: 0.3s;
            text-transform: uppercase;
            text-decoration: none;
        }

        .btn-cancel { background: #E9ECEF; color: #495057; }
        .btn-cancel:hover { background: #DEE2E6; }

        .btn-save { background: var(--ulu-orange); color: white; }
        .btn-save:hover { 
            background: #B3966F; 
            transform: translateY(-3px); 
            box-shadow: 0 10px 20px rgba(255,127,50,0.25); 
        }

        /* --- ALERTS --- */
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; text-align: center; }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }

        @media (max-width: 768px) {
            
            .form-grid { grid-template-columns: 1fr; }
            .full-wid
        }
    </style>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

    <header class="header">
        <a href="admin_dashboard.php" class="logo-box">
            <div class="logo-text">
                <span class="logo-ulu">Easy</span><span class="logo-garden">Stay</span>
            </div>
            <div class="portal-sub">Management Portal</div>
        </a>

        <a href="manage_packages.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
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
                <i class="fas fa-edit"></i> Edit Homestay Package
            </div>

            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><i class="fas fa-tag"></i> Package Name</label>
                            <input type="text" name="package_name" class="form-control" value="<?php echo htmlspecialchars($package['package_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-money-bill"></i> Price (RM)</label>
                            <input type="number" name="price" step="0.01" class="form-control" value="<?php echo $package['price']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-boxes"></i> Availability (Units)</label>
                            <input type="number" name="availability" class="form-control" value="<?php echo $package['availability']; ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" class="form-control" required><?php echo htmlspecialchars($package['description']); ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-image"></i> Package Image</label>
                            <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                            
                            <div class="image-preview-area">
                                <p style="font-size: 0.7rem; color: #888; font-weight: 700; margin-bottom: 10px; text-transform: uppercase;">Current / New Preview:</p>
                                <img id="preview-img" src="uploads/<?php echo $package['image']; ?>" alt="Package Image">
                                <p style="font-size: 0.75rem; color: #BBB; margin-top: 10px;">PNG, JPG or JPEG (Max 5MB)</p>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <a href="manage_packages.php" class="btn btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Real-time Image Preview
        document.getElementById('imageInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview-img');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>

</body>
</html>
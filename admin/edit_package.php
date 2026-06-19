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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .edit-layout {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 32px;
        }
        
        .form-row-double {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .image-upload-card {
            border: 2px dashed rgba(197, 168, 128, 0.25);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            background: #FAFAFA;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            height: calc(100% - 28px);
            transition: all 0.3s ease;
        }
        
        .image-upload-card:hover {
            border-color: var(--gold);
            background: rgba(197, 168, 128, 0.02);
        }
        
        .image-preview-container {
            width: 100%;
            height: 220px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            background: #FFF;
            border: 1px solid var(--slate-100);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #preview-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .image-upload-card:hover #preview-img {
            transform: scale(1.02);
        }
        
        .upload-control-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        
        .custom-file-upload {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--gold-light);
            color: var(--gold-dark);
            border: 1.5px solid rgba(197, 168, 128, 0.3);
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .custom-file-upload:hover {
            background: var(--gold);
            color: var(--white);
            border-color: var(--gold);
            box-shadow: 0 4px 10px rgba(197, 168, 128, 0.2);
        }
        
        .upload-hint {
            font-size: 11px;
            color: var(--slate-400);
            font-weight: 500;
            margin: 0;
        }
        
        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            border-top: 1px solid var(--slate-100);
            padding-top: 24px;
        }
        
        .form-label i {
            color: var(--gold);
            margin-right: 6px;
            font-size: 0.85rem;
        }
        
        @media (max-width: 992px) {
            .edit-layout {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .image-upload-card {
                height: auto;
                min-height: auto;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Edit Package</div>
            <div class="topbar-breadcrumb">Modify properties and settings for Package #<?= $package_id ?></div>
        </div>
        <div class="topbar-right">
            <a href="manage_packages.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="admin-content" style="max-width: 960px;">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-box-open"></i> Package Details</h3>
            </div>
            <div class="card-body" style="padding: 32px;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                    
                    <div class="edit-layout">
                        <!-- Left Column: Form Fields -->
                        <div class="edit-fields-column">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-tag"></i> Package Name</label>
                                <input type="text" name="package_name" class="form-control" value="<?php echo htmlspecialchars($package['package_name']); ?>" required>
                            </div>

                            <div class="form-row-double">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-money-bill"></i> Price (RM)</label>
                                    <input type="number" name="price" step="0.01" class="form-control" value="<?php echo $package['price']; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-boxes"></i> Availability (Units)</label>
                                    <input type="number" name="availability" class="form-control" value="<?php echo $package['availability']; ?>" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                                <textarea name="description" class="form-control" required style="min-height: 150px; resize: none;"><?php echo htmlspecialchars($package['description']); ?></textarea>
                            </div>
                        </div>

                        <!-- Right Column: Package Image Upload & Preview -->
                        <div class="edit-image-column">
                            <label class="form-label"><i class="fas fa-image"></i> Package Image</label>
                            
                            <div class="image-upload-card">
                                <div class="image-preview-container">
                                    <img id="preview-img" src="uploads/<?php echo $package['image']; ?>" alt="Package Image">
                                </div>
                                <div class="upload-control-zone">
                                    <label for="imageInput" class="custom-file-upload">
                                        <i class="fas fa-cloud-upload-alt"></i> Choose New Image
                                    </label>
                                    <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;">
                                    <p class="upload-hint">PNG, JPG or JPEG (Max 5MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <a href="manage_packages.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
<?php
session_start();
require 'db_connect.php';

// Semak jika admin telah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = trim($_POST['package_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $availability = intval($_POST['availability']);
    $status = "ACTIVE";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . "_" . uniqid() . "." . $file_ext;
        $image_path = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $sql = "INSERT INTO packages (package_name, description, price, availability, image, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiss", $package_name, $description, $price, $availability, $image_name, $status);

            if ($stmt->execute()) {
                $success_message = "Package added successfully!";
                header("refresh:2;url=manage_packages.php");
            } else {
                $error_message = "Error: " . $conn->error;
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
    <title>Add New Package | EasyStay</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
/* --- HEADER SELARI DENGAN MANAGE_PACKAGE.PHP --- */
        

        

        
        
        
        
        

        
        

        /* --- FORM CONTAINER --- */
        

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

        /* --- UPLOAD BOX --- */
        .upload-area {
            border: 2px dashed #E5E7EB;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            background: #F9FAFB;
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
        }

        .btn-reset { background: #E9ECEF; color: #495057; }
        .btn-publish { background: var(--ulu-orange); color: white; }
        .btn-publish:hover { background: #B3966F; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,127,50,0.25); }

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
            <div style="background: #D1FAE5; color: #065F46; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; text-align: center;">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Add New Package
            </div>

            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><i class="fas fa-tag"></i> Package Name</label>
                            <input type="text" name="package_name" class="form-control" placeholder="e.g. Family Suite Garden View" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-money-bill"></i> Price (RM)</label>
                            <input type="number" name="price" step="0.01" class="form-control" placeholder="0.00" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-boxes"></i> Availability (Units)</label>
                            <input type="number" name="availability" class="form-control" placeholder="Units available" required>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Describe the amenities, room types, and special offers..." required></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-image"></i> Package Photo</label>
                            <div class="upload-area">
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                                <div style="font-size: 0.75rem; color: #BBB; margin-top: 10px;">PNG, JPG or JPEG (Max 5MB)</div>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="reset" class="btn btn-reset">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-publish">
                            <i class="fas fa-paper-plane"></i> Publish Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
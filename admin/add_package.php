<?php
session_start();
require 'db_connect.php';

// Semak jika admin telah login
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginform.html");
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
    <title>Add New Package | UluGarden</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ulu-orange: #FF7F32;
            --garden-black: #1A1A1A;
            --bg-color: #FFF5E9;
            --white: #ffffff;
            --text-gray: #8E8E8E;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { background-color: var(--bg-color); min-height: 100vh; }

        /* --- HEADER SELARI DENGAN MANAGE_PACKAGE.PHP --- */
        .header {
            background: var(--white);
            padding: 15px 80px; /* Samakan padding kiri/kanan dengan dashboard */
            display: flex;
            justify-content: space-between;
            align-items: center; /* Memastikan logo & butang selari secara vertikal */
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            height: 85px; /* Tetapkan height supaya konsisten antara page */
            width: 100%;
        }

        .logo-box {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            line-height: 1.1;
        }

        .logo-text { font-size: 1.8rem; font-weight: 800; }
        .logo-ulu { color: var(--ulu-orange); }
        .logo-garden { color: var(--garden-black); }
        
        .portal-sub { 
            font-size: 0.75rem; 
            font-weight: 700; 
            color: var(--text-gray); 
            letter-spacing: 2px; 
            text-transform: uppercase;
        }

        .back-btn {
            text-decoration: none;
            color: var(--ulu-orange);
            border: 1.5px solid var(--ulu-orange);
            padding: 10px 22px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }
        .back-btn:hover { background: var(--ulu-orange); color: white; }

        /* --- FORM CONTAINER --- */
        .container { max-width: 750px; margin: 40px auto; padding: 0 20px 60px 20px; }

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

        .card-body { padding: 40px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }

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
        .btn-publish:hover { background: #E66A20; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,127,50,0.25); }

        @media (max-width: 768px) {
            .header { padding: 15px 30px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="admin_dashboard.php" class="logo-box">
            <div class="logo-text">
                <span class="logo-ulu">Ulu</span><span class="logo-garden">Garden</span>
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
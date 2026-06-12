<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
require 'db_connect.php';

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $image = $_FILES['image'];
    $upload_dir = "uploads/";
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $extension = strtolower(pathinfo(basename($image['name']), PATHINFO_EXTENSION));
    $image_name = time() . "_img_gallery." . $extension;
    $image_path = $upload_dir . $image_name;
    $original_filename = basename($image['name']);

    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($image['type'], $allowed_types)) {
        $error_message = "Only JPG and PNG images are allowed.";
    } elseif ($image['size'] > 5 * 1024 * 1024) {
        $error_message = "Image must be smaller than 5MB.";
    }

    if (empty($error_message)) {
        if (move_uploaded_file($image['tmp_name'], $image_path)) {
            $sql = "INSERT INTO gallery (filename, original_filename, create_dt) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $image_name, $original_filename);

            if ($stmt->execute()) {
                $success_message = "Image added successfully!";
                header("refresh:2;url=view_gallery.php");
            } else {
                $error_message = "Error adding to gallery: " . $stmt->error;
            }
        } else {
            $error_message = "Failed to upload image.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Photo | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
/* --- HEADER IDENTIKAL --- */
        

        
        
        
        
        

        
        

        /* --- CONTAINER --- */
        

        .card {
            background: var(--white);
            border-radius: 35px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(0,0,0,0.04);
            border: 1px solid rgba(197, 168, 128, 0.1);
        }

        .card-header {
            background: var(--garden-black);
            color: white; padding: 25px; text-align: center; font-size: 1.3rem; font-weight: 700;
            display: flex; justify-content: center; align-items: center; gap: 12px;
        }

        .card-

        /* --- ALERT --- */
        .alert {
            padding: 15px 20px; border-radius: 15px; margin-bottom: 25px;
            font-weight: 700; display: flex; align-items: center; gap: 12px;
            animation: slideIn 0.4s ease forwards;
        }
        .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

        /* --- FORM --- */
        .form-section {
            background: #F9FAFB; padding: 30px; border-radius: 20px;
            border: 1px solid #F0F0F0; margin-bottom: 20px;
        }

        .form-group label {
            display: block; font-weight: 800; font-size: 0.75rem; color: var(--garden-black);
            margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;
        }

        /* --- FILE INPUT CUSTOM --- */
        .file-upload-wrapper {
            position: relative; width: 100%; height: 180px;
            border: 2px dashed #D1D5DB; border-radius: 15px;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; background: white; transition: 0.3s;
            cursor: pointer; overflow: hidden;
        }
        .file-upload-wrapper:hover { border-color: var(--ulu-orange); background: #FFF9F5; }
        
        .file-upload-wrapper input[type="file"] {
            position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;
        }

        .upload-icon { font-size: 2.5rem; color: var(--ulu-orange); margin-bottom: 10px; }
        .upload-text { font-size: 0.9rem; color: var(--text-gray); font-weight: 600; }

        /* --- PREVIEW --- */
        #preview-container {
            margin-top: 20px; display: none; text-align: center;
        }
        #preview-img {
            width: 100%; max-height: 300px; object-fit: cover;
            border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 3px solid white;
        }

        /* --- BUTTONS --- */
        .btn-submit {
            width: 100%; padding: 18px; border-radius: 15px; border: none;
            background: var(--ulu-orange); color: white; font-weight: 800;
            font-size: 1rem; cursor: pointer; display: flex; align-items: center;
            justify-content: center; gap: 12px; transition: 0.3s;
            text-transform: uppercase; margin-top: 10px;
        }
        .btn-submit:hover { background: #B3966F; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255,127,50,0.25); }
        
        .btn-submit.loading { opacity: 0.7; pointer-events: none; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            
            .card-
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

        <a href="view_gallery.php" class="back-btn">
            <i class="fas fa-images"></i> Back to Gallery
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
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-camera"></i> ADD NEW PHOTO
            </div>

            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="photoForm">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Upload Image <span style="color: var(--danger);">*</span></label>
                            
                            <div class="file-upload-wrapper" id="drop-area">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <span class="upload-text">Drag & drop or click to upload</span>
                                <span style="font-size: 0.7rem; color: #AAA; margin-top: 5px;">JPG, PNG (Max 5MB)</span>
                                <input type="file" name="image" id="image_input" accept="image/*" required>
                            </div>

                            <div id="preview-container">
                                <p class="form-group" style="margin-top: 20px;"><label>Image Preview</label></p>
                                <img id="preview-img" src="#" alt="Preview">
                                <p id="file-name" style="font-size: 0.8rem; color: var(--text-gray); margin-top: 10px; font-weight: 600;"></p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-plus-circle"></i> Upload to Gallery
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        const imageInput = document.getElementById('image_input');
        const previewImg = document.getElementById('preview-img');
        const previewContainer = document.getElementById('preview-container');
        const fileName = document.getElementById('file-name');
        const photoForm = document.getElementById('photoForm');
        const submitBtn = document.getElementById('submitBtn');

        // Image Preview Logic
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                fileName.textContent = file.name;
                
                reader.onload = function(e) {
                    previewImg.setAttribute('src', e.target.result);
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Loading State
        photoForm.addEventListener('submit', function() {
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        });

        // Auto-hide alert
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(a => {
                a.style.transition = '0.5s';
                a.style.opacity = '0';
                setTimeout(() => a.remove(), 500);
            });
        }, 4000);
    </script>
</body>
</html>
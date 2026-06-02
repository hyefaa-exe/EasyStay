<?php
session_start();
// 1. Semak Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$package_id = $_GET['package_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

// --- PEMBETULAN: DEFINISI VARIABLE INI ---
$is_logged_in = true; // Wajib ada sebab kita guna di Header nanti
// ----------------------------------------

if (!$package_id) {
    header("Location: package.php");
    exit;
}

// Ambil data pakej
$sql = "SELECT * FROM packages WHERE package_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package_rs = $stmt->get_result()->fetch_assoc();

if (!$package_rs) {
    header("Location: package.php");
    exit;
}

// Ambil tarikh yang telah ditempah untuk disable dalam kalendar
$sqlBooked = "SELECT checkin_date, checkout_date FROM bookings WHERE package_id = $package_id AND status != 'Cancelled'";
$result_cal = $conn->query($sqlBooked);
$booked_ranges = [];
while ($row = $result_cal->fetch_assoc()) {
    $booked_ranges[] = ['from' => $row['checkin_date'], 'to' => $row['checkout_date']];
}
?>

<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>Reservation | Ulu Garden</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <header class="header-area">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-xl-5 col-lg-5 d-none d-lg-block">
                    <nav>
                        <ul id="navigation">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="package.php" class="active-link">Package</a></li>
                            <li><a href="about.php">About</a></li>
                            <li><a href="gallery.php">Gallery</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <?php if ($is_logged_in): ?>
                                <li><a href="my_profile.php">My Profile</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <div class="col-xl-2 col-lg-2 text-center">
                    <a href="index.php" class="logo-link"><img src="img/logo.png" alt="Logo" style="height: 50px;"></a>
                </div>
                <div class="col-xl-5 col-lg-5">
                    <div class="header-right-part d-flex justify-content-end align-items-center">
                        <ul class="social-icons-head d-flex list-unstyled m-0 mr-4">
                            <li class="mr-3"><a href="https://www.facebook.com/profile.php?id=100092359781203" target="_blank" style="color:white;"><i class="fa-brands fa-facebook-f"></i></a></li>
                            <li><a href="https://www.tiktok.com/@ulugardenhomestay" target="_blank" style="color:white;"><i class="fa-brands fa-tiktok"></i></a></li>
                        </ul>
                        <?php if ($is_logged_in): ?>
                            <a href="logout.php" class="auth-btn" style="background: #ff7b00; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; text-decoration: none;">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="auth-btn" style="background: #ff7b00; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; text-decoration: none;">Login / Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <a href="package.php" class="back-btn"><i class="fa fa-arrow-left mr-2"></i> Back to Packages</a>

            <div class="booking-container">
                <div class="package-info-card">
                    <div class="package-poster">
                        <?php
                        // Path gambar dari admin/uploads/
                        $image_name = $package_rs['image'];
                        $image_path = "admin/uploads/" . $image_name;
                        ?>
                        <img src="<?= $image_path ?>" alt="Package Poster" style="width: 100%; border-radius: 10px; object-fit: cover;">
                    </div>
                    <div class="package-details-bottom">
                        <h2><?= htmlspecialchars($package_rs['package_name']) ?></h2>
                        <p class="text-muted"><?= htmlspecialchars($package_rs['description']) ?></p>
                        <div class="price-row">
                            <span class="price-label">Price per night:</span>
                            <div class="price-tag-box">RM <?= number_format($package_rs['price'], 2) ?></div>
                        </div>
                    </div>
                </div>

                <div class="reservation-form-card">
                    <h3 class="font-weight-bold mb-1">Reservation Details</h3>
                    <p class="text-muted mb-4">Please fill in your check-in dates and details below.</p>

                    <form id="bookingForm" action="booking_process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="package_id" value="<?= $package_id ?>">
                        <input type="hidden" name="process_booking" value="1">
                        <input type="hidden" id="total_price_input" name="total_price" value="0">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small">Check-in Date</label>
                                <input type="text" id="checkin_date" name="checkin_date" class="form-control" placeholder="Select date" readonly required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small">Check-out Date</label>
                                <input type="text" id="checkout_date" name="checkout_date" class="form-control" placeholder="Select date" readonly required>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small">Adults</label>
                                <input type="number" name="adults" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small">Children</label>
                                <input type="number" name="children" class="form-control" value="0" min="0">
                            </div>
                        </div>

                        <div class="total-pay-box">
                            <small>Total Payment Due</small>
                            <h2 class="mb-0">RM <span id="display_total">0.00</span></h2>
                        </div>

                        <div class="bank-info-section">
                            <h6 class="font-weight-bold mb-3"><i class="fa fa-university mr-2 text-warning"></i> Bank Transfer Information</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Bank Name:</span>
                                <span class="font-weight-bold small">PUBLIC BANK BERHAD</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted small">Account No:</span>
                                <span class="font-weight-bold text-dark">8763780979</span>
                            </div>

                            <div class="note-box">
                                <strong>Note:</strong> A security deposit of <b>RM 50.00</b> is required upon check-in. This is refundable after room inspection.
                            </div>

                            <label class="font-weight-bold small">Proof of Payment (Image/PDF)</label>
                            <input type="file" id="payment_receipt" name="payment_receipt" class="form-control-file" accept="image/*,.pdf" required>
                        </div>

                        <button type="submit" id="submitBtn" class="btn-confirm" disabled>
                            <i class="fa fa-lock mr-2"></i> Confirm & Book Securely
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>ULU GARDEN</h3>
                    <p>Lot 8012, Kampung Binjai Kertas,</p>
                    <p>21700 Kuala Berang, Terengganu.</p>
                    <div class="footer-social-icons">
                        <a href="https://www.facebook.com/profile.php?id=100092359781203"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.tiktok.com/@ulugardenhomestay"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>CONTACT US</h3>
                    <p><i class="fas fa-phone-alt mr-2"></i> +60 19 211 9223</p>
                    <p><i class="fas fa-envelope mr-2"></i> reservation@ulugarden.com</p>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>NAVIGATION</h3>
                    <a href="index.php">Home</a>
                    <a href="package.php">Package</a>
                    <a href="about.php">About</a>
                    <a href="gallery.php">Gallery</a>
                    <a href="contact.php">Contact</a>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>NEWSLETTER</h3>
                    <p>Subscribe to get latest offers.</p>
                    <div class="newsletter-box">
                        <input type="email" placeholder="Your email">
                        <button type="button">Sign Up</button>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>Copyright Ulu Garden &copy; 2025. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const price = <?= (float)$package_rs['price'] ?>;
        const booked = <?= json_encode($booked_ranges) ?>;
        const submitBtn = document.getElementById('submitBtn');
        const receiptInput = document.getElementById('payment_receipt');
        const totalPriceInput = document.getElementById('total_price_input');

        function validateForm() {
            const inDate = document.getElementById('checkin_date').value;
            const outDate = document.getElementById('checkout_date').value;
            const hasFile = receiptInput.files.length > 0;

            let nights = 0;
            if (inDate && outDate) {
                const d1 = new Date(inDate);
                const d2 = new Date(outDate);
                nights = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
            }

            if (nights > 0 && hasFile) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        function calc() {
            const inDate = document.getElementById('checkin_date').value;
            const outDate = document.getElementById('checkout_date').value;
            if (inDate && outDate) {
                const d1 = new Date(inDate);
                const d2 = new Date(outDate);
                const nights = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
                if (nights > 0) {
                    const totalAmount = (nights * price).toFixed(2);
                    document.getElementById('display_total').innerText = totalAmount;
                    totalPriceInput.value = totalAmount;
                } else {
                    document.getElementById('display_total').innerText = "0.00";
                    totalPriceInput.value = "0.00";
                }
            }
            validateForm();
        }

        receiptInput.addEventListener('change', validateForm);

        const flatConfig = {
            minDate: "today",
            disable: booked,
            onChange: calc,
            dateFormat: "Y-m-d",
        };
        flatpickr("#checkin_date", flatConfig);
        flatpickr("#checkout_date", flatConfig);
    </script>
</body>

</html>
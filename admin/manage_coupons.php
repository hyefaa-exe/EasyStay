<?php
session_start();
require 'db_connect.php';
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['admin_id'];

$success_msg = '';
$error_msg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'Deleted') $success_msg = "Coupon deleted successfully!";
    if ($_GET['msg'] === 'Toggled') $success_msg = "Coupon status updated!";
    if ($_GET['msg'] === 'Added') $success_msg = "New coupon created successfully!";
}

// 1. Handle Delete Coupon
if (isset($_GET['delete'])) {
    $coupon_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM coupons WHERE coupon_id = ?");
    $stmt->bind_param("i", $coupon_id);
    if ($stmt->execute()) {
        header("Location: manage_coupons.php?msg=Deleted");
        exit();
    } else {
        $error_msg = "Failed to delete coupon.";
    }
    $stmt->close();
}

// 2. Handle Toggle Status
if (isset($_GET['toggle']) && isset($_GET['status'])) {
    $coupon_id = intval($_GET['toggle']);
    $new_status = $_GET['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
    $stmt = $conn->prepare("UPDATE coupons SET status = ? WHERE coupon_id = ?");
    $stmt->bind_param("si", $new_status, $coupon_id);
    if ($stmt->execute()) {
        header("Location: manage_coupons.php?msg=Toggled");
        exit();
    } else {
        $error_msg = "Failed to update status.";
    }
    $stmt->close();
}

// 3. Handle Add Coupon
if (isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'] === 'percentage' ? 'percentage' : 'flat';
    $discount_value = floatval($_POST['discount_value']);
    $min_spend = floatval($_POST['min_spend']);
    $expiry_date = $_POST['expiry_date'];
    $max_uses = intval($_POST['max_uses']);
    $status = $_POST['status'] === 'ACTIVE' ? 'ACTIVE' : 'INACTIVE';

    if (empty($code) || empty($expiry_date) || $discount_value <= 0) {
        $error_msg = "Please fill in all required fields correctly.";
    } else {
        // Check duplicate
        $check = $conn->prepare("SELECT coupon_id FROM coupons WHERE code = ?");
        $check->bind_param("s", $code);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error_msg = "Coupon code already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_spend, expiry_date, max_uses, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddsds", $code, $discount_type, $discount_value, $min_spend, $expiry_date, $max_uses, $status);
            if ($stmt->execute()) {
                header("Location: manage_coupons.php?msg=Added");
                exit();
            } else {
                $error_msg = "Failed to create coupon.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Fetch coupons
$result = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
$total_coupons = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .coupon-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            align-items: start;
        }
        @media (max-width: 992px) {
            .coupon-grid {
                grid-template-columns: 1fr;
            }
        }
        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(197, 168, 128, 0.08);
        }
        .form-card h3 {
            font-weight: 800;
            color: var(--slate-800);
            font-size: 1.15rem;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
            border-bottom: 1.5px solid #f3ebd8;
            padding-bottom: 12px;
        }
        .form-group-custom {
            margin-bottom: 18px;
        }
        .form-group-custom label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }
        .form-control-custom {
            width: 100%;
            height: 44px;
            border-radius: 8px;
            border: 1.5px solid var(--slate-200);
            padding: 0 14px;
            font-size: 0.88rem;
            font-family: inherit;
            font-weight: 600;
            outline: none;
            transition: all 0.3s ease;
            background: var(--slate-50);
            color: var(--slate-800);
        }
        .form-control-custom:focus {
            border-color: var(--gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.15);
        }
        .btn-submit-custom {
            width: 100%;
            height: 46px;
            background: var(--slate-800);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit-custom:hover {
            background: var(--gold);
            box-shadow: 0 5px 15px rgba(197, 168, 128, 0.3);
        }
        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            cursor: pointer;
            display: inline-block;
            transition: all 0.2s;
        }
        .badge-active {
            background: rgba(46, 125, 50, 0.08);
            color: #2E7D32;
            border: 1.5px solid rgba(46, 125, 50, 0.15);
        }
        .badge-active:hover {
            background: #2E7D32;
            color: #fff;
        }
        .badge-inactive {
            background: rgba(198, 40, 40, 0.08);
            color: #C62828;
            border: 1.5px solid rgba(198, 40, 40, 0.15);
        }
        .badge-inactive:hover {
            background: #C62828;
            color: #fff;
        }
        .alert-custom {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .alert-success-custom {
            background: #e6f7ed;
            color: #1e7e34;
            border-left: 4px solid #28a745;
        }
        .alert-danger-custom {
            background: #fdf2f2;
            color: #d12e2e;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Manage Coupons</div>
            <div class="topbar-breadcrumb">Create, delete, and monitor promo discount codes for EasyStay homestay</div>
        </div>
        <div class="topbar-right">
            <div class="btn btn-outline btn-sm" style="cursor:default; opacity:0.95;">
                <i class="fas fa-ticket-alt"></i> Total: <?= $total_coupons ?>
            </div>
        </div>
    </div>

    <div class="admin-content">

        <!-- Alert messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle mr-2"></i> <?= $success_msg ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert-custom alert-danger-custom">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <div class="coupon-grid">
            
            <!-- 1. Add Coupon Form Card -->
            <div class="form-card">
                <h3><i class="fas fa-plus mr-2"></i> Create Coupon</h3>
                <form method="POST">
                    <div class="form-group-custom">
                        <label for="code">Coupon Code</label>
                        <input type="text" id="code" name="code" class="form-control-custom" placeholder="e.g. WELCOME10" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group-custom">
                        <label for="discount_type">Discount Type</label>
                        <select id="discount_type" name="discount_type" class="form-control-custom" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="flat">Flat Amount (RM)</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label for="discount_value">Discount Value</label>
                        <input type="number" id="discount_value" name="discount_value" class="form-control-custom" placeholder="e.g. 10 or 25" step="0.01" required min="0.01">
                    </div>

                    <div class="form-group-custom">
                        <label for="min_spend">Minimum Spend (RM)</label>
                        <input type="number" id="min_spend" name="min_spend" class="form-control-custom" placeholder="e.g. 0 or 150" step="0.01" value="0.00">
                    </div>

                    <div class="form-group-custom">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-control-custom" required value="<?= date('Y-m-d', strtotime('+3 months')) ?>">
                    </div>

                    <div class="form-group-custom">
                        <label for="max_uses">Max Uses (0 = Unlimited)</label>
                        <input type="number" id="max_uses" name="max_uses" class="form-control-custom" placeholder="e.g. 0" value="0" min="0">
                    </div>

                    <div class="form-group-custom">
                        <label for="status">Initial Status</label>
                        <select id="status" name="status" class="form-control-custom" required>
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" name="add_coupon" class="btn-submit-custom">
                        <i class="fas fa-save"></i> Save Coupon
                    </button>
                </form>
            </div>

            <!-- 2. Coupons List Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3><i class="fas fa-ticket-alt"></i> Promo & Coupon Codes</h3>
                </div>
                <div class="table-responsive">
                    <table id="couponsTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No.</th>
                                <th>Code</th>
                                <th>Value / Type</th>
                                <th>Min. Spend</th>
                                <th>Expiry</th>
                                <th>Uses (Used / Limit)</th>
                                <th>Status</th>
                                <th style="text-align: right; width: 80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if ($result && $result->num_rows > 0):
                                $no = 1;
                                while ($row = $result->fetch_assoc()):
                                    $is_percentage = ($row['discount_type'] === 'percentage');
                                    $type_lbl = $is_percentage ? '%' : 'RM';
                                    $val_lbl = $is_percentage ? number_format($row['discount_value'], 0) : number_format($row['discount_value'], 2);
                                    
                                    $uses_lbl = $row['uses_count'];
                                    $limit_lbl = $row['max_uses'] > 0 ? $row['max_uses'] : '∞';
                                    
                                    $is_active = ($row['status'] === 'ACTIVE');
                                    $status_class = $is_active ? 'badge-active' : 'badge-inactive';
                                    
                                    $is_expired = ($row['expiry_date'] < date('Y-m-d'));
                            ?>
                            <tr style="<?= $is_expired ? 'opacity: 0.65; background: #fafafa;' : '' ?>">
                                <td style="color: var(--slate-300); font-weight: 600;"><?= $no++ ?>.</td>
                                <td style="font-weight: 800; color: var(--slate-800); font-size: 0.95rem; letter-spacing: 0.3px;">
                                    <?= htmlspecialchars($row['code']) ?>
                                    <?php if ($is_expired): ?>
                                        <span style="font-size: 0.65rem; background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: 700; margin-left: 5px;">EXPIRED</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 700; color: var(--gold);">
                                    <?= $is_percentage ? '' : 'RM ' ?><?= $val_lbl ?><?= $is_percentage ? '%' : '' ?>
                                </td>
                                <td style="color: var(--slate-500); font-weight: 500;">
                                    RM <?= number_format($row['min_spend'], 2) ?>
                                </td>
                                <td style="font-size: 0.8rem; color: var(--slate-500); font-weight: 600;">
                                    <?= date('d M Y', strtotime($row['expiry_date'])) ?>
                                </td>
                                <td style="font-size: 0.82rem; color: var(--slate-600); font-weight: 600;">
                                    <span style="color: var(--slate-800);"><?= $uses_lbl ?></span> / <?= $limit_lbl ?>
                                </td>
                                <td>
                                    <a href="manage_coupons.php?toggle=<?= $row['coupon_id'] ?>&status=<?= $row['status'] ?>" class="badge-status <?= $status_class ?>" title="Click to toggle status">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="action-btns" style="justify-content: flex-end;">
                                        <a href="manage_coupons.php?delete=<?= $row['coupon_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Confirm delete this coupon? Users will no longer be able to use it.');" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="8" class="table-empty">
                                    <i class="fas fa-ticket-alt"></i> No coupons found. Create one on the left!
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container">
                    <span id="pageInfo" style="font-size: 0.8rem; color: var(--slate-400);">Loading...</span>
                    <div style="display: flex; gap: 6px;">
                        <button class="page-btn" id="prevBtn" onclick="prevPage()"><i class="fas fa-chevron-left"></i> Prev</button>
                        <button class="page-btn" id="nextBtn" onclick="nextPage()">Next <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
let currentPage = 1;
const rowsPerPage = 10;
const tableRows = Array.from(document.querySelectorAll('#tableBody tr'));

function displayTable() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    tableRows.forEach((row, i) => {
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });
    
    document.getElementById('pageInfo').innerText = `Showing ${tableRows.length > 0 ? start + 1 : 0}–${Math.min(end, tableRows.length)} of ${tableRows.length} coupons`;
    document.getElementById('prevBtn').disabled = (currentPage === 1);
    document.getElementById('nextBtn').disabled = (currentPage >= Math.ceil(tableRows.length / rowsPerPage));
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        displayTable();
    }
}

function nextPage() {
    if (currentPage < Math.ceil(tableRows.length / rowsPerPage)) {
        currentPage++;
        displayTable();
    }
}

document.addEventListener('DOMContentLoaded', displayTable);
</script>
</body>
</html>
<?php $conn->close(); ?>

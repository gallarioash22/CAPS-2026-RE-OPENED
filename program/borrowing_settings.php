<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("db_connect.php");
include("pending_expiry_helper.php");

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['student_role']) || $_SESSION['student_role'] !== 'admin') {
    echo "<script>alert('Access denied. Admin only.'); window.location='dashboard.php';</script>";
    exit;
}

auto_expire_pending_requests($conn);

$msg = "";
$msgType = "";

if (isset($_POST['save_pending_limit'])) {
    $pending_limit_minutes = (int)($_POST['pending_limit_minutes'] ?? 180);

    if ($pending_limit_minutes < 5) {
        $msg = "Pending limit must be at least 5 minutes.";
        $msgType = "danger";
    } elseif ($pending_limit_minutes > 1440) {
        $msg = "Pending limit cannot exceed 1440 minutes (24 hours).";
        $msgType = "danger";
    } else {
        $value = (string)$pending_limit_minutes;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO tbl_system_settings (setting_key, setting_value)
            VALUES ('pending_limit_minutes', ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        mysqli_stmt_bind_param($stmt, "ss", $value, $value);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Pending request limit updated successfully.";
            $msgType = "success";
        } else {
            $msg = "Failed to save setting.";
            $msgType = "danger";
        }
    }
}

$current_limit = get_pending_limit_minutes($conn);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Borrowing Settings — Golden Minds E-Library</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --brown: #b5651d;
            --brown-dark: #8b4b2b;
            --brown-deep: #6f3b3b;
            --light: #fff8e7;
            --accent: #ffcc66;
            --text: #333;
            --muted: #777;
            --border: #ecd9b0;
            --success-bg: #e8f7ee;
            --success-text: #146c43;
            --danger-bg: #fdeaea;
            --danger-text: #b42318;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: var(--light);
            color: var(--text);
        }

        .topbar {
            background: linear-gradient(180deg, var(--brown-deep), var(--brown));
            color: white;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar-title {
            margin: 0;
            font-size: 22px;
        }

        .logout-btn {
            display: inline-block;
            padding: 9px 16px;
            background: var(--brown-dark);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .logout-btn:hover {
            background: #5e2d21;
        }

        .wrap {
            padding: 20px;
        }

        .main {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #f1e1bb;
        }

        .section-header {
            background: #fff3d1;
            border-bottom: 1px solid var(--border);
            padding: 20px 24px 16px;
        }

        .section-header h2 {
            margin: 0 0 6px 0;
            font-size: 24px;
            color: var(--brown-deep);
        }

        .section-header p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .section-body {
            padding: 24px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #b7e4c7;
        }

        .alert.danger {
            background: var(--danger-bg);
            color: var(--danger-text);
            border-color: #f5c2c7;
        }

        .info-box {
            background: #fff8e7;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            margin: 0 0 10px 0;
            color: var(--brown-deep);
            font-size: 17px;
        }

        .info-box p {
            margin: 0 0 8px 0;
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin: 18px 0 22px;
        }

        .settings-card {
            background: #fff8e7;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
        }

        .settings-card strong {
            display: block;
            margin-bottom: 6px;
            color: var(--brown-deep);
            font-size: 14px;
        }

        .settings-card span {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text);
        }

        input[type="number"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d8c7a2;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 15px;
            background: #fffdf8;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="number"]:focus {
            border-color: var(--brown);
            box-shadow: 0 0 0 3px rgba(181, 101, 29, 0.15);
        }

        .helper-text {
            margin-top: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 10px;
        }

        .btn-primary {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            background: var(--brown);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-primary:hover {
            background: var(--brown-dark);
        }

        .btn-secondary {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 10px;
            text-decoration: none;
            background: #6c757d;
            color: white;
            font-weight: bold;
            transition: background 0.2s ease;
        }

        .btn-secondary:hover {
            background: #565e64;
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .wrap {
                padding: 14px;
            }

            .section-header,
            .section-body {
                padding: 18px;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-left">
        <h1 class="topbar-title">Golden Minds E-Library</h1>
    </div>
    <div class="topbar-right">
        <a href="dashboard.php" class="logout-btn">Back to Dashboard</a>
    </div>
</header>

<div class="wrap">
    <section class="main">
        <div class="section-header">
            <h2>Borrowing Settings</h2>
            <p>
                Manage how long a borrow request stays in <strong>Pending</strong>.
                If the student does not claim the book at the librarian counter before the time runs out,
                the request automatically becomes <strong>Expired</strong>.
            </p>
        </div>

        <div class="section-body">
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msgType; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h3>How this setting works</h3>
                <p><strong>Recommended default:</strong> 180 minutes (3 hours)</p>
                <p>
                    This time limit applies to all student borrow requests with status
                    <strong>Pending</strong>. Once the limit is reached, the request will no longer stay
                    in the active approval queue and will move to <strong>Expired</strong>.
                </p>
            </div>

            <div class="settings-grid">
                <div class="settings-card">
                    <strong>For Testing</strong>
                    <span>Use 5 minutes first so you can quickly confirm the timer is working.</span>
                </div>
                <div class="settings-card">
                    <strong>For Real Usage</strong>
                    <span>After testing, set it back to 180 minutes for actual student use.</span>
                </div>
                <div class="settings-card">
                    <strong>Result</strong>
                    <span>Unclaimed requests will not remain pending forever in the system.</span>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="pending_limit_minutes">Pending request time limit (minutes)</label>
                    <input
                        type="number"
                        name="pending_limit_minutes"
                        id="pending_limit_minutes"
                        min="5"
                        max="1440"
                        value="<?php echo (int)$current_limit; ?>"
                        required
                    >
                    <div class="helper-text">
                        Minimum: 5 minutes • Maximum: 1440 minutes (24 hours)
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="save_pending_limit" class="btn-primary">Save Setting</button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </section>
</div>

</body>
</html>
<?php
/* 
==========================================================================
 SYSTEM SPECIFICATION & ARCHITECTURE (BLACK & RED THEME)
==========================================================================
 1. Database Connection & Helper Functions
 2. Authentication & Data Validation (Login / Logout)
 3. CRUD Logic Handling (Information, User Management, & QR Scanner)
 4. Navigation & Layout Framework (Black & Red Design)
 5. Modules (Dashboard, Info Mgmt, Attendance QR Scan, Reports, Users, Logs)
==========================================================================
*/

// --- SECTION 1: DATABASE CONNECTION & CONFIGURATION ---
$host = 'localhost';
$db   = 'org_attendance';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

session_start();

// Audit Trail Helper Function
function log_activity($pdo, $action, $details = '') {
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $details]);
}

// Data Input Sanitization
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}


// --- SECTION 2: AUTHENTICATION & ROUTING LOGIC ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_SESSION['user_id'])) {
        log_activity($pdo, "User Logout", "User logged out.");
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            log_activity($pdo, "User Login", "User {$user['username']} successfully logged in.");
            header("Location: index.php?page=dashboard");
            exit();
        } else {
            $login_error = "Maling username o password.";
        }
    } else {
        $login_error = "Paki-fill up ang lahat ng fields.";
    }
}

// Render Login Page if not logged in
if (!isset($_SESSION['user_id'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Digital Attendance System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #121212; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #ffffff; }
        .login-card { background: #1e1e1e; padding: 35px; border-radius: 8px; box-shadow: 0 4px 20px rgba(220, 38, 38, 0.25); width: 100%; max-width: 380px; border: 1px solid #dc2626; }
        .login-card h2 { margin-top: 0; color: #dc2626; text-align: center; text-transform: uppercase; letter-spacing: 1px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; color: #cccccc; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #333333; background: #2a2a2a; color: #ffffff; border-radius: 4px; box-sizing: border-box; }
        .form-group input:focus { border-color: #dc2626; outline: none; }
        .btn-submit { width: 100%; padding: 11px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; text-transform: uppercase; transition: 0.3s; }
        .btn-submit:hover { background: #b91c1c; }
        .alert { color: #ffffff; background: #991b1b; padding: 10px; border-radius: 4px; font-size: 14px; margin-bottom: 15px; text-align: center; border: 1px solid #dc2626; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>System Login</h2>
        <?php if ($login_error): ?>
            <div class="alert"><?= $login_error ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login_btn" class="btn-submit">Login</button>
        </form>
    </div>
</body>
</html>
<?php
    exit();
}


// --- SECTION 3: CRUD & HANDLERS ---
$page = $_GET['page'] ?? 'dashboard';

// CRUD: Add Member
if (isset($_POST['action_member_add'])) {
    $code = sanitize($_POST['member_code']);
    $fname = sanitize($_POST['first_name']);
    $lname = sanitize($_POST['last_name']);
    $dept = sanitize($_POST['department']);

    if (!empty($code) && !empty($fname) && !empty($lname)) {
        $stmt = $pdo->prepare("INSERT INTO members (member_code, first_name, last_name, department) VALUES (?, ?, ?, ?)");
        $stmt->execute([$code, $fname, $lname, $dept]);
        log_activity($pdo, "Create Member", "Added member $code ($fname $lname)");
    }
    header("Location: index.php?page=members");
    exit();
}

// CRUD: Delete Member
if (isset($_GET['action_member_delete'])) {
    $id = (int)$_GET['action_member_delete'];
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
    $stmt->execute([$id]);
    log_activity($pdo, "Delete Member", "Deleted member ID: $id");
    header("Location: index.php?page=members");
    exit();
}

// CRUD: Add User
if (isset($_POST['action_user_add'])) {
    $uname = sanitize($_POST['username']);
    $pword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fname = sanitize($_POST['full_name']);
    $role  = sanitize($_POST['role']);

    if (!empty($uname) && !empty($_POST['password'])) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uname, $pword, $fname, $role]);
        log_activity($pdo, "Create User", "Created system user: $uname");
    }
    header("Location: index.php?page=users");
    exit();
}

// CRUD: Delete User
if (isset($_GET['action_user_delete'])) {
    $id = (int)$_GET['action_user_delete'];
    if ($id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, "Delete User", "Deleted system user ID: $id");
    }
    header("Location: index.php?page=users");
    exit();
}

// Attendance Processing
if (isset($_POST['action_take_attendance'])) {
    $member_code = sanitize($_POST['member_code']);
    $status      = sanitize($_POST['status'] ?? 'Present');
    $date        = date('Y-m-d');
    $time        = date('H:i:s');

    $m_stmt = $pdo->prepare("SELECT id, first_name, last_name FROM members WHERE member_code = ?");
    $m_stmt->execute([$member_code]);
    $member = $m_stmt->fetch();

    if ($member) {
        $member_id = $member['id'];
        $chk = $pdo->prepare("SELECT id FROM attendance WHERE member_id = ? AND attendance_date = ?");
        $chk->execute([$member_id, $date]);
        if (!$chk->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO attendance (member_id, attendance_date, status, time_in) VALUES (?, ?, ?, ?)");
            $stmt->execute([$member_id, $date, $status, $time]);
            log_activity($pdo, "Record Attendance", "Recorded $status for Member: {$member['first_name']} {$member['last_name']} ($member_code)");
            $_SESSION['msg'] = "Narecord nang matagumpay ang attendance para kay {$member['first_name']} {$member['last_name']}!";
        } else {
            $_SESSION['msg_err'] = "Narecord na ang attendance ngayong araw para sa estudyanteng ito!";
        }
    } else {
        $_SESSION['msg_err'] = "Hindi nahanap ang Student ID/Code!";
    }
    header("Location: index.php?page=attendance");
    exit();
}
?>

<!-- --- SECTION 4: USER INTERFACE (BLACK & RED THEME) --- -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Digital Attendance System</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body {
         font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
         margin: 0; 
         background-color: #121212; color: #e0e0e0; }
        header { 
         background: #000000; 
         color: white; 
         padding: 15px 20px; 
         display: flex; 
         justify-content: space-between; 
         align-items: center; 
         border-bottom: 2px solid #dc2626; 
        } 

        header h1 { 
         margin: 0; 
         font-size: 20px; 
         color: #dc2626; 
         text-transform: uppercase; 
         letter-spacing: 1px; }

        nav { 
         background: #1a1a1a; 
         padding: 10px 20px; 
         border-bottom: 1px solid #2a2a2a; 
        }

        nav a {
         color: #cccccc; 
         text-decoration: none; 
         padding: 8px 15px; 
         border-radius: 4px; 
         margin-right: 5px; 
         font-weight: 500; 
         display: inline-block; 
         transition: 0.2s; 
        }

        nav a:hover, nav a.active {
         background: #dc2626; 
         color: white; 
        }
        
        .container { 
         padding: 25px; 
         max-width: 1200px; 
         margin: 0 auto; 
        }
        
        .card {
         background: #1e1e1e;
         border-radius: 6px;
         padding: 20px; 
         margin-bottom: 20px; 
         box-shadow: 0 4px 10px rgba(0,0,0,0.5); 
         border: 1px solid #2d2d2d; 
        }

        .card h2, .card h3 { 
         color: #dc2626;
         margin-top: 0;
        }
        
        .grid-stats {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
         gap: 20px;
         margin-bottom: 20px;
        }

        .stat-card { 
         background: #1e1e1e;
         padding: 20px;
         border-radius: 6px;
         border-left: 5px solid #dc2626;
         box-shadow: 0 4px 10px rgba(0,0,0,0.5);
         border-top: 1px solid #2d2d2d;
         border-right: 1px solid #2d2d2d; 
         border-bottom: 1px solid #2d2d2d;
        }

        .stat-card h3 { 
          margin: 0 0 10px 0;
          font-size: 14px;
          color: #a0a0a0;
          text-transform: uppercase;
        }

        .stat-card p {
         margin: 0;
         font-size: 28px;
         font-weight: bold;
         color: #ffffff;
        }

        table { 
         width: 100%;
         border-collapse: collapse;
         margin-top: 15px;
         background: #1e1e1e;
        }

        table, th, td { 
         border: 1px solid #333333;
        }

        th, td { 
         padding: 12px;
         text-align: left; 
        }

        th { 
         background: #000000; 
         color: #dc2626; 
         font-weight: 600; 
         text-transform: uppercase; 
         font-size: 13px; 
        }

        tr:nth-child(even) {
         background-color: #252525; 
        }

        .btn {
         padding: 8px 14px;
         background: #dc2626; 
         color: white; 
         border: none; 
         border-radius: 4px; 
         cursor: pointer; 
         text-decoration: none; 
         font-size: 14px; 
         display: inline-block; 
         font-weight: bold; 
         transition: 0.2s;
       }
        .btn:hover { 
         background: #b91c1c; }
        .btn-danger { 
         background: #7f1d1d; 
        }

        .btn-danger:hover { 
         background: #991b1b;
         
        }

        .btn-success { 
         background: #166534;
         }

        .btn-success:hover { 
         background: #15803d; 
        }

        .form-inline { 
         display: flex; 
         gap: 10px; 
         flex-wrap: wrap; 
         margin-top: 10px; 
        }

        .form-inline input, .form-inline select {
           padding: 9px; 
          border: 1px solid #333333; 
          background: #2a2a2a; color: #ffffff;
          border-radius: 4px; 
            
        }
        .form-inline input:focus, .form-inline select:focus { 
         border-color: #dc2626; 
         outline: none;
         }


        .qr-img {
         width: 80px; 
         height: 80px; 
         background: white;
         padding: 3px; 
         border-radius: 4px;
         }
        .alert-success { 
         background: #14532d; 
         color: #ffffff; 
         padding: 12px; 
         border-radius: 4px; 
         margin-bottom: 15px; 
         border: 1px solid #166534; }
        
        .alert-danger {
          background: #7f1d1d; 
          color: #ffffff;
          padding: 12px;
          border-radius: 4px;
          margin-bottom: 15px; 
          border: 1px solid #991b1b;
         }
    </style>
</head>
<body>

    <header>
        <h1>Org Digital Attendance System</h1>
        <div>User: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= $_SESSION['role'] ?>) | <a href="index.php?action=logout" style="color:#ef4444; text-decoration:none; font-weight:bold;">Logout</a></div>
    </header>

    <nav>
        <a href="index.php?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="index.php?page=members" class="<?= $page === 'members' ? 'active' : '' ?>">Information Management</a>
        <a href="index.php?page=attendance" class="<?= $page === 'attendance' ? 'active' : '' ?>">Take Attendance (QR Scanner)</a>
        <a href="index.php?page=reports" class="<?= $page === 'reports' ? 'active' : '' ?>">Reports</a>
        <a href="index.php?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">User Management</a>
        <a href="index.php?page=logs" class="<?= $page === 'logs' ? 'active' : '' ?>">Activity Logs</a>
    </nav>

    <div class="container">

    <!-- Notifications -->
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['msg_err'])): ?>
        <div class="alert-danger"><?= $_SESSION['msg_err']; unset($_SESSION['msg_err']); ?></div>
    <?php endif; ?>

    <!-- --- SECTION 5: MODULES --- -->

    <?php if ($page === 'dashboard'): ?>
        <!-- MODULE 1: DASHBOARD -->
        <h2>Dashboard Overview</h2>
        <?php
            $total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
            $today_present = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'")->fetchColumn();
            $today_late    = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Late'")->fetchColumn();
            $total_users   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        ?>
        <div class="grid-stats">
            <div class="stat-card"><h3>Total Members</h3><p><?= $total_members ?></p></div>
            <div class="stat-card" style="border-left-color: #16a34a;"><h3>Present Today</h3><p><?= $today_present ?></p></div>
            <div class="stat-card" style="border-left-color: #ca8a04;"><h3>Late Today</h3><p><?= $today_late ?></p></div>
            <div class="stat-card" style="border-left-color: #dc2626;"><h3>System Users</h3><p><?= $total_users ?></p></div>
        </div>


    <?php elseif ($page === 'members'): ?>
        <!-- MODULE 2: INFORMATION MANAGEMENT & AUTO QR GENERATOR -->
        <h2>Information Management & Auto QR Generator</h2>

        <div class="card">
            <h3>Add New Member (Auto QR Generation)</h3>
            <form method="POST" action="index.php?page=members" class="form-inline">
                <input type="text" name="member_code" placeholder="Member/Student ID" required>
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="text" name="department" placeholder="Department/Course" required>
                <button type="submit" name="action_member_add" class="btn">Add Record</button>
            </form>
        </div>

        <div class="card">
            <h3>Members Directory</h3>
            <table>
                <tr>
                    <th>QR Code</th>
                    <th>ID Code</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
                <?php
                $members = $pdo->query("SELECT * FROM members ORDER BY id DESC")->fetchAll();
                foreach ($members as $m):
                    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($m['member_code']);
                ?>
                <tr>
                    <td>
                        <img src="<?= $qr_api_url ?>" alt="QR Code" class="qr-img"><br>
                        <a href="<?= $qr_api_url ?>" target="_blank" style="font-size:12px; color:#ef4444;">Download/Print</a>
                    </td>
                    <td><strong><?= htmlspecialchars($m['member_code']) ?></strong></td>
                    <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                    <td><?= htmlspecialchars($m['department']) ?></td>
                    <td>
                        <a href="index.php?page=members&action_member_delete=<?= $m['id'] ?>" class="btn btn-danger" onclick="return confirm('Sigurado ka bang gusto mong burahin ito?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>


    <?php elseif ($page === 'attendance'): ?>
        <!-- MODULE: QR SCANNER & MANUAL ATTENDANCE -->
        <h2>Take Attendance</h2>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <!-- Camera Scanner Card -->
            <div class="card" style="flex: 1; min-width: 300px;">
                <h3>Scan Student QR Code</h3>
                <div id="reader" style="width: 100%; background: #000;"></div>
            </div>

            <!-- Manual Submission Form -->
            <div class="card" style="flex: 1; min-width: 300px;">
                <h3>Manual / Scanned Entry</h3>
                <form id="attendance-form" method="POST" action="index.php?page=attendance">
                    <p>
                        <label>Student ID / Scanned Code:</label><br>
                        <input type="text" id="member_code_input" name="member_code" placeholder="Scan or enter ID" required style="width:100%; padding:10px; margin-top:5px; background:#2a2a2a; color:#fff; border:1px solid #333; border-radius:4px; box-sizing:border-box;">
                    </p>
                    <p>
                        <label>Status:</label><br>
                        <select name="status" style="width:100%; padding:10px; margin-top:5px; background:#2a2a2a; color:#fff; border:1px solid #333; border-radius:4px;">
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </p>
                    <button type="submit" name="action_take_attendance" class="btn btn-success" style="width:100%; padding:12px; font-size:16px;">Submit Attendance</button>
                </form>
            </div>
        </div>

        <script>
            function onScanSuccess(decodedText, decodedResult) {
                document.getElementById('member_code_input').value = decodedText;
                document.getElementById('attendance-form').submit();
            }

            let html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
            html5QrcodeScanner.render(onScanSuccess);
        </script>


    <?php elseif ($page === 'reports'): ?>
        <!-- MODULE 3: REPORTS -->
        <h2>Attendance Reports</h2>
        <?php $selected_date = $_GET['filter_date'] ?? date('Y-m-d'); ?>
        <div class="card">
            <form method="GET" action="index.php" class="form-inline">
                <input type="hidden" name="page" value="reports">
                <label>Filter Date: </label>
                <input type="date" name="filter_date" value="<?= $selected_date ?>">
                <button type="submit" class="btn">Apply Filter</button>
            </form>
        </div>

        <div class="card">
            <h3>Records for Date: <?= htmlspecialchars($selected_date) ?></h3>
            <table>
                <tr><th>Member Code</th><th>Name</th><th>Department</th><th>Status</th><th>Time In</th></tr>
                <?php
                $stmt = $pdo->prepare("
                    SELECT a.*, m.member_code, m.first_name, m.last_name, m.department 
                    FROM attendance a 
                    JOIN members m ON a.member_id = m.id 
                    WHERE a.attendance_date = ?
                    ORDER BY a.time_in ASC
                ");
                $stmt->execute([$selected_date]);
                $records = $stmt->fetchAll();

                if (empty($records)) {
                    echo "<tr><td colspan='5'>Walang records para sa petsang ito.</td></tr>";
                } else {
                    foreach ($records as $r):
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['member_code']) ?></td>
                    <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= htmlspecialchars($r['department']) ?></td>
                    <td><strong><?= htmlspecialchars($r['status']) ?></strong></td>
                    <td><?= htmlspecialchars($r['time_in']) ?></td>
                </tr>
                <?php endforeach; } ?>
            </table>
        </div>


    <?php elseif ($page === 'users'): ?>
        <!-- MODULE 4: USER MANAGEMENT -->
        <h2>User Management</h2>

        <div class="card">
            <h3>Create System User</h3>
            <form method="POST" action="index.php?page=users" class="form-inline">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="full_name" placeholder="Full Name" required>
                <select name="role">
                    <option value="Staff">Staff</option>
                    <option value="Admin">Admin</option>
                </select>
                <button type="submit" name="action_user_add" class="btn">Create Account</button>
            </form>
        </div>

        <div class="card">
            <h3>Accounts List</h3>
            <table>
                <tr><th>Username</th><th>Full Name</th><th>Role</th><th>Created At</th><th>Action</th></tr>
                <?php
                $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
                foreach ($users as $u):
                ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td><?= $u['created_at'] ?></td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <a href="index.php?page=users&action_user_delete=<?= $u['id'] ?>" class="btn btn-danger" onclick="return confirm('Burahin ang user?')">Delete</a>
                        <?php else: ?>
                            <em style="color:#a0a0a0;">Active Session</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>


    <?php elseif ($page === 'logs'): ?>
        <!-- MODULE 5: AUDIT TRAIL / ACTIVITY LOGS -->
        <h2>Activity Logs & Audit Trail</h2>
        <div class="card">
            <table>
                <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Details</th></tr>
                <?php
                $logs = $pdo->query("
                    SELECT l.*, u.username 
                    FROM activity_logs l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    ORDER BY l.created_at DESC 
                    LIMIT 50
                ")->fetchAll();

                foreach ($logs as $log):
                ?>
                <tr>
                    <td><?= $log['created_at'] ?></td>
                    <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                    <td><strong style="color:#dc2626;"><?= htmlspecialchars($log['action']) ?></strong></td>
                    <td><?= htmlspecialchars($log['details']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

    </div>
</body>
</html>
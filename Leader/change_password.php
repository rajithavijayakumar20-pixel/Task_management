<?php
session_start();
require_once "../config/database.php";

/* Login Check */
if (!isset($_SESSION["user_id"]) || strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION["user_id"];
$successMsg = "";
$errorMsg = "";

/* Handle Password Change Submission */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMsg = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMsg = "New passwords do not match.";
    } else {
        /* Fetch User Password Hash */
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($currentPassword, $hashedPassword)) {
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHashedPassword, $userId);
            
            if ($updateStmt->execute()) {
                $successMsg = "Password changed successfully!";
            } else {
                $errorMsg = "Error updating password. Try again.";
            }
            $updateStmt->close();
        } else {
            $errorMsg = "Incorrect current password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Leader</title>
    <link rel="stylesheet" href="../assets/css/leader.css">
</head>
<body>
<div class="dashboard-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <h2>Task<span>Flow</span></h2>
            <p>Leader Panel</p>
        </div>
        <details class="main-menu" open>
            <summary>
                <span class="menu-icon">☰</span>
                <span class="menu-text">MENU</span>
                <span class="menu-arrow">▼</span>
            </summary>
            <div class="menu-list">
                <a href="dashboard.php"><span>🏠</span><span>Dashboard</span></a>
                <a href="add_staff.php"><span>👥</span><span>Add Staff</span></a>
                <a href="manage_staff.php"><span>👤</span><span>Manage Staff</span></a>
                <a href="assign_task.php"><span>📝</span><span>Assign Task</span></a>
                <a href="task_list.php"><span>📋</span><span>Task List</span></a>
                <a href="warnings.php"><span>⚠️</span><span>Warnings</span></a>
                <a href="profile.php" class="active"><span>👤</span><span>Profile</span></a>
                <a href="../logout.php" class="logout-link"><span>🚪</span><span>Logout</span></a>
            </div>
        </details>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar">
            <h3>Change Password</h3>
        </header>

        <section class="profile-section">
            <div class="profile-card" style="max-width: 600px; margin: 0 auto;">
                <h2>Update Password</h2>

                <?php if (!empty($successMsg)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <?php echo $successMsg; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMsg)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <?php echo $errorMsg; ?>
                    </div>
                <?php endif; ?>

                <form action="change_password.php" method="POST">
                    <div class="profile-item" style="margin-bottom: 15px;">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>

                    <div class="profile-item" style="margin-bottom: 15px;">
                        <label>New Password</label>
                        <input type="password" name="new_password" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>

                    <div class="profile-item" style="margin-bottom: 15px;">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>

                    <div class="profile-actions" style="margin-top: 20px;">
                        <button type="submit" class="edit-profile-btn" style="cursor: pointer; border: none; padding: 10px 20px;">Update Password</button>
                        <a href="profile.php" class="password-btn" style="text-decoration: none; padding: 10px 20px; display: inline-block;">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
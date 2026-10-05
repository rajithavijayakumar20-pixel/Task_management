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

/* Handle Form Submission */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $position = trim($_POST["position"]);

    if (empty($name) || empty($email) || empty($username)) {
        $errorMsg = "Name, Email, and Username are required fields.";
    } else {
        $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, username = ?, position = ? WHERE id = ?");
        $updateStmt->bind_param("ssssi", $name, $email, $username, $position, $userId);
        
        if ($updateStmt->execute()) {
            $successMsg = "Profile updated successfully!";
        } else {
            $errorMsg = "Error updating profile. Username or Email might already be taken.";
        }
        $updateStmt->close();
    }
}

/* Fetch Current User Details */
$stmt = $conn->prepare("SELECT name, email, username, position, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Leader</title>
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
            <h3>Edit Profile</h3>
            <div class="user-info">Welcome, <?php echo htmlspecialchars($user["name"]); ?></div>
        </header>

        <section class="profile-section">
            <div class="profile-card" style="max-width: 600px; margin: 0 auto;">
                <h2>Edit Your Information</h2>
                
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

                <form action="edit_profile.php" method="POST">
                    <div class="profile-item" style="margin-bottom: 15px;">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user["name"]); ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>

                    <div class="profile-item" style="margin-bottom: 15px;">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user["email"]); ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>

                    <div class="profile-item" style="margin-bottom: 15px;">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user["username"]); ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>

                    <div class="profile-item" style="margin-bottom: 15px;">
                        <label>Position</label>
                        <input type="text" name="position" value="<?php echo htmlspecialchars($user["position"]); ?>" style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>

                    <div class="profile-actions" style="margin-top: 20px;">
                        <button type="submit" class="edit-profile-btn" style="cursor: pointer; border: none; padding: 10px 20px;">Save Changes</button>
                        <a href="profile.php" class="password-btn" style="text-decoration: none; padding: 10px 20px; display: inline-block;">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
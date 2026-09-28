<?php
session_start();
require_once "../config/database.php";

/* Login Check */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION["user_id"];

/* Get Leader Details */
$stmt = $conn->prepare("
    SELECT id, name, email, username, position, role, created_at
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    die("User profile not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profile - Leader</title>

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


        <!-- MENU -->

        <details class="main-menu" open>

            <summary>

                <span class="menu-icon">☰</span>

                <span class="menu-text">MENU</span>

                <span class="menu-arrow">▼</span>

            </summary>


            <div class="menu-list">

                <a href="dashboard.php">
                    <span>🏠</span>
                    <span>Dashboard</span>
                </a>

                <a href="add_staff.php">
                    <span>👥</span>
                    <span>Add Staff</span>
                </a>

                <a href="manage_staff.php">
                    <span>👤</span>
                    <span>Manage Staff</span>
                </a>

                <a href="assign_task.php">
                    <span>📝</span>
                    <span>Assign Task</span>
                </a>

                <a href="task_list.php">
                    <span>📋</span>
                    <span>Task List</span>
                </a>

                <a href="warnings.php">
                    <span>⚠️</span>
                    <span>Warnings</span>
                </a>

                <a href="profile.php" class="active">
                    <span>👤</span>
                    <span>Profile</span>
                </a>

                <a href="../logout.php" class="logout-link">
                    <span>🚪</span>
                    <span>Logout</span>
                </a>

            </div>

        </details>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <h3>My Profile</h3>

            <div class="user-info">

                Welcome,
                <?php
                echo htmlspecialchars($user["name"]);
                ?>

            </div>

        </header>


        <!-- PROFILE -->

        <section class="profile-section">

            <div class="profile-card">

                <!-- Profile Header -->

                <div class="profile-header">

                    <div class="profile-avatar">

                        <?php
                        echo strtoupper(
                            substr($user["name"], 0, 1)
                        );
                        ?>

                    </div>


                    <div>

                        <h2>
                            <?php
                            echo htmlspecialchars($user["name"]);
                            ?>
                        </h2>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $user["position"] ?: "Leader"
                            );
                            ?>
                        </p>

                    </div>

                </div>


                <!-- Profile Details -->

                <div class="profile-details">

                    <div class="profile-item">

                        <label>Full Name</label>

                        <p>
                            <?php
                            echo htmlspecialchars($user["name"]);
                            ?>
                        </p>

                    </div>


                    <div class="profile-item">

                        <label>Email</label>

                        <p>
                            <?php
                            echo htmlspecialchars($user["email"]);
                            ?>
                        </p>

                    </div>


                    <div class="profile-item">

                        <label>Username</label>

                        <p>
                            @<?php
                            echo htmlspecialchars($user["username"]);
                            ?>
                        </p>

                    </div>


                    <div class="profile-item">

                        <label>Position</label>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $user["position"] ?: "Leader"
                            );
                            ?>
                        </p>

                    </div>


                    <div class="profile-item">

                        <label>Role</label>

                        <p>

                            <span class="role-badge">
                                <?php
                                echo ucfirst(
                                    htmlspecialchars($user["role"])
                                );
                                ?>
                            </span>

                        </p>

                    </div>


                    <div class="profile-item">

                        <label>Joined Date</label>

                        <p>
                            <?php
                            echo date(
                                "d M Y",
                                strtotime($user["created_at"])
                            );
                            ?>
                        </p>

                    </div>

                </div>


                <!-- Buttons -->

                <div class="profile-actions">

                    <a
                        href="edit_profile.php"
                        class="edit-profile-btn"
                    >
                        ✏️ Edit Profile
                    </a>

                    <a
                        href="change_password.php"
                        class="password-btn"
                    >
                        🔑 Change Password
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>
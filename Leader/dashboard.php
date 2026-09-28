<?php
session_start();
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Leader Login Check
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {
    header("Location: ../login.php");
    exit;
}

$leaderId = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Dashboard Counts
|--------------------------------------------------------------------------
*/

/* Total Staff - only staff belonging to this leader */
$staffStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'worker'
     AND leader_id = ?"
);

$staffStmt->bind_param("i", $leaderId);
$staffStmt->execute();

$staffResult = $staffStmt->get_result();
$totalStaff = $staffResult->fetch_assoc()["total"];


/* Total Tasks - only tasks assigned by this leader */
$taskStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM tasks
     WHERE assigned_by = ?"
);

$taskStmt->bind_param("i", $leaderId);
$taskStmt->execute();

$taskResult = $taskStmt->get_result();
$totalTasks = $taskResult->fetch_assoc()["total"];


/* Pending Tasks - only this leader's tasks */
$pendingStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM tasks
     WHERE assigned_by = ?
     AND status = 'Pending'"
);

$pendingStmt->bind_param("i", $leaderId);
$pendingStmt->execute();

$pendingResult = $pendingStmt->get_result();
$pendingTasks = $pendingResult->fetch_assoc()["total"];


/* Completed Tasks - only this leader's tasks */
$completedStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM tasks
     WHERE assigned_by = ?
     AND status = 'Completed'"
);

$completedStmt->bind_param("i", $leaderId);
$completedStmt->execute();

$completedResult = $completedStmt->get_result();
$completedTasks = $completedResult->fetch_assoc()["total"];


/*
|--------------------------------------------------------------------------
| Recent Tasks
|--------------------------------------------------------------------------
*/

$recentStmt = $conn->prepare(
    "SELECT 
        tasks.*,
        users.name AS worker_name
     FROM tasks
     LEFT JOIN users
        ON tasks.assigned_to = users.id
     WHERE tasks.assigned_by = ?
     ORDER BY tasks.created_at DESC
     LIMIT 5"
);

$recentStmt->bind_param("i", $leaderId);
$recentStmt->execute();

$recentTasks = $recentStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Leader Dashboard</title>

    <link
        rel="stylesheet"
        href="../assets/css/leader.css"
    >

</head>

<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="logo">

            <h2>TaskFlow</h2>

            <p>Leader Panel</p>

        </div>


        <details class="main-menu">

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


                <a href="profile.php">

                    <span>👤</span>

                    <span>Profile</span>

                </a>


                <a
                    href="../logout.php"
                    class="logout-link"
                >

                    <span>🚪</span>

                    <span>Logout</span>

                </a>

            </div>

        </details>

    </aside>


    <!-- Main Content -->
    <main class="main-content">


        <!-- Topbar -->
        <header class="topbar">

            <div>

                <h1>Leader Dashboard</h1>

                <p>
                    Welcome back,
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["name"] ?? "Leader"
                    );
                    ?>!
                </p>

            </div>


            <div class="profile">

                <span>👤</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["name"] ?? "Leader"
                    );
                    ?>
                </strong>

            </div>

        </header>


        <!-- Dashboard Cards -->
        <section class="cards">


            <!-- Total Staff -->
            <div class="card staff-card">

                <div class="card-icon">
                    👥
                </div>

                <div>

                    <h3>Total Staff</h3>

                    <h2>
                        <?php echo $totalStaff; ?>
                    </h2>

                </div>

            </div>


            <!-- Total Tasks -->
            <div class="card task-card">

                <div class="card-icon">
                    📋
                </div>

                <div>

                    <h3>Total Tasks</h3>

                    <h2>
                        <?php echo $totalTasks; ?>
                    </h2>

                </div>

            </div>


            <!-- Pending Tasks -->
            <div class="card pending-card">

                <div class="card-icon">
                    ⏳
                </div>

                <div>

                    <h3>Pending Tasks</h3>

                    <h2>
                        <?php echo $pendingTasks; ?>
                    </h2>

                </div>

            </div>


            <!-- Completed Tasks -->
            <div class="card completed-card">

                <div class="card-icon">
                    ✅
                </div>

                <div>

                    <h3>Completed Tasks</h3>

                    <h2>
                        <?php echo $completedTasks; ?>
                    </h2>

                </div>

            </div>

        </section>


        <!-- Recent Tasks -->
        <section class="table-section">


            <div class="section-header">

                <h2>Recent Tasks</h2>


                <a
                    href="task_list.php"
                    class="view-btn"
                >
                    View All
                </a>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Task Title</th>

                            <th>Assigned To</th>

                            <th>Start Date</th>

                            <th>Due Date</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($recentTasks->num_rows > 0): ?>


                        <?php
                        while (
                            $task = $recentTasks->fetch_assoc()
                        ):
                        ?>


                            <tr>

                                <td>
                                    <?php
                                    echo $task["id"];
                                    ?>
                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $task["title"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $task["worker_name"]
                                        ?? "Not Assigned"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $task["start_date"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $task["due_date"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="status <?php
                                        echo strtolower(
                                            str_replace(
                                                " ",
                                                "-",
                                                $task["status"]
                                            )
                                        );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $task["status"]
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="6"
                                class="no-data"
                            >

                                No tasks found

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>


<?php
$staffStmt->close();
$taskStmt->close();
$pendingStmt->close();
$completedStmt->close();
$recentStmt->close();
?>

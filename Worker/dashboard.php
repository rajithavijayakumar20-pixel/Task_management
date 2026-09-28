<?php

session_start();
require_once "../config/database.php";

/* Worker Login Check */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "worker") {
    header("Location: ../login.php");
    exit;
}

$workerId = $_SESSION["user_id"];


/* Total Tasks */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
");

$stmt->bind_param("i", $workerId);
$stmt->execute();

$totalTasks = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* Pending Tasks */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND status = 'Pending'
");

$stmt->bind_param("i", $workerId);
$stmt->execute();

$pendingTasks = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* In Progress Tasks */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND status = 'In Progress'
");

$stmt->bind_param("i", $workerId);
$stmt->execute();

$progressTasks = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* Completed Tasks */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND status = 'Completed'
");

$stmt->bind_param("i", $workerId);
$stmt->execute();

$completedTasks = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* Overdue Tasks */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE assigned_to = ?
    AND status != 'Completed'
    AND due_date < CURDATE()
");

$stmt->bind_param("i", $workerId);
$stmt->execute();

$overdueTasks = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


/* =========================
   RECENT TASKS
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        start_date,
        due_date,
        status
    FROM tasks
    WHERE assigned_to = ?
    ORDER BY id DESC
    LIMIT 5
");

$stmt->bind_param("i", $workerId);
$stmt->execute();

$recentTasks = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Worker Dashboard - TaskFlow</title>

    <link
        rel="stylesheet"
        href="../assets/css/worker.css"
    >

</head>

<body>

<div class="dashboard-container">


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="logo">

            <h2>
                Task<span>Flow</span>
            </h2>

            <p>Worker Panel</p>

        </div>


        <!-- MAIN MENU -->

        <details class="main-menu" open>

            <summary>

                <span class="menu-icon">☰</span>

                <span class="menu-text">MENU</span>

                <span class="menu-arrow">▼</span>

            </summary>


            <div class="menu-list">

                <a
                    href="dashboard.php"
                    class="active"
                >
                    <span>🏠</span>
                    <span>Dashboard</span>
                </a>


                <a href="my_tasks.php">

                    <span>📋</span>

                    <span>My Tasks</span>

                </a>


                <a href="task_progress.php">

                    <span>📊</span>

                    <span>Task Progress</span>

                </a>


                <a href="warnings.php">

                    <span>⚠️</span>

                    <span>Warnings</span>

                </a>


                <a href="submit_task.php">

                    <span>📤</span>

                    <span>Submit Task</span>

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



    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <h3>
                    Worker Dashboard
                </h3>

            </div>


            <div class="user-info">

                Welcome,

                <?php
                echo htmlspecialchars(
                    $_SESSION["name"] ?? "Worker"
                );
                ?>

            </div>

        </header>



        <!-- =========================
             DASHBOARD CONTENT
        ========================== -->

        <section class="dashboard-content">


            <!-- Welcome -->

            <div class="welcome-box">

                <div>

                    <h2>
                        Welcome back,
                        <?php
                        echo htmlspecialchars(
                            $_SESSION["name"] ?? "Worker"
                        );
                        ?> 👋
                    </h2>

                    <p>
                        Here is your task summary.
                    </p>

                </div>

            </div>



            <!-- =========================
                 STAT CARDS
            ========================== -->

            <div class="stats-grid">


                <!-- Total -->

                <div class="stat-card">

                    <div class="stat-icon total-icon">
                        📋
                    </div>

                    <div>

                        <p>Total Tasks</p>

                        <h3>
                            <?php echo $totalTasks; ?>
                        </h3>

                    </div>

                </div>


                <!-- Pending -->

                <div class="stat-card">

                    <div class="stat-icon pending-icon">
                        ⏳
                    </div>

                    <div>

                        <p>Pending</p>

                        <h3>
                            <?php echo $pendingTasks; ?>
                        </h3>

                    </div>

                </div>


                <!-- In Progress -->

                <div class="stat-card">

                    <div class="stat-icon progress-icon">
                        🔄
                    </div>

                    <div>

                        <p>In Progress</p>

                        <h3>
                            <?php echo $progressTasks; ?>
                        </h3>

                    </div>

                </div>


                <!-- Completed -->

                <div class="stat-card">

                    <div class="stat-icon completed-icon">
                        ✓
                    </div>

                    <div>

                        <p>Completed</p>

                        <h3>
                            <?php echo $completedTasks; ?>
                        </h3>

                    </div>

                </div>


                <!-- Overdue -->

                <div class="stat-card overdue-card">

                    <div class="stat-icon overdue-icon">
                        ⚠️
                    </div>

                    <div>

                        <p>Overdue</p>

                        <h3>
                            <?php echo $overdueTasks; ?>
                        </h3>

                    </div>

                </div>

            </div>



            <!-- =========================
                 RECENT TASKS
            ========================== -->

            <div class="task-card">

                <div class="task-card-header">

                    <div>

                        <h2>
                            Recent Tasks
                        </h2>

                        <p>
                            Your latest assigned tasks
                        </p>

                    </div>


                    <a href="my_tasks.php">
                        View All
                    </a>

                </div>


                <?php if ($recentTasks->num_rows > 0): ?>

                    <div class="task-table-container">

                        <table class="task-table">

                            <thead>

                                <tr>

                                    <th>
                                        Task
                                    </th>

                                    <th>
                                        Start Date
                                    </th>

                                    <th>
                                        Due Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php while (
                                $task = $recentTasks->fetch_assoc()
                            ): ?>

                                <?php

                                $status = $task["status"];

                                $statusClass = "pending";

                                $displayStatus = $status;


                                if ($status === "In Progress") {

                                    $statusClass = "progress";

                                } elseif ($status === "Completed") {

                                    $statusClass = "completed";

                                } elseif (
                                    $status !== "Completed"
                                    &&
                                    $task["due_date"] < date("Y-m-d")
                                ) {

                                    $statusClass = "overdue";

                                    $displayStatus = "Overdue";

                                }

                                ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $task["title"]
                                            );
                                            ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $task["start_date"]
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $task["due_date"]
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="status <?php echo $statusClass; ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $displayStatus
                                            );
                                            ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-task">

                        <div>
                            📋
                        </div>

                        <h3>
                            No Tasks Yet
                        </h3>

                        <p>
                            No tasks have been assigned to you yet.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


        </section>

    </main>

</div>

</body>

</html>

<?php
$stmt->close();
?>
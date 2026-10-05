<?php
session_start();
require_once "../config/database.php";

/* Leader Login Check */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {
    header("Location: ../login.php");
    exit;
}

$leaderId = $_SESSION["user_id"];

/*
    Get overdue tasks assigned by this leader
*/
$sql = "
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.start_date,
        tasks.due_date,
        tasks.status,
        users.name AS worker_name,
        users.username AS worker_username
    FROM tasks
    INNER JOIN users
        ON tasks.assigned_to = users.id
    WHERE tasks.assigned_by = ?
      AND tasks.status != 'Completed'
      AND tasks.due_date < CURDATE()
    ORDER BY tasks.due_date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leaderId);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Warnings - Leader</title>

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


        <!-- MAIN MENU -->

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

                <a href="warnings.php" class="active">
                    <span>⚠️</span>
                    <span>Warnings</span>
                </a>

                <a href="profile.php">
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

            <div>

                <h3>Warnings</h3>

            </div>

            <div class="user-info">

                Welcome,
                <?php
                echo htmlspecialchars(
                    $_SESSION["name"] ?? "Leader"
                );
                ?>

            </div>

        </header>


        <!-- WARNING CONTENT -->

        <section class="warning-section">

            <div class="warning-header">

                <div>

                    <h2>Task Warnings</h2>

                    <p>
                        Tasks that have passed their due date.
                    </p>

                </div>


                <div class="warning-count">

                    <?php echo $result->num_rows; ?>

                    <span>Overdue</span>

                </div>

            </div>


            <?php if ($result->num_rows > 0): ?>

                <div class="warning-list">

                    <?php while ($task = $result->fetch_assoc()): ?>

                        <?php

                        $today = new DateTime();

                        $dueDate = new DateTime($task["due_date"]);

                        $daysLate = $dueDate->diff($today)->days;

                        ?>

                        <div class="warning-card">

                            <div class="warning-icon">
                                ⚠️
                            </div>


                            <div class="warning-content">

                                <h3>
                                    <?php
                                    echo htmlspecialchars(
                                        $task["title"]
                                    );
                                    ?>
                                </h3>


                                <p class="warning-worker">

                                    Assigned to:
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $task["worker_name"]
                                        );
                                        ?>
                                    </strong>

                                    (@<?php
                                    echo htmlspecialchars(
                                        $task["worker_username"]
                                    );
                                    ?>)

                                </p>


                                <div class="warning-details">

                                    <span>
                                        📅 Due:
                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $task["due_date"]
                                            )
                                        );
                                        ?>
                                    </span>


                                    <span>
                                        ⏰
                                        <?php
                                        echo $daysLate;
                                        ?>
                                        day(s) overdue
                                    </span>


                                    <span class="task-status">
                                        <?php
                                        echo htmlspecialchars(
                                            $task["status"]
                                        );
                                        ?>
                                    </span>

                                </div>

                            </div>


                            <div class="warning-action">

                               <a href="view_task.php?id=<?php echo $task['id']; ?>" class="view-task-btn">
    View Task
</a>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="no-warning">

                    <div class="success-icon">
                        ✓
                    </div>

                    <h3>No Warnings</h3>

                    <p>
                        Great! There are no overdue tasks at the moment.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>

<?php
$stmt->close();
?>
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

/* Get Tasks */
$sql = "
    SELECT 
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.start_date,
        tasks.due_date,
        tasks.status,
        tasks.created_at,
        users.name AS worker_name,
        users.username AS worker_username
    FROM tasks
    INNER JOIN users 
        ON tasks.assigned_to = users.id
    WHERE tasks.assigned_by = ?
    ORDER BY tasks.id DESC
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

    <title>Task List - Leader</title>

    <link rel="stylesheet" href="../assets/css/leader.css">

</head>

<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
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

        <a href="../logout.php" class="logout-link">
            <span>🚪</span>
            <span>Logout</span>
        </a>

    </div>


    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP BAR -->
        <header class="topbar">

            <div>
                <h3>Task List</h3>
            </div>

            <div class="user-info">

                <span>
                    Welcome,
                    <?php echo htmlspecialchars($_SESSION["name"] ?? "Leader"); ?>
                </span>

            </div>

        </header>


        <!-- CONTENT -->
        <section class="content-card">

            <div class="page-title">

                <h2>Assigned Tasks</h2>

                <p>
                    View and monitor all tasks assigned by you.
                </p>

            </div>


            <?php if ($result->num_rows > 0): ?>

                <div class="table-container">

                    <table class="task-table">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Start Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php
                        $count = 1;

                        while ($task = $result->fetch_assoc()):

                            $status = $task["status"];

                            /* Check overdue */
                            $today = date("Y-m-d");

                            if (
                                $status !== "Completed" &&
                                $task["due_date"] < $today
                            ) {
                                $statusClass = "overdue";
                                $displayStatus = "Overdue";
                            } 
                            elseif ($status === "In Progress") {
                                $statusClass = "progress";
                                $displayStatus = "In Progress";
                            } 
                            elseif ($status === "Completed") {
                                $statusClass = "completed";
                                $displayStatus = "Completed";
                            } 
                            else {
                                $statusClass = "pending";
                                $displayStatus = "Pending";
                            }
                        ?>

                            <tr>

                                <td>
                                    <?php echo $count++; ?>
                                </td>

                                <td>

                                    <div class="task-title">
                                        <?php
                                        echo htmlspecialchars($task["title"]);
                                        ?>
                                    </div>

                                </td>

                                <td>

                                    <div class="worker-name">
                                        <?php
                                        echo htmlspecialchars($task["worker_name"]);
                                        ?>
                                    </div>

                                    <span class="worker-username">
                                        @<?php
                                        echo htmlspecialchars($task["worker_username"]);
                                        ?>
                                    </span>

                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime($task["start_date"])
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime($task["due_date"])
                                    );
                                    ?>
                                </td>

                                <td>

                                    <span class="status <?php echo $statusClass; ?>">
                                        <?php echo $displayStatus; ?>
                                    </span>

                                </td>

                                <td>

                                    <a
                                        href="view_task.php?id=<?php echo $task["id"]; ?>"
                                        class="view-btn"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-box">

                    <h3>No Tasks Found</h3>

                    <p>
                        You have not assigned any tasks yet.
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
<?php

session_start();

require_once "../config/database.php";


/* =========================================
   LEADER LOGIN CHECK
========================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {
    header("Location: ../login.php");
    exit;
}


/* =========================================
   CURRENT LEADER
========================================= */

$leaderId = $_SESSION["user_id"];


/* =========================================
   GET TASK ID
========================================= */

$taskId = intval($_GET["id"] ?? 0);

if ($taskId <= 0) {
    header("Location: warnings.php");
    exit;
}


/* =========================================
   GET TASK DETAILS
========================================= */

$stmt = $conn->prepare("
    SELECT
        t.id,
        t.title,
        t.description,
        t.start_date,
        t.due_date,
        t.status,
        t.assigned_by,
        t.assigned_to,
        u.name AS worker_name,
        u.username AS worker_username,
        u.email AS worker_email
    FROM tasks t
    INNER JOIN users u
        ON t.assigned_to = u.id
    WHERE t.id = ?
      AND t.assigned_by = ?
");

$stmt->bind_param(
    "ii",
    $taskId,
    $leaderId
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();

    header("Location: warnings.php");
    exit;
}

$task = $result->fetch_assoc();

$stmt->close();


/* =========================================
   CHECK OVERDUE
========================================= */

$isOverdue = false;

if (
    $task["status"] !== "Completed" &&
    strtotime($task["due_date"]) < time()
) {
    $isOverdue = true;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Task - Leader</title>

    <link rel="stylesheet" href="../assets/css/leader.css">

    <style>

        .task-details-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
        }

        .task-header h2 {
            margin: 0;
            color: #172554;
        }

        .task-status {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .pending {
            background: #fff7ed;
            color: #ea580c;
        }

        .progress {
            background: #eff6ff;
            color: #2563eb;
        }

        .completed {
            background: #ecfdf5;
            color: #16a34a;
        }

        .overdue {
            background: #fef2f2;
            color: #dc2626;
        }

        .task-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-box {
            background: #f8fafc;
            padding: 18px;
            border-radius: 10px;
        }

        .info-box.full {
            grid-column: 1 / -1;
        }

        .info-box label {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .info-box strong {
            color: #172554;
            font-size: 14px;
        }

        .description {
            color: #334155;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .overdue-message {
            margin-bottom: 20px;
            padding: 15px 18px;
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            color: #991b1b;
        }

        .back-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 18px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
        }

        .back-btn:hover {
            background: #1d4ed8;
        }

        @media (max-width: 700px) {

            .task-info {
                grid-template-columns: 1fr;
            }

            .info-box.full {
                grid-column: auto;
            }

            .task-header {
                flex-direction: column;
                align-items: flex-start;
            }

        }

    </style>

</head>

<body>

<div class="dashboard-container">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">

            <h2>TaskFlow</h2>

            <p>Leader Panel</p>

        </div>


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


        <header class="topbar">

            <div>

                <h3>View Task</h3>

            </div>

            <div class="user-info">

                Welcome,
                <?php echo htmlspecialchars($_SESSION["name"] ?? "Leader"); ?>

            </div>

        </header>


        <section class="content-card">

            <div class="task-details-card">


                <div class="task-header">

                    <h2>
                        <?php echo htmlspecialchars($task["title"]); ?>
                    </h2>


                    <?php if ($isOverdue): ?>

                        <span class="task-status overdue">
                            Overdue
                        </span>

                    <?php elseif (strtolower($task["status"]) === "completed"): ?>

                        <span class="task-status completed">
                            Completed
                        </span>

                    <?php elseif (strtolower($task["status"]) === "in progress"): ?>

                        <span class="task-status progress">
                            In Progress
                        </span>

                    <?php else: ?>

                        <span class="task-status pending">
                            <?php echo htmlspecialchars($task["status"]); ?>
                        </span>

                    <?php endif; ?>

                </div>


                <?php if ($isOverdue): ?>

                    <div class="overdue-message">

                        ⚠️ <strong>Task Overdue!</strong>

                        This task has passed its due date and is not yet completed.

                    </div>

                <?php endif; ?>


                <div class="task-info">


                    <!-- Worker -->

                    <div class="info-box">

                        <label>Assigned To</label>

                        <strong>
                            <?php echo htmlspecialchars($task["worker_name"]); ?>
                        </strong>

                        <small>
                            @<?php echo htmlspecialchars($task["worker_username"]); ?>
                        </small>

                    </div>


                    <!-- Email -->

                    <div class="info-box">

                        <label>Worker Email</label>

                        <strong>
                            <?php echo htmlspecialchars($task["worker_email"]); ?>
                        </strong>

                    </div>


                    <!-- Start Date -->

                    <div class="info-box">

                        <label>Start Date</label>

                        <strong>
                            <?php
                            echo date(
                                "d M Y",
                                strtotime($task["start_date"])
                            );
                            ?>
                        </strong>

                    </div>


                    <!-- Due Date -->

                    <div class="info-box">

                        <label>Due Date</label>

                        <strong>
                            <?php
                            echo date(
                                "d M Y",
                                strtotime($task["due_date"])
                            );
                            ?>
                        </strong>

                    </div>


                    <!-- Description -->

                    <div class="info-box full">

                        <label>Task Description</label>

                        <div class="description">

                            <?php
                            echo htmlspecialchars(
                                $task["description"]
                            );
                            ?>

                        </div>

                    </div>


                </div>


                <a href="warnings.php" class="back-btn">
                    ← Back to Warnings
                </a>


            </div>

        </section>


    </main>

</div>

</body>

</html>
<?php
session_start();

require_once "../config/database.php";

/* =========================
   WORKER LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "worker") {
    header("Location: ../login.php");
    exit;
}

$workerId = $_SESSION["user_id"];


/* =========================
   GET WORKER TASKS
========================= */

$sql = "
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.start_date,
        tasks.due_date,
        tasks.status,
        tasks.created_at,
        users.name AS leader_name
    FROM tasks
    INNER JOIN users
        ON tasks.assigned_by = users.id
    WHERE tasks.assigned_to = ?
    ORDER BY tasks.id DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $workerId);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Tasks - Worker</title>

    <link
        rel="stylesheet"
        href="../assets/css/worker.css"
    >

    <style>

        /* =========================
           TASK PAGE
        ========================= */

        .tasks-container {
            padding: 25px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0;
            color: #1e293b;
        }

        .page-heading p {
            margin-top: 8px;
            color: #64748b;
        }


        /* =========================
           TASK GRID
        ========================= */

        .task-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(300px, 1fr)
            );

            gap: 20px;
        }


        /* =========================
           TASK CARD
        ========================= */

        .task-card {
            background: white;
            border-radius: 14px;
            padding: 22px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.07);

            border-left: 5px solid #2563eb;
        }

        .task-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #1e293b;
        }

        .task-description {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 18px;
        }


        /* =========================
           TASK INFO
        ========================= */

        .task-info {
            display: grid;
            gap: 10px;
            margin-bottom: 18px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;

            padding: 9px 0;

            border-bottom: 1px solid #e2e8f0;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            color: #1e293b;
            text-align: right;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }


        /* =========================
           OVERDUE
        ========================= */

        .overdue {
            color: #dc2626;
            font-weight: 700;
        }


        /* =========================
           BUTTON
        ========================= */

        .task-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .task-btn {
            display: inline-block;

            padding: 10px 15px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;
        }

        .progress-btn {
            background: #2563eb;
            color: white;
        }

        .submit-btn {
            background: #16a34a;
            color: white;
        }

        .task-btn:hover {
            opacity: 0.9;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty-box {
            background: white;
            padding: 50px 20px;

            text-align: center;

            border-radius: 14px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.06);
        }

        .empty-box h3 {
            color: #334155;
        }

        .empty-box p {
            color: #64748b;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .tasks-container {
                padding: 15px;
            }

            .task-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                flex-direction: column;
            }

            .info-value {
                text-align: left;
            }

            .task-actions {
                flex-direction: column;
            }

            .task-btn {
                text-align: center;
            }

        }

    </style>

</head>


<body>


<div class="dashboard-container">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">


        <div class="logo">

            <h2>TaskFlow</h2>

            <p>Worker Panel</p>

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


                <a
                    href="my_tasks.php"
                    class="active"
                >

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
    ========================= -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">


            <div>

                <h3>My Tasks</h3>

                <p>
                    View your assigned tasks
                </p>

            </div>


            <div class="user-info">

                Welcome,

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["name"] ?? "Worker"
                    );

                    ?>

                </strong>

            </div>


        </header>



        <!-- =========================
             TASK CONTENT
        ========================= -->

        <section class="tasks-container">


            <div class="page-heading">

                <h1>📋 My Tasks</h1>

                <p>
                    These are the tasks assigned to you.
                </p>

            </div>



            <?php if ($result->num_rows > 0): ?>


                <div class="task-grid">


                <?php while ($task = $result->fetch_assoc()): ?>


                    <?php

                    /* Status class */

                    $statusClass = "";

                    if ($task["status"] === "Pending") {

                        $statusClass = "status-pending";

                    } elseif (
                        $task["status"] === "In Progress"
                    ) {

                        $statusClass = "status-progress";

                    } elseif (
                        $task["status"] === "Completed"
                    ) {

                        $statusClass = "status-completed";
                    }


                    /* Overdue check */

                    $isOverdue = (
                        $task["status"] !== "Completed"
                        &&
                        strtotime($task["due_date"])
                        < strtotime(date("Y-m-d"))
                    );

                    ?>


                    <div class="task-card">


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $task["title"]
                            );

                            ?>

                        </h3>


                        <div class="task-description">

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $task["description"]
                                    ?: "No description available."
                                )
                            );

                            ?>

                        </div>



                        <div class="task-info">


                            <!-- LEADER -->

                            <div class="info-row">

                                <span class="info-label">
                                    Assigned By
                                </span>

                                <span class="info-value">

                                    <?php

                                    echo htmlspecialchars(
                                        $task["leader_name"]
                                    );

                                    ?>

                                </span>

                            </div>


                            <!-- START DATE -->

                            <div class="info-row">

                                <span class="info-label">
                                    Start Date
                                </span>

                                <span class="info-value">

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $task["start_date"]
                                        )
                                    );

                                    ?>

                                </span>

                            </div>


                            <!-- DUE DATE -->

                            <div class="info-row">

                                <span class="info-label">
                                    Due Date
                                </span>

                                <span
                                    class="info-value
                                    <?php
                                    echo $isOverdue
                                        ? 'overdue'
                                        : '';
                                    ?>"
                                >

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $task["due_date"]
                                        )
                                    );

                                    ?>


                                    <?php if ($isOverdue): ?>

                                        ⚠️ Overdue

                                    <?php endif; ?>


                                </span>

                            </div>


                            <!-- STATUS -->

                            <div class="info-row">

                                <span class="info-label">
                                    Status
                                </span>

                                <span class="info-value">

                                    <span
                                        class="status
                                        <?php
                                        echo $statusClass;
                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $task["status"]
                                        );

                                        ?>

                                    </span>

                                </span>

                            </div>


                        </div>



                        <!-- ACTIONS -->

                        <div class="task-actions">


                            <a
                                href="task_progress.php?task_id=<?php echo $task["id"]; ?>"
                                class="task-btn progress-btn"
                            >

                                📊 Progress

                            </a>


                            <?php if (
                                $task["status"] !== "Completed"
                            ): ?>

                                <a
                                    href="submit_task.php?task_id=<?php echo $task["id"]; ?>"
                                    class="task-btn submit-btn"
                                >

                                    📤 Submit

                                </a>

                            <?php endif; ?>


                        </div>


                    </div>


                <?php endwhile; ?>


                </div>


            <?php else: ?>


                <div class="empty-box">


                    <h3>📋 No Tasks Found</h3>


                    <p>
                        No tasks have been assigned to you yet.
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
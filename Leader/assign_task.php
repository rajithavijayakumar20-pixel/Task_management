<?php

session_start();

require_once "../config/database.php";

$message = "";
$messageType = "";


/* ==============================
   CHECK LOGIN
============================== */

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");
    exit;
}


/* ==============================
   CHECK LEADER ROLE
============================== */

if (strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {

    header("Location: ../login.php");
    exit;
}


$leaderId = $_SESSION["user_id"];


/* ==============================
   ASSIGN TASK
============================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $assignedTo = $_POST["assigned_to"] ?? "";
    $startDate = $_POST["start_date"] ?? "";
    $dueDate = $_POST["due_date"] ?? "";


    /* ==============================
       VALIDATION
    ============================== */

    if (
        empty($title) ||
        empty($description) ||
        empty($assignedTo) ||
        empty($startDate) ||
        empty($dueDate)
    ) {

        $message = "Please fill all required fields.";
        $messageType = "error";

    } elseif ($dueDate < $startDate) {

        $message = "Due date cannot be before start date.";
        $messageType = "error";

    } else {


        /* ==============================
           CHECK WORKER
        ============================== */

        $workerCheck = $conn->prepare(
            "SELECT id
             FROM users
             WHERE id = ?
             AND role = 'worker'"
        );


        if (!$workerCheck) {

            $message = "Worker check error: " . $conn->error;
            $messageType = "error";

        } else {

            $workerCheck->bind_param(
                "i",
                $assignedTo
            );

            $workerCheck->execute();

            $workerResult = $workerCheck->get_result();


            if ($workerResult->num_rows == 0) {

                $message = "Selected worker does not exist.";
                $messageType = "error";

            } else {


                /* ==============================
                   INSERT TASK
                ============================== */

                $stmt = $conn->prepare(
                    "INSERT INTO tasks
                    (
                        title,
                        description,
                        assigned_by,
                        assigned_to,
                        start_date,
                        due_date,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
                );


                if (!$stmt) {

                    $message = "Prepare Error: " . $conn->error;
                    $messageType = "error";

                } else {

                    $stmt->bind_param(
                        "ssiiss",
                        $title,
                        $description,
                        $leaderId,
                        $assignedTo,
                        $startDate,
                        $dueDate
                    );


                    if ($stmt->execute()) {

                        $message = "Task assigned successfully!";
                        $messageType = "success";

                    } else {

                        $message = "Database Error: " . $stmt->error;
                        $messageType = "error";
                    }


                    $stmt->close();
                }
            }


            $workerCheck->close();
        }
    }
}


/* ==============================
   GET WORKERS
============================== */

$workers = [];


$workerQuery = $conn->query(
    "SELECT id, name, username, position
     FROM users
     WHERE role = 'worker'
     ORDER BY name ASC"
);


if ($workerQuery) {

    while ($worker = $workerQuery->fetch_assoc()) {

        $workers[] = $worker;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Assign Task - TaskFlow</title>

    <link
        rel="stylesheet"
        href="../assets/css/leader.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- ==============================
         SIDEBAR
    =============================== -->

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



    <!-- ==============================
         MAIN CONTENT
    =============================== -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">


            <div>

                <h1>Assign Task</h1>

                <p>
                    Assign a new task to your team member
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



        <!-- ==============================
             FORM SECTION
        =============================== -->

        <section class="form-section">


            <div class="form-header">

                <h2>Task Information</h2>

                <p>
                    Enter the task details and assign it to a worker.
                </p>

            </div>



            <!-- MESSAGE -->

            <?php if (!empty($message)): ?>

                <div
                    class="message <?php echo $messageType; ?>"
                >

                    <?php

                    echo htmlspecialchars($message);

                    ?>

                </div>

            <?php endif; ?>



            <!-- FORM -->

            <form
                method="POST"
                action=""
            >


                <div class="form-grid">


                    <!-- TASK TITLE -->

                    <div class="form-group full-width">


                        <label for="title">

                            Task Title

                            <span>*</span>

                        </label>


                        <input
                            type="text"
                            id="title"
                            name="title"
                            placeholder="Enter task title"
                            required
                        >


                    </div>



                    <!-- DESCRIPTION -->

                    <div class="form-group full-width">


                        <label for="description">

                            Task Description

                            <span>*</span>

                        </label>


                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            placeholder="Enter task description"
                            required
                        ></textarea>


                    </div>



                    <!-- ASSIGN TO -->

                    <div class="form-group full-width">


                        <label for="assigned_to">

                            Assign To

                            <span>*</span>

                        </label>


                        <select
                            id="assigned_to"
                            name="assigned_to"
                            required
                        >


                            <option value="">

                                Select Worker

                            </option>


                            <?php foreach ($workers as $worker): ?>


                                <option
                                    value="<?php
                                        echo $worker["id"];
                                    ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $worker["name"]
                                    );

                                    ?>

                                    -

                                    <?php

                                    echo htmlspecialchars(
                                        $worker["username"]
                                    );

                                    ?>

                                </option>


                            <?php endforeach; ?>


                        </select>



                        <?php if (empty($workers)): ?>


                            <small class="form-note">

                                No workers found.
                                Please add a worker first.

                            </small>


                        <?php endif; ?>


                    </div>



                    <!-- START DATE -->

                    <div class="form-group">


                        <label for="start_date">

                            Start Date

                            <span>*</span>

                        </label>


                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            required
                        >


                    </div>



                    <!-- DUE DATE -->

                    <div class="form-group">


                        <label for="due_date">

                            Due Date

                            <span>*</span>

                        </label>


                        <input
                            type="date"
                            id="due_date"
                            name="due_date"
                            required
                        >


                    </div>


                </div>



                <!-- BUTTONS -->

                <div class="form-actions">


                    <button
                        type="reset"
                        class="reset-btn"
                    >

                        Reset

                    </button>


                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        📝 Assign Task

                    </button>


                </div>


            </form>


        </section>


    </main>


</div>


</body>

</html>
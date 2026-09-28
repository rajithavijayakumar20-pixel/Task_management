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

$message = "";
$messageType = "";

$taskId = intval($_GET["task_id"] ?? $_POST["task_id"] ?? 0);


/* =========================
   TASK CHECK
   Worker-க்கு assign ஆன task மட்டும்
========================= */

$task = null;

if ($taskId > 0) {

    $taskStmt = $conn->prepare("
        SELECT
            tasks.id,
            tasks.title,
            tasks.description,
            tasks.start_date,
            tasks.due_date,
            tasks.status,
            users.name AS leader_name
        FROM tasks
        INNER JOIN users
            ON tasks.assigned_by = users.id
        WHERE tasks.id = ?
        AND tasks.assigned_to = ?
    ");

    $taskStmt->bind_param(
        "ii",
        $taskId,
        $workerId
    );

    $taskStmt->execute();

    $taskResult = $taskStmt->get_result();

    if ($taskResult->num_rows === 1) {
        $task = $taskResult->fetch_assoc();
    }

    $taskStmt->close();
}


/* =========================
   FORM SUBMIT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $comment = trim($_POST["comment"] ?? "");


    /* Task exists and belongs to worker */

    if (!$task) {

        $message = "Invalid task or this task is not assigned to you.";
        $messageType = "error";

    } elseif ($task["status"] === "Completed") {

        $message = "This task is already completed.";
        $messageType = "error";

    } else {

        $fileName = "";
        $filePath = "";


        /* =========================
           FILE UPLOAD
        ========================= */

        if (
            isset($_FILES["task_file"]) &&
            $_FILES["task_file"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES["task_file"]["error"] !== UPLOAD_ERR_OK) {

                $message = "File upload failed.";
                $messageType = "error";

            } else {

                $originalName = $_FILES["task_file"]["name"];
                $tmpName = $_FILES["task_file"]["tmp_name"];
                $fileSize = $_FILES["task_file"]["size"];

                /* Allowed extensions */

                $allowedExtensions = [
                    "pdf",
                    "doc",
                    "docx",
                    "xls",
                    "xlsx",
                    "jpg",
                    "jpeg",
                    "png",
                    "zip"
                ];

                $extension = strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );


                /* Maximum 5 MB */

                $maxSize = 5 * 1024 * 1024;


                if (!in_array($extension, $allowedExtensions)) {

                    $message =
                        "Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP.";

                    $messageType = "error";

                } elseif ($fileSize > $maxSize) {

                    $message =
                        "File size must be less than 5 MB.";

                    $messageType = "error";

                } else {

                    /* Create upload folder */

                    $uploadDir = "../uploads/tasks/";

                    if (!is_dir($uploadDir)) {
                        mkdir(
                            $uploadDir,
                            0777,
                            true
                        );
                    }


                    /* Unique file name */

                    $fileName =
                        time()
                        . "_"
                        . $workerId
                        . "_"
                        . uniqid()
                        . "."
                        . $extension;


                    $filePath =
                        "uploads/tasks/"
                        . $fileName;


                    $fullPath =
                        "../"
                        . $filePath;


                    if (!move_uploaded_file(
                        $tmpName,
                        $fullPath
                    )) {

                        $message =
                            "Could not save uploaded file.";

                        $messageType = "error";

                    }
                }
            }
        }


        /* =========================
           SAVE SUBMISSION
        ========================= */

        if ($message === "") {

            $submitStmt = $conn->prepare("
                INSERT INTO task_submissions
                (
                    task_id,
                    worker_id,
                    file_name,
                    file_path,
                    comment
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $submitStmt->bind_param(
                "iisss",
                $taskId,
                $workerId,
                $fileName,
                $filePath,
                $comment
            );


            if ($submitStmt->execute()) {

                /* =========================
                   UPDATE TASK STATUS
                ========================= */

                $updateStmt = $conn->prepare("
                    UPDATE tasks
                    SET status = 'Completed'
                    WHERE id = ?
                    AND assigned_to = ?
                ");

                $updateStmt->bind_param(
                    "ii",
                    $taskId,
                    $workerId
                );

                $updateStmt->execute();

                $updateStmt->close();


                $message =
                    "Task submitted successfully!";

                $messageType = "success";


                /* Reload task */

                $task["status"] = "Completed";

            } else {

                $message =
                    "Failed to submit task.";

                $messageType = "error";
            }

            $submitStmt->close();
        }
    }
}


/* =========================
   GET ALL WORKER TASKS
   Dropdown
========================= */

$tasksStmt = $conn->prepare("
    SELECT
        id,
        title,
        due_date,
        status
    FROM tasks
    WHERE assigned_to = ?
    ORDER BY id DESC
");

$tasksStmt->bind_param(
    "i",
    $workerId
);

$tasksStmt->execute();

$tasksResult = $tasksStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Submit Task - Worker</title>

    <link
        rel="stylesheet"
        href="../assets/css/worker.css"
    >


    <style>

        /* =========================
           PAGE
        ========================= */

        .submit-container {
            padding: 30px;
            max-width: 900px;
            margin: auto;
        }


        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0;
            color: #1e293b;
        }

        .page-heading p {
            color: #64748b;
            margin-top: 8px;
        }


        /* =========================
           MESSAGE
        ========================= */

        .message {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }


        /* =========================
           FORM CARD
        ========================= */

        .submit-card {
            background: white;
            padding: 30px;
            border-radius: 15px;

            box-shadow:
                0 5px 25px rgba(0, 0, 0, 0.08);
        }


        /* =========================
           FORM GROUP
        ========================= */

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
        }


        .form-group select,
        .form-group textarea,
        .form-group input[type="file"] {

            width: 100%;

            padding: 12px;

            border: 1px solid #cbd5e1;

            border-radius: 8px;

            font-size: 15px;

            background: white;
        }


        .form-group select:focus,
        .form-group textarea:focus {

            outline: none;

            border-color: #2563eb;

        }


        textarea {
            min-height: 140px;
            resize: vertical;
        }


        /* =========================
           TASK DETAILS
        ========================= */

        .task-details {

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 25px;
        }

        .task-details h3 {
            margin-top: 0;
            color: #1e293b;
        }

        .detail-row {

            display: flex;

            justify-content: space-between;

            padding: 9px 0;

            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #64748b;
            font-weight: 600;
        }

        .detail-value {
            color: #1e293b;
        }


        /* =========================
           BUTTON
        ========================= */

        .submit-btn {

            border: none;

            background: #16a34a;

            color: white;

            padding: 13px 25px;

            border-radius: 8px;

            font-size: 15px;

            font-weight: 600;

            cursor: pointer;
        }

        .submit-btn:hover {
            background: #15803d;
        }


        /* =========================
           BACK BUTTON
        ========================= */

        .back-btn {

            display: inline-block;

            margin-left: 10px;

            padding: 13px 25px;

            background: #e2e8f0;

            color: #334155;

            text-decoration: none;

            border-radius: 8px;

            font-weight: 600;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .submit-container {
                padding: 15px;
            }

            .submit-card {
                padding: 20px;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .back-btn {
                margin-left: 0;
                margin-top: 10px;
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


                <a
                    href="submit_task.php"
                    class="active"
                >

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


        <header class="topbar">

            <div>

                <h3>Submit Task</h3>

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
             SUBMIT CONTENT
        ========================= -->

        <section class="submit-container">


            <div class="page-heading">

                <h1>📤 Submit Task</h1>

                <p>
                    Submit your completed task.
                </p>

            </div>


            <!-- MESSAGE -->

            <?php if ($message !== ""): ?>

                <div
                    class="message
                    <?php echo htmlspecialchars($messageType); ?>"
                >

                    <?php

                    echo htmlspecialchars($message);

                    ?>

                </div>

            <?php endif; ?>



            <div class="submit-card">


                <!-- =========================
                     TASK SELECT
                ========================= -->

                <form
                    method="POST"
                    action="submit_task.php"
                    enctype="multipart/form-data"
                >


                    <div class="form-group">

                        <label for="task_id">
                            Select Task *
                        </label>


                        <select
                            name="task_id"
                            id="task_id"
                            required
                            onchange="changeTask(this.value)"
                        >

                            <option value="">
                                -- Select Task --
                            </option>


                            <?php while (
                                $taskOption =
                                $tasksResult->fetch_assoc()
                            ): ?>


                                <option
                                    value="<?php
                                        echo $taskOption["id"];
                                    ?>"
                                    <?php
                                    echo (
                                        $taskId ==
                                        $taskOption["id"]
                                    )
                                    ? "selected"
                                    : "";
                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $taskOption["title"]
                                    );

                                    ?>

                                    -
                                    <?php

                                    echo htmlspecialchars(
                                        $taskOption["status"]
                                    );

                                    ?>

                                </option>


                            <?php endwhile; ?>


                        </select>

                    </div>



                    <!-- =========================
                         SELECTED TASK DETAILS
                    ========================= -->

                    <?php if ($task): ?>


                        <div class="task-details">


                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $task["title"]
                                );

                                ?>

                            </h3>


                            <div class="detail-row">

                                <span class="detail-label">
                                    Assigned By
                                </span>

                                <span class="detail-value">

                                    <?php

                                    echo htmlspecialchars(
                                        $task["leader_name"]
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="detail-row">

                                <span class="detail-label">
                                    Start Date
                                </span>

                                <span class="detail-value">

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


                            <div class="detail-row">

                                <span class="detail-label">
                                    Due Date
                                </span>

                                <span class="detail-value">

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $task["due_date"]
                                        )
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="detail-row">

                                <span class="detail-label">
                                    Status
                                </span>

                                <span class="detail-value">

                                    <?php

                                    echo htmlspecialchars(
                                        $task["status"]
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="detail-row">

                                <span class="detail-label">
                                    Description
                                </span>

                                <span class="detail-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $task["description"]
                                            ?: "No description."
                                        )
                                    );

                                    ?>

                                </span>

                            </div>


                        </div>


                    <?php endif; ?>



                    <!-- =========================
                         COMMENT
                    ========================= -->

                    <div class="form-group">

                        <label for="comment">
                            Comment
                        </label>


                        <textarea
                            name="comment"
                            id="comment"
                            placeholder="Write your submission comment..."
                        ></textarea>

                    </div>



                    <!-- =========================
                         FILE
                    ========================= -->

                    <div class="form-group">

                        <label for="task_file">
                            Upload File
                        </label>


                        <input
                            type="file"
                            name="task_file"
                            id="task_file"
                        >


                        <small>
                            Maximum file size: 5 MB
                        </small>

                    </div>



                    <!-- =========================
                         BUTTONS
                    ========================= -->

                    <div>

                        <button
                            type="submit"
                            class="submit-btn"
                        >

                            📤 Submit Task

                        </button>


                        <a
                            href="my_tasks.php"
                            class="back-btn"
                        >

                            ← Back to My Tasks

                        </a>

                    </div>


                </form>


            </div>


        </section>


    </main>


</div>


<script>

/* =========================
   CHANGE TASK
========================= */

function changeTask(taskId) {

    if (taskId !== "") {

        window.location.href =
            "submit_task.php?task_id="
            + taskId;

    }

}

</script>


</body>

</html>


<?php

$tasksStmt->close();

?>
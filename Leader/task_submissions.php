<?php

session_start();

require_once "../config/database.php";

// Leader login check
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Leader role check
if (strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {
    header("Location: ../login.php");
    exit;
}

$leaderId = $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Get submitted tasks of this leader's team
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        task_submissions.id AS submission_id,
        task_submissions.file_name,
        task_submissions.file_path,
        task_submissions.comment,
        task_submissions.submitted_at,

        tasks.id AS task_id,
        tasks.title AS task_title,
        tasks.due_date,
        tasks.status,

        users.name AS worker_name,
        users.username AS worker_username

    FROM task_submissions

    INNER JOIN tasks
        ON task_submissions.task_id = tasks.id

    INNER JOIN users
        ON task_submissions.worker_id = users.id

    WHERE tasks.assigned_by = ?

    ORDER BY task_submissions.submitted_at DESC
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

    <title>Task Submissions</title>

    <link rel="stylesheet" href="../assets/css/leader.css">

    <style>

        .page-content {
            padding: 30px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            margin: 0;
        }

        .page-title p {
            color: #777;
            margin-top: 5px;
        }

        .submission-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .submission-table th,
        .submission-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .submission-table th {
            background: #1e293b;
            color: white;
        }

        .submission-table tr:hover {
            background: #f8fafc;
        }

        .worker-name {
            font-weight: bold;
            color: #1e293b;
        }

        .task-name {
            font-weight: 600;
        }

        .file-name {
            color: #475569;
        }

        .comment {
            max-width: 220px;
            word-wrap: break-word;
        }

        .view-btn {
            display: inline-block;
            padding: 8px 14px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 5px;
        }

        .view-btn:hover {
            background: #1d4ed8;
        }

        .download-btn {
            display: inline-block;
            padding: 8px 14px;
            background: #16a34a;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .download-btn:hover {
            background: #15803d;
        }

        .no-data {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            color: #777;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            background: #dcfce7;
            color: #166534;
        }

        @media (max-width: 900px) {

            .submission-table {
                display: block;
                overflow-x: auto;
            }

        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <h2>Task Management</h2>

    <ul>

        <li>
            <a href="dashboard.php">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="add_staff.php">
                👥 Staff Add
            </a>
        </li>

        <li>
            <a href="manage_staff.php">
                👤 Manage Staff
            </a>
        </li>

        <li>
            <a href="assign_task.php">
                📋 Assign Task
            </a>
        </li>

        <li>
            <a href="task_list.php">
                📝 Task List
            </a>
        </li>

        <li>
            <a href="task_submissions.php" class="active">
                📤 Task Submissions
            </a>
        </li>

        <li>
            <a href="warnings.php">
                ⚠️ Warnings
            </a>
        </li>

        <li>
            <a href="profile.php">
                👤 Profile
            </a>
        </li>

        <li>
            <a href="../logout.php">
                🚪 Logout
            </a>
        </li>

    </ul>

</div>


<!-- MAIN CONTENT -->

<div class="main-content">

    <div class="page-content">

        <div class="page-title">

            <h1>📤 Task Submissions</h1>

            <p>
                View tasks submitted by your workers
            </p>

        </div>


        <?php if ($result->num_rows > 0): ?>

            <table class="submission-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Task</th>

                        <th>Worker</th>

                        <th>File</th>

                        <th>Comment</th>

                        <th>Submitted Date</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $count = 1;

                while ($row = $result->fetch_assoc()):

                ?>

                    <tr>

                        <td>
                            <?php echo $count++; ?>
                        </td>


                        <td>

                            <div class="task-name">

                                <?php
                                echo htmlspecialchars($row["task_title"]);
                                ?>

                            </div>

                        </td>


                        <td>

                            <div class="worker-name">

                                <?php
                                echo htmlspecialchars($row["worker_name"]);
                                ?>

                            </div>

                            <small>

                                @<?php
                                echo htmlspecialchars($row["worker_username"]);
                                ?>

                            </small>

                        </td>


                        <td>

                            <?php if (!empty($row["file_name"])): ?>

                                <div class="file-name">

                                    📎
                                    <?php
                                    echo htmlspecialchars($row["file_name"]);
                                    ?>

                                </div>

                            <?php else: ?>

                                No File

                            <?php endif; ?>

                        </td>


                        <td>

                            <div class="comment">

                                <?php

                                if (!empty($row["comment"])) {

                                    echo nl2br(
                                        htmlspecialchars($row["comment"])
                                    );

                                } else {

                                    echo "No comment";

                                }

                                ?>

                            </div>

                        </td>


                        <td>

                            <?php

                            echo date(
                                "d M Y, h:i A",
                                strtotime($row["submitted_at"])
                            );

                            ?>

                        </td>


                        <td>

                            <?php if (!empty($row["file_path"])): ?>

                                <a
                                    href="../<?php echo htmlspecialchars($row["file_path"]); ?>"
                                    target="_blank"
                                    class="view-btn"
                                >
                                    👁 View
                                </a>


                                <a
                                    href="../<?php echo htmlspecialchars($row["file_path"]); ?>"
                                    download
                                    class="download-btn"
                                >
                                    ⬇ Download
                                </a>

                            <?php else: ?>

                                No File

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>


        <?php else: ?>

            <div class="no-data">

                <h2>📭 No Submissions</h2>

                <p>
                    Your workers have not submitted any tasks yet.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>


</body>

</html>
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
   CURRENT LEADER ID
========================================= */

$leaderId = $_SESSION["user_id"];


/* =========================================
   GET WORKER ID
========================================= */

$workerId = intval($_GET["id"] ?? 0);

if ($workerId <= 0) {
    header("Location: manage_staff.php");
    exit;
}


/* =========================================
   MESSAGE
========================================= */

$message = "";
$messageType = "";


/* =========================================
   GET WORKER DETAILS
   IMPORTANT:
   worker must belong to current leader
========================================= */

$stmt = $conn->prepare("
    SELECT id, name, email, username, position
    FROM users
    WHERE id = ?
      AND role = 'worker'
      AND leader_id = ?
");

$stmt->bind_param(
    "ii",
    $workerId,
    $leaderId
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    header("Location: manage_staff.php");
    exit;
}

$worker = $result->fetch_assoc();

$stmt->close();


/* =========================================
   UPDATE WORKER
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $position = trim($_POST["position"] ?? "");
    $password = $_POST["password"] ?? "";


    /* =====================================
       VALIDATION
    ===================================== */

    if (
        $name === "" ||
        $email === "" ||
        $username === "" ||
        $position === ""
    ) {

        $message = "Please fill all required fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } else {


        /* =================================
           CHECK USERNAME
        ================================= */

        $checkUsername = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
              AND id != ?
        ");

        $checkUsername->bind_param(
            "si",
            $username,
            $workerId
        );

        $checkUsername->execute();

        $usernameResult = $checkUsername->get_result();


        if ($usernameResult->num_rows > 0) {

            $message = "Username already exists.";
            $messageType = "error";

            $checkUsername->close();

        } else {

            $checkUsername->close();


            /* ==============================
               UPDATE WITH PASSWORD
            ============================== */

            if ($password !== "") {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $update = $conn->prepare("
                    UPDATE users
                    SET
                        name = ?,
                        email = ?,
                        username = ?,
                        position = ?,
                        password = ?
                    WHERE id = ?
                      AND role = 'worker'
                      AND leader_id = ?
                ");

                $update->bind_param(
                    "sssssii",
                    $name,
                    $email,
                    $username,
                    $position,
                    $hashedPassword,
                    $workerId,
                    $leaderId
                );

            } else {

                /* ==============================
                   UPDATE WITHOUT PASSWORD
                ============================== */

                $update = $conn->prepare("
                    UPDATE users
                    SET
                        name = ?,
                        email = ?,
                        username = ?,
                        position = ?
                    WHERE id = ?
                      AND role = 'worker'
                      AND leader_id = ?
                ");

                $update->bind_param(
                    "ssssii",
                    $name,
                    $email,
                    $username,
                    $position,
                    $workerId,
                    $leaderId
                );
            }


            /* ==============================
               EXECUTE UPDATE
            ============================== */

            if ($update->execute()) {

                $message = "Staff details updated successfully!";
                $messageType = "success";

                /* Update displayed values */

                $worker["name"] = $name;
                $worker["email"] = $email;
                $worker["username"] = $username;
                $worker["position"] = $position;

            } else {

                $message = "Failed to update staff details.";
                $messageType = "error";
            }

            $update->close();
        }
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

    <title>Edit Staff - Leader</title>

    <link
        rel="stylesheet"
        href="../assets/css/leader.css"
    >

</head>


<body>

<div class="dashboard-container">


    <!-- =====================================
         SIDEBAR
    ====================================== -->

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


                <a
                    href="manage_staff.php"
                    class="active"
                >
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


                <a href="task_submissions.php">
                    <span>📤</span>
                    <span>Task Submissions</span>
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



    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">

            <div>

                <h3>Edit Staff</h3>

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



        <!-- =================================
             EDIT FORM
        ================================== -->

        <section class="content-card">


            <div class="page-title">

                <div>

                    <h2>Edit Staff Member</h2>

                    <p>
                        Update your team member details.
                    </p>

                </div>

            </div>



            <!-- MESSAGE -->

            <?php if ($message !== ""): ?>

                <div
                    class="form-message <?php echo $messageType; ?>"
                >

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php endif; ?>



            <!-- FORM -->

            <form
                method="POST"
                class="staff-form"
            >


                <!-- NAME -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php
                        echo htmlspecialchars(
                            $worker["name"]
                        );
                        ?>"
                        required
                    >

                </div>



                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email
                        <span>*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php
                        echo htmlspecialchars(
                            $worker["email"]
                        );
                        ?>"
                        required
                    >

                </div>



                <!-- USERNAME -->

                <div class="form-group">

                    <label for="username">
                        Username
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php
                        echo htmlspecialchars(
                            $worker["username"]
                        );
                        ?>"
                        required
                    >

                </div>



                <!-- POSITION -->

                <div class="form-group">

                    <label for="position">
                        Position
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="position"
                        name="position"
                        value="<?php
                        echo htmlspecialchars(
                            $worker["position"]
                        );
                        ?>"
                        required
                    >

                </div>



                <!-- PASSWORD -->

                <div class="form-group full-width">

                    <label for="password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Leave blank to keep current password"
                    >

                    <small class="form-note">
                        Leave this field empty if you don't want
                        to change the password.
                    </small>

                </div>



                <!-- BUTTONS -->

                <div class="form-actions">

                    <a
                        href="manage_staff.php"
                        class="cancel-btn"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="save-btn"
                    >
                        Save Changes
                    </button>

                </div>


            </form>

        </section>

    </main>

</div>

</body>

</html>
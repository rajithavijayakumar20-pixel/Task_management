<?php
session_start();

require_once "../config/database.php";

/* =========================
   LEADER LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "leader") {
    header("Location: ../login.php");
    exit;
}

/* Current logged-in Leader ID */
$leaderId = $_SESSION["user_id"];

$message = "";
$messageType = "";


/* =========================
   FORM SUBMIT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* Get form values */
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $position = trim($_POST["position"] ?? "");


    /* =========================
       VALIDATION
    ========================= */

    if ($name === "" || $email === "" || $username === "" || $password === "") {

        $message = "Please fill all required fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $messageType = "error";

    } else {

        $check = $conn->prepare(
            "SELECT id 
             FROM users 
             WHERE email = ? OR username = ?"
        );

        $check->bind_param(
            "ss",
            $email,
            $username
        );

        $check->execute();

        $result = $check->get_result();


        if ($result->num_rows > 0) {

            $message = "Email or Username already exists.";
            $messageType = "error";

        } else {

            /* =========================
               PASSWORD HASH
            ========================= */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* =========================
               ADD WORKER
               
               Role = worker automatically
               leader_id = current leader ID
            ========================= */

            $stmt = $conn->prepare(
                "INSERT INTO users
                (name, email, username, password, role, leader_id, position)
                VALUES (?, ?, ?, ?, 'worker', ?, ?)"
            );


            $stmt->bind_param(
                "ssssis",
                $name,
                $email,
                $username,
                $hashedPassword,
                $leaderId,
                $position
            );


            /* =========================
               EXECUTE
            ========================= */

            if ($stmt->execute()) {

                $message = "Staff added successfully!";
                $messageType = "success";

                /* Clear form values */
                $name = "";
                $email = "";
                $username = "";
                $position = "";

            } else {

                $message = "Failed to add staff. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Staff - Task Management System</title>

    <link rel="stylesheet" href="../assets/css/leader.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
        }

        /* =========================
           PAGE CONTAINER
        ========================= */

        .page-container {
            width: 100%;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .page-header {
            max-width: 900px;
            margin: 0 auto 25px;
        }

        .page-header h1 {
            margin: 0;
            color: #1e293b;
            font-size: 30px;
        }

        .page-header p {
            margin-top: 8px;
            color: #64748b;
            font-size: 15px;
        }


        /* =========================
           FORM CARD
        ========================= */

        .form-card {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }


        /* =========================
           MESSAGE
        ========================= */

        .message {
            max-width: 900px;
            margin: 0 auto 20px;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }


        /* =========================
           FORM GRID
        ========================= */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }


        /* =========================
           ROLE INFO
        ========================= */

        .role-box {
            margin-top: 22px;
            padding: 15px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            color: #1e40af;
            font-size: 14px;
        }

        .role-box strong {
            color: #1d4ed8;
        }


        /* =========================
           BUTTONS
        ========================= */

        .button-area {
            margin-top: 28px;
            display: flex;
            gap: 12px;
        }

        .btn {
            border: none;
            padding: 13px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .page-container {
                padding: 25px 15px;
            }

            .form-card {
                padding: 22px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .button-area {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

        }

    </style>

</head>


<body>


<div class="page-container">

    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="page-header">

        <h1>👥 Add Staff</h1>

        <p>
            Add a new worker to your team.
        </p>

    </div>


    <!-- =========================
         MESSAGE
    ========================= -->

    <?php if ($message !== ""): ?>

        <div class="message <?php echo htmlspecialchars($messageType); ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         FORM CARD
    ========================= -->

    <div class="form-card">

        <form method="POST" action="">


            <div class="form-grid">


                <!-- NAME -->

                <div class="form-group">

                    <label for="name">
                        Full Name *
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter staff name"
                        value="<?php echo htmlspecialchars($name ?? ''); ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email *
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter email address"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required
                    >

                </div>


                <!-- USERNAME -->

                <div class="form-group">

                    <label for="username">
                        Username *
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter username"
                        value="<?php echo htmlspecialchars($username ?? ''); ?>"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="password">
                        Password *
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        required
                    >

                </div>


                <!-- POSITION -->

                <div class="form-group full">

                    <label for="position">
                        Position
                    </label>

                    <input
                        type="text"
                        id="position"
                        name="position"
                        placeholder="Example: Developer, Designer, Accountant"
                        value="<?php echo htmlspecialchars($position ?? ''); ?>"
                    >

                </div>

            </div>


            <!-- ROLE INFORMATION -->

            <div class="role-box">

                👤 <strong>Role:</strong> Worker

                <br>

                This staff member will automatically be assigned
                to your team.

            </div>


            <!-- BUTTONS -->

            <div class="button-area">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    ➕ Add Staff
                </button>


                <a
                    href="dashboard.php"
                    class="btn btn-secondary"
                >
                    ← Back to Dashboard
                </a>

            </div>


        </form>

    </div>

</div>


</body>

</html>
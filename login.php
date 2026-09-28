
<?php

session_start();

require_once "./config/database.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {

        $message = "Please enter username and password.";
        $messageType = "error";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, username, password, role
             FROM users
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                if ($user["role"] == "admin") {

                    header("Location: Admin/dashboard.php");
                    exit;

                } elseif ($user["role"] == "leader") {

                    header("Location: Leader/dashboard.php");
                    exit;

                } elseif ($user["role"] == "worker") {

                    header("Location: Worker/dashboard.php");
                    exit;

                } else {

                    $message = "Invalid user role.";
                    $messageType = "error";
                }

            } else {

                $message = "Incorrect password.";
                $messageType = "error";
            }

        } else {

            $message = "Username not found.";
            $messageType = "error";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Task Management</title>

    <link rel="stylesheet" href="./assets/css/login.css">

</head>

<body>

    <div class="login-container">

        <div class="login-left">

            <div class="brand">
                <span>✓</span> TaskFlow
            </div>

            <h1>Welcome<br>Back!</h1>

            <p>
                Manage your tasks, track your progress,
                and work together with your team.
            </p>

            <div class="login-decoration">
                <div class="decoration-card">
                    <span>✓</span>
                    <div>
                        <strong>Stay Organized</strong>
                        <small>Manage your daily tasks</small>
                    </div>
                </div>

                <div class="decoration-card">
                    <span>↗</span>
                    <div>
                        <strong>Track Progress</strong>
                        <small>Achieve your team goals</small>
                    </div>
                </div>
            </div>

        </div>


        <div class="login-right">

            <div class="login-box">

                <div class="mobile-brand">
                    <span>✓</span> TaskFlow
                </div>

                <h2>Sign In</h2>

                <p class="login-subtitle">
                    Enter your details to access your account
                </p>

                <?php if (!empty($message)): ?>

                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php endif; ?>


                <form method="POST" action="">

                    <div class="form-group">

                        <label for="username">Username</label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">Password</label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                    </div>


                    <div class="form-options">

                        <label class="remember">
                            <input type="checkbox" name="remember">
                            Remember me
                        </label>

                        <a href="#">Forgot Password?</a>

                    </div>


                    <button type="submit" class="login-btn">
                        Sign In →
                    </button>

                </form>

                <p class="login-footer">
                    Task Management System
                </p>

            </div>

        </div>

    </div>

</body>
</html>
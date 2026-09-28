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


/* =========================
   CURRENT LEADER ID
========================= */

$leaderId = $_SESSION["user_id"];


/* =========================
   SEARCH & POSITION FILTER
========================= */

$search = trim($_GET["search"] ?? "");
$position = trim($_GET["position"] ?? "");


/* =========================
   MAIN QUERY
   CURRENT LEADER'S WORKERS ONLY
========================= */

$sql = "
    SELECT id, name, email, username, position, created_at
    FROM users
    WHERE role = 'worker'
    AND leader_id = ?
";


$params = [];
$types = "i";

/* Current Leader ID */
$params[] = $leaderId;


/* =========================
   SEARCH FILTER
========================= */

if ($search !== "") {

    $sql .= " AND (
        name LIKE ?
        OR username LIKE ?
        OR email LIKE ?
    )";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}


/* =========================
   POSITION FILTER
========================= */

if ($position !== "") {

    $sql .= " AND position = ?";

    $params[] = $position;

    $types .= "s";
}


/* =========================
   ORDER
========================= */

$sql .= " ORDER BY id DESC";


/* =========================
   PREPARE QUERY
========================= */

$stmt = $conn->prepare($sql);

$stmt->bind_param($types, ...$params);

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

    <title>Manage Staff - Leader</title>

    <link
        rel="stylesheet"
        href="../assets/css/leader.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- =========================
         SIDEBAR
    ========================= -->

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



    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">


            <div>

                <h3>Manage Staff</h3>

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



        <!-- =========================
             STAFF CONTENT
        ========================= -->

        <section class="content-card">


            <div class="page-title">


                <div>

                    <h2>Staff Management</h2>

                    <p>
                        View and manage your team members.
                    </p>

                </div>


                <a
                    href="add_staff.php"
                    class="add-staff-btn"
                >

                    + Add Staff

                </a>


            </div>



            <!-- =========================
                 SEARCH
            ========================= -->

            <form
                method="GET"
                action="manage_staff.php"
                style="
                    display:flex;
                    gap:10px;
                    margin-bottom:20px;
                    flex-wrap:wrap;
                "
            >


                <input
                    type="text"
                    name="search"
                    placeholder="Search staff..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="
                        padding:10px 12px;
                        border:1px solid #cbd5e1;
                        border-radius:8px;
                        min-width:250px;
                    "
                >


                <input
                    type="text"
                    name="position"
                    placeholder="Position"
                    value="<?php echo htmlspecialchars($position); ?>"
                    style="
                        padding:10px 12px;
                        border:1px solid #cbd5e1;
                        border-radius:8px;
                        min-width:180px;
                    "
                >


                <button
                    type="submit"
                    style="
                        padding:10px 18px;
                        border:none;
                        border-radius:8px;
                        background:#2563eb;
                        color:white;
                        cursor:pointer;
                    "
                >

                    Search

                </button>


                <a
                    href="manage_staff.php"
                    style="
                        padding:10px 18px;
                        border-radius:8px;
                        background:#e2e8f0;
                        color:#334155;
                        text-decoration:none;
                    "
                >

                    Reset

                </a>


            </form>



            <!-- =========================
                 STAFF TABLE
            ========================= -->

            <?php if ($result->num_rows > 0): ?>


                <div class="table-container">


                    <table class="staff-table">


                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Staff</th>

                                <th>Email</th>

                                <th>Position</th>

                                <th>Username</th>

                                <th>Joined Date</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php

                        $count = 1;

                        while (
                            $staff = $result->fetch_assoc()
                        ):

                        ?>


                            <tr>


                                <td>

                                    <?php

                                    echo $count++;

                                    ?>

                                </td>



                                <td>

                                    <div class="staff-name">

                                        <?php

                                        echo htmlspecialchars(
                                            $staff["name"]
                                        );

                                        ?>

                                    </div>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $staff["email"]
                                    );

                                    ?>

                                </td>



                                <td>

                                    <span class="position-badge">

                                        <?php

                                        echo htmlspecialchars(
                                            $staff["position"]
                                            ?: "Staff"
                                        );

                                        ?>

                                    </span>

                                </td>



                                <td>

                                    @<?php

                                    echo htmlspecialchars(
                                        $staff["username"]
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $staff["created_at"]
                                        )
                                    );

                                    ?>

                                </td>



                                <td>

                                    <div class="action-buttons">


                                        <a
                                            href="edit_staff.php?id=<?php echo $staff["id"]; ?>"
                                            class="edit-btn"
                                        >

                                            Edit

                                        </a>



                                        <a
                                            href="delete_staff.php?id=<?php echo $staff["id"]; ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this staff member?');"
                                        >

                                            Delete

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <!-- NO STAFF -->

                <div class="empty-box">


                    <h3>No Staff Found</h3>


                    <p>

                        You have not added any staff
                        members to your team yet.

                    </p>


                    <a
                        href="add_staff.php"
                        class="add-staff-btn"
                    >

                        + Add Staff

                    </a>


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
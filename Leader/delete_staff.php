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
   CHECK WORKER BELONGS TO THIS LEADER
========================================= */

$check = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role = 'worker'
      AND leader_id = ?
");

$check->bind_param(
    "ii",
    $workerId,
    $leaderId
);

$check->execute();

$result = $check->get_result();


if ($result->num_rows === 0) {

    $check->close();

    header("Location: manage_staff.php");
    exit;
}

$check->close();


/* =========================================
   DELETE WORKER
========================================= */

$delete = $conn->prepare("
    DELETE FROM users
    WHERE id = ?
      AND role = 'worker'
      AND leader_id = ?
");

$delete->bind_param(
    "ii",
    $workerId,
    $leaderId
);


if ($delete->execute()) {

    $delete->close();

    header("Location: manage_staff.php?deleted=1");
    exit;

} else {

    $delete->close();

    header("Location: manage_staff.php?deleted=0");
    exit;
}
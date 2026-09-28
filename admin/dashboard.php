<?php
session_start();
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| admin Login Check
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminId = (int) $_SESSION["user_id"];

/* Total Staff - only staff belonging to this leader */
$staffStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'leader'
     AND leader_id = ?"
);

$staffStmt->bind_param("i", $leaderId);
$staffStmt->execute();

$staffResult = $staffStmt->get_result();
$totalStaff = $staffResult->fetch_assoc()["total"];


/* Total Tasks - only tasks assigned by this leader */
$taskStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM tasks
     WHERE assigned_by = ?"
);
?>


































<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Leader Dashboard</title>

    <link
        rel="stylesheet"
        href="../assets/css/leader.css"

    >

</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="logo">

            <h2>TaskFlow</h2>

            <p>Admin  Panel</p>

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



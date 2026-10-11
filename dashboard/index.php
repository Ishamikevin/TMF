<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| TOTAL SUITS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM suits
     WHERE user_id = ?"
);

$stmt->execute([$user_id]);

$total_suits = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| AVAILABLE SUITS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM suits
     WHERE user_id = ?
     AND status = 'Available'"
);

$stmt->execute([$user_id]);

$available_suits = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| RENTED SUITS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM suits
     WHERE user_id = ?
     AND status = 'Rented'"
);

$stmt->execute([$user_id]);

$rented_suits = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| OVERDUE RENTALS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM rentals
     WHERE user_id = ?
     AND status = 'Rented'
     AND expected_return_date < CURDATE()"
);

$stmt->execute([$user_id]);

$overdue_rentals = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL RENTALS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM rentals
     WHERE user_id = ?"
);

$stmt->execute([$user_id]);

$total_rentals = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL RETURNED
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM rentals
     WHERE user_id = ?
     AND status = 'Returned'"
);

$stmt->execute([$user_id]);

$total_returned = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| RECENT RENTALS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        rentals.id,
        rentals.person_name,
        rentals.taken_date,
        rentals.expected_return_date,
        rentals.returned_date,
        rentals.status,
        suits.suit_code,
        suits.name AS suit_name

     FROM rentals

     INNER JOIN suits
        ON rentals.suit_id = suits.id

     WHERE rentals.user_id = ?

     ORDER BY rentals.created_at DESC

     LIMIT 5"
);

$stmt->execute([$user_id]);

$recent_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard - TMF</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="app">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>


    <main class="main-content">

        <div class="topbar">

            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Welcome back,
                    <strong>
                        <?= htmlspecialchars(
                            $_SESSION["user_name"]
                        ) ?>
                    </strong>
                </p>

            </div>

        </div>


        <!-- STATISTICS -->

        <div class="stats-grid">

            <div class="stat-card">

                <h3>
                    TOTAL SUITS
                </h3>

                <div class="stat-number">
                    <?= $total_suits ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    AVAILABLE
                </h3>

                <div class="stat-number">
                    <?= $available_suits ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    RENTED
                </h3>

                <div class="stat-number">
                    <?= $rented_suits ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    OVERDUE
                </h3>

                <div class="stat-number">
                    <?= $overdue_rentals ?>
                </div>

            </div>

        </div>


        <!-- QUICK ACTIONS -->

        <div class="card">

            <div class="card-header">

                <h2>
                    Quick Actions
                </h2>

            </div>

            <div class="quick-actions">

                <a
                    href="../suits/add.php"
                    class="btn btn-gold"
                >
                    + Add Suit
                </a>

                <a
                    href="../suits/index.php"
                    class="btn"
                >
                    👔 Manage Suits
                </a>

                <a
                    href="../rentals/index.php"
                    class="btn"
                >
                    📋 Rentals
                </a>

                <a
                    href="../reports/index.php"
                    class="btn"
                >
                    📊 Reports
                </a>

            </div>

        </div>


        <!-- RECENT RENTALS -->

        <div class="card">

            <div class="card-header">

                <h2>
                    Recent Rentals
                </h2>

                <a
                    href="../rentals/index.php"
                    class="btn"
                >
                    View All
                </a>

            </div>


            <?php if (count($recent_rentals) > 0): ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Suit
                                </th>

                                <th>
                                    Person
                                </th>

                                <th>
                                    Taken
                                </th>

                                <th>
                                    Expected Return
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $recent_rentals
                                as $rental
                            ): ?>

                                <?php

                                if (
                                    $rental["status"]
                                    === "Returned"
                                ) {

                                    $display_status =
                                        "Returned";

                                    $status_class =
                                        "status-returned";

                                } elseif (
                                    $rental[
                                        "expected_return_date"
                                    ] < date("Y-m-d")
                                ) {

                                    $display_status =
                                        "Overdue";

                                    $status_class =
                                        "status-overdue";

                                } else {

                                    $display_status =
                                        "Rented";

                                    $status_class =
                                        "status-rented";
                                }

                                ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $rental[
                                                    "suit_code"
                                                ]
                                            ) ?>
                                        </strong>

                                        <br>

                                        <?= htmlspecialchars(
                                            $rental[
                                                "suit_name"
                                            ]
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $rental[
                                                "person_name"
                                            ]
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $rental[
                                                "taken_date"
                                            ]
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $rental[
                                                "expected_return_date"
                                            ]
                                        ) ?>

                                    </td>

                                    <td>

                                        <span
                                            class="status
                                            <?= $status_class ?>"
                                        >
                                            <?= $display_status ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p>
                    No rental records yet.
                </p>

            <?php endif; ?>

        </div>


    </main>

</div>

</body>

</html>
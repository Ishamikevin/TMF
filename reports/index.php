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
| TOTAL RETURNS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     FROM rentals
     WHERE user_id = ?
     AND status = 'Returned'"
);

$stmt->execute([$user_id]);

$total_returns = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| AVERAGE DURATION
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT AVG(duration)
     FROM rentals
     WHERE user_id = ?
     AND duration IS NOT NULL"
);

$stmt->execute([$user_id]);

$average_duration = $stmt->fetchColumn();

$average_duration =
    $average_duration !== null
        ? round($average_duration, 1)
        : 0;


/*
|--------------------------------------------------------------------------
| LONGEST RENTAL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        rentals.duration,
        rentals.person_name,
        suits.suit_code,
        suits.name AS suit_name

     FROM rentals

     INNER JOIN suits
        ON rentals.suit_id = suits.id

     WHERE rentals.user_id = ?

     AND rentals.duration IS NOT NULL

     ORDER BY rentals.duration DESC

     LIMIT 1"
);

$stmt->execute([$user_id]);

$longest_rental =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| MOST RENTED SUIT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        suits.suit_code,
        suits.name,
        COUNT(rentals.id) AS rental_count

     FROM rentals

     INNER JOIN suits
        ON rentals.suit_id = suits.id

     WHERE rentals.user_id = ?

     GROUP BY
        suits.id,
        suits.suit_code,
        suits.name

     ORDER BY rental_count DESC

     LIMIT 1"
);

$stmt->execute([$user_id]);

$most_rented =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| LEAST RENTED SUIT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        suits.suit_code,
        suits.name,
        COUNT(rentals.id) AS rental_count

     FROM rentals

     INNER JOIN suits
        ON rentals.suit_id = suits.id

     WHERE rentals.user_id = ?

     GROUP BY
        suits.id,
        suits.suit_code,
        suits.name

     ORDER BY rental_count ASC

     LIMIT 1"
);

$stmt->execute([$user_id]);

$least_rented =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| RENTALS PER MONTH
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        DATE_FORMAT(
            taken_date,
            '%Y-%m'
        ) AS rental_month,

        COUNT(*) AS rental_count

     FROM rentals

     WHERE user_id = ?

     GROUP BY
        DATE_FORMAT(
            taken_date,
            '%Y-%m'
        )

     ORDER BY rental_month DESC"
);

$stmt->execute([$user_id]);

$monthly_rentals =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| MOST RENTED SIZES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        suits.size,
        COUNT(rentals.id) AS rental_count

     FROM rentals

     INNER JOIN suits
        ON rentals.suit_id = suits.id

     WHERE rentals.user_id = ?

     GROUP BY suits.size

     ORDER BY rental_count DESC"
);

$stmt->execute([$user_id]);

$size_statistics =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| POPULAR COLORS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        suits.color,
        COUNT(rentals.id) AS rental_count

     FROM rentals

     INNER JOIN suits
        ON rentals.suit_id = suits.id

     WHERE rentals.user_id = ?

     GROUP BY suits.color

     ORDER BY rental_count DESC"
);

$stmt->execute([$user_id]);

$color_statistics =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| OVERDUE
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

$overdue_rentals =
    $stmt->fetchColumn();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reports - TMF</title>

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
                    Reports & Analytics
                </h1>

                <p>
                    Understand your rental business
                </p>

            </div>

        </div>


        <!-- STATISTICS -->

        <div class="stats-grid">


            <div class="stat-card">

                <h3>
                    TOTAL RENTALS
                </h3>

                <div class="stat-number">
                    <?= $total_rentals ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    TOTAL RETURNS
                </h3>

                <div class="stat-number">
                    <?= $total_returns ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    AVG. DURATION
                </h3>

                <div class="stat-number">
                    <?= $average_duration ?>
                </div>

                <p>
                    days
                </p>

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


        <!-- MOST / LEAST / LONGEST -->

        <div class="stats-grid">


            <div class="card">

                <h2>
                    Most Rented Suit
                </h2>

                <br>

                <?php if ($most_rented): ?>

                    <h3>
                        <?= htmlspecialchars(
                            $most_rented[
                                "suit_code"
                            ]
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $most_rented[
                                "name"
                            ]
                        ) ?>
                    </p>

                    <br>

                    <strong>
                        <?= $most_rented[
                            "rental_count"
                        ] ?>
                        rental(s)
                    </strong>

                <?php else: ?>

                    <p>
                        No rental data yet.
                    </p>

                <?php endif; ?>

            </div>


            <div class="card">

                <h2>
                    Least Rented Suit
                </h2>

                <br>

                <?php if ($least_rented): ?>

                    <h3>
                        <?= htmlspecialchars(
                            $least_rented[
                                "suit_code"
                            ]
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $least_rented[
                                "name"
                            ]
                        ) ?>
                    </p>

                    <br>

                    <strong>
                        <?= $least_rented[
                            "rental_count"
                        ] ?>
                        rental(s)
                    </strong>

                <?php else: ?>

                    <p>
                        No rental data yet.
                    </p>

                <?php endif; ?>

            </div>


            <div class="card">

                <h2>
                    Longest Rental
                </h2>

                <br>

                <?php if ($longest_rental): ?>

                    <div class="stat-number">
                        <?= $longest_rental[
                            "duration"
                        ] ?>
                    </div>

                    <p>
                        days
                    </p>

                    <br>

                    <p>
                        Suit:
                        <strong>
                            <?= htmlspecialchars(
                                $longest_rental[
                                    "suit_code"
                                ]
                            ) ?>
                        </strong>
                    </p>

                    <p>
                        Person:
                        <?= htmlspecialchars(
                            $longest_rental[
                                "person_name"
                            ]
                        ) ?>
                    </p>

                <?php else: ?>

                    <p>
                        No returned rentals yet.
                    </p>

                <?php endif; ?>

            </div>


        </div>


        <!-- MONTHLY RENTALS -->

        <div class="card">

            <div class="card-header">

                <h2>
                    Rentals Per Month
                </h2>

            </div>


            <?php if (
                count($monthly_rentals) > 0
            ): ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Month
                                </th>

                                <th>
                                    Rentals
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $monthly_rentals
                                as $month
                            ): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars(
                                            $month[
                                                "rental_month"
                                            ]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $month[
                                            "rental_count"
                                        ] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p>
                    No rental data available.
                </p>

            <?php endif; ?>

        </div>


        <!-- SIZE -->

        <div class="card">

            <div class="card-header">

                <h2>
                    Most Rented Sizes
                </h2>

            </div>


            <?php if (
                count($size_statistics) > 0
            ): ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Size
                                </th>

                                <th>
                                    Rentals
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $size_statistics
                                as $size
                            ): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars(
                                            $size["size"]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $size[
                                            "rental_count"
                                        ] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p>
                    No size data available.
                </p>

            <?php endif; ?>

        </div>


        <!-- COLORS -->

        <div class="card">

            <div class="card-header">

                <h2>
                    Most Popular Colors
                </h2>

            </div>


            <?php if (
                count($color_statistics) > 0
            ): ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Color
                                </th>

                                <th>
                                    Rentals
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $color_statistics
                                as $color
                            ): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars(
                                            $color["color"]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $color[
                                            "rental_count"
                                        ] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p>
                    No color data available.
                </p>

            <?php endif; ?>

        </div>


    </main>

</div>

</body>

</html>
<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


if (
    !isset($_GET["id"])
    ||
    !is_numeric($_GET["id"])
) {

    header("Location: index.php");

    exit;
}


$rental_id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| FIND RENTAL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        rentals.*,
        suits.suit_code,
        suits.name AS suit_name

     FROM rentals

     INNER JOIN suits
        ON rentals.suit_id = suits.id

     WHERE rentals.id = ?

     AND rentals.user_id = ?

     AND rentals.status = 'Rented'"
);

$stmt->execute([
    $rental_id,
    $user_id
]);

$rental = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$rental) {

    die("Rental record not found.");

}


/*
|--------------------------------------------------------------------------
| RETURN SUIT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $returned_date = date("Y-m-d");


    $taken = new DateTime(
        $rental["taken_date"]
    );


    $returned = new DateTime(
        $returned_date
    );


    $duration =
        $taken->diff($returned)->days;


    try {

        $conn->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | UPDATE RENTAL
        |--------------------------------------------------------------------------
        */

        $updateRental = $conn->prepare(
            "UPDATE rentals

             SET
                returned_date = ?,
                duration = ?,
                status = 'Returned'

             WHERE id = ?

             AND user_id = ?"
        );


        $updateRental->execute([
            $returned_date,
            $duration,
            $rental_id,
            $user_id
        ]);


        /*
        |--------------------------------------------------------------------------
        | MAKE SUIT AVAILABLE
        |--------------------------------------------------------------------------
        */

        $updateSuit = $conn->prepare(
            "UPDATE suits

             SET status = 'Available'

             WHERE id = ?

             AND user_id = ?"
        );


        $updateSuit->execute([
            $rental["suit_id"],
            $user_id
        ]);


        $conn->commit();


        header("Location: index.php");

        exit;


    } catch (Exception $e) {

        $conn->rollBack();

        die(
            "Could not return the suit."
        );

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

    <title>Return Suit - TMF</title>

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
                    Return Suit
                </h1>

                <p>
                    Complete this rental
                </p>

            </div>

        </div>


        <div class="form-card">

            <h2>
                Return Confirmation
            </h2>

            <br>


            <div class="card">

                <p>
                    <strong>
                        Suit:
                    </strong>

                    <?= htmlspecialchars(
                        $rental["suit_code"]
                    ) ?>
                </p>

                <br>

                <p>
                    <strong>
                        Name:
                    </strong>

                    <?= htmlspecialchars(
                        $rental["suit_name"]
                    ) ?>
                </p>

                <br>

                <p>
                    <strong>
                        Rented By:
                    </strong>

                    <?= htmlspecialchars(
                        $rental["person_name"]
                    ) ?>
                </p>

                <br>

                <p>
                    <strong>
                        Phone:
                    </strong>

                    <?= htmlspecialchars(
                        $rental["person_phone"]
                    ) ?>
                </p>

                <br>

                <p>
                    <strong>
                        Taken Date:
                    </strong>

                    <?= htmlspecialchars(
                        $rental["taken_date"]
                    ) ?>
                </p>

                <br>

                <p>
                    <strong>
                        Expected Return:
                    </strong>

                    <?= htmlspecialchars(
                        $rental[
                            "expected_return_date"
                        ]
                    ) ?>
                </p>

                <br>

                <p>
                    <strong>
                        Return Date:
                    </strong>

                    <?= date("Y-m-d") ?>
                </p>

            </div>


            <form
                method="POST"
                action=""
            >

                <p>
                    Are you sure this suit has been returned?
                </p>

                <br>

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    Confirm Return
                </button>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

            </form>

        </div>


    </main>

</div>

</body>

</html>
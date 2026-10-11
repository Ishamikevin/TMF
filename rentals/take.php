<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$error = "";


/*
|--------------------------------------------------------------------------
| GET SUIT ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET["suit_id"])
    ||
    !is_numeric($_GET["suit_id"])
) {

    header("Location: ../suits/index.php");

    exit;
}

$suit_id = (int) $_GET["suit_id"];


/*
|--------------------------------------------------------------------------
| GET SUIT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT *
     FROM suits
     WHERE id = ?
     AND user_id = ?
     AND status = 'Available'"
);

$stmt->execute([
    $suit_id,
    $user_id
]);

$suit = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$suit) {

    die(
        "Suit not found or it is currently rented."
    );

}


/*
|--------------------------------------------------------------------------
| RENT SUIT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $person_name =
        trim($_POST["person_name"] ?? "");

    $person_phone =
        trim($_POST["person_phone"] ?? "");

    $taken_date =
        $_POST["taken_date"] ?? "";

    $expected_return_date =
        $_POST["expected_return_date"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $person_name === ""
        ||
        $person_phone === ""
        ||
        $taken_date === ""
        ||
        $expected_return_date === ""
    ) {

        $error =
            "Please fill in all fields.";

    } elseif (
        !preg_match(
            "/^[A-Za-z ]+$/",
            $person_name
        )
    ) {

        $error =
            "Person name must contain letters only.";

    } elseif (
        !preg_match(
            "/^[0-9]+$/",
            $person_phone
        )
    ) {

        $error =
            "Phone number must contain numbers only.";

    } elseif (
        $expected_return_date < $taken_date
    ) {

        $error =
            "Expected return date cannot be before the taken date.";

    } else {


        try {

            $conn->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | INSERT RENTAL
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare(
                "INSERT INTO rentals
                (
                    user_id,
                    suit_id,
                    person_name,
                    person_phone,
                    taken_date,
                    expected_return_date,
                    status
                )

                VALUES
                (?, ?, ?, ?, ?, ?, 'Rented')"
            );


            $stmt->execute([
                $user_id,
                $suit_id,
                $person_name,
                $person_phone,
                $taken_date,
                $expected_return_date
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE SUIT
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare(
                "UPDATE suits

                 SET status = 'Rented'

                 WHERE id = ?

                 AND user_id = ?"
            );


            $stmt->execute([
                $suit_id,
                $user_id
            ]);


            $conn->commit();


            header(
                "Location: index.php"
            );

            exit;


        } catch (Exception $e) {

            $conn->rollBack();

            $error =
                "Could not complete the rental.";
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

    <title>Rent Suit - TMF</title>

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
                    Rent Suit
                </h1>

                <p>
                    Create a new rental
                </p>

            </div>

        </div>


        <div class="form-card">


            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- SUIT INFORMATION -->

            <div class="card">

                <h2>
                    Suit Information
                </h2>

                <br>

                <p>

                    <strong>
                        Code:
                    </strong>

                    <?= htmlspecialchars(
                        $suit["suit_code"]
                    ) ?>

                </p>

                <br>

                <p>

                    <strong>
                        Name:
                    </strong>

                    <?= htmlspecialchars(
                        $suit["name"]
                    ) ?>

                </p>

                <br>

                <p>

                    <strong>
                        Color:
                    </strong>

                    <?= htmlspecialchars(
                        $suit["color"]
                    ) ?>

                </p>

                <br>

                <p>

                    <strong>
                        Size:
                    </strong>

                    <?= htmlspecialchars(
                        $suit["size"]
                    ) ?>

                </p>

                <br>

                <p>

                    <strong>
                        Price:
                    </strong>

                    <?= number_format(
                        $suit["price"],
                        2
                    ) ?>

                </p>

            </div>


            <!-- RENTAL FORM -->

            <form
                method="POST"
                action=""
            >


                <div class="form-group">

                    <label for="person_name">

                        Customer Name

                    </label>

                    <input
                        type="text"
                        id="person_name"
                        name="person_name"
                        placeholder="Enter customer name"
                        value="<?= htmlspecialchars(
                            $_POST[
                                "person_name"
                            ] ?? ""
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="person_phone">

                        Phone Number

                    </label>

                    <input
                        type="text"
                        id="person_phone"
                        name="person_phone"
                        placeholder="Enter phone number"
                        value="<?= htmlspecialchars(
                            $_POST[
                                "person_phone"
                            ] ?? ""
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="taken_date">

                        Taken Date

                    </label>

                    <input
                        type="date"
                        id="taken_date"
                        name="taken_date"
                        value="<?= htmlspecialchars(
                            $_POST[
                                "taken_date"
                            ]
                            ??
                            date("Y-m-d")
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label
                        for="expected_return_date"
                    >

                        Expected Return Date

                    </label>

                    <input
                        type="date"
                        id="expected_return_date"
                        name="expected_return_date"
                        value="<?= htmlspecialchars(
                            $_POST[
                                "expected_return_date"
                            ]
                            ??
                            date(
                                "Y-m-d",
                                strtotime("+1 day")
                            )
                        ) ?>"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-gold"
                >

                    Confirm Rental

                </button>


                <a
                    href="../suits/index.php"
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
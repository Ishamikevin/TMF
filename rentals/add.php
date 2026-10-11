<?php

session_start();

require_once __DIR__ . "/../includes/db.php";

$message = "";
$message_type = "";


// ==========================================
// SAVE RENTAL
// ==========================================

if (isset($_POST['save_rental'])) {

    $suit_id = (int)($_POST['suit_id'] ?? 0);

    $rental_type =
        trim($_POST['rental_type'] ?? '');

    $customer_name =
        trim($_POST['customer_name'] ?? '');

    $customer_phone =
        trim($_POST['customer_phone'] ?? '');

    $rental_date =
        $_POST['rental_date'] ?? date('Y-m-d');

    $return_date =
        $_POST['return_date'] ?? '';

    $price =
        (float)($_POST['price'] ?? 0);


    // ======================================
    // VALIDATION
    // ======================================

    if (
        $suit_id <= 0 ||
        $rental_type === "" ||
        $customer_name === "" ||
        $rental_date === ""
    ) {

        $message =
            "Please fill in all required fields.";

        $message_type = "error";

    } else {


        // ==================================
        // GET SUIT
        // ==================================

        $suit_sql = "
            SELECT *
            FROM suits
            WHERE id = ?
            LIMIT 1
        ";

        $suit_stmt =
            mysqli_prepare(
                $conn,
                $suit_sql
            );

        mysqli_stmt_bind_param(
            $suit_stmt,
            "i",
            $suit_id
        );

        mysqli_stmt_execute(
            $suit_stmt
        );

        $suit_result =
            mysqli_stmt_get_result(
                $suit_stmt
            );

        $suit =
            mysqli_fetch_assoc(
                $suit_result
            );

        mysqli_stmt_close(
            $suit_stmt
        );


        if (!$suit) {

            $message =
                "Suit not found.";

            $message_type = "error";

        } else {


            // ==================================
            // GET COAT AND PANT
            // ==================================

            $components_sql = "
                SELECT *
                FROM suit_components
                WHERE suit_id = ?
            ";

            $components_stmt =
                mysqli_prepare(
                    $conn,
                    $components_sql
                );

            mysqli_stmt_bind_param(
                $components_stmt,
                "i",
                $suit_id
            );

            mysqli_stmt_execute(
                $components_stmt
            );

            $components_result =
                mysqli_stmt_get_result(
                    $components_stmt
                );


            $coat = null;
            $pant = null;


            while (
                $component =
                mysqli_fetch_assoc(
                    $components_result
                )
            ) {

                if (
                    $component['component_type']
                    === 'Coat'
                ) {

                    $coat = $component;

                }

                if (
                    $component['component_type']
                    === 'Pant'
                ) {

                    $pant = $component;

                }

            }


            mysqli_stmt_close(
                $components_stmt
            );


            // ==================================
            // CHECK AVAILABILITY
            // ==================================

            if (
                $rental_type === "Complete Suit"
            ) {

                if (!$coat || !$pant) {

                    $message =
                        "This suit does not have both coat and pant.";

                    $message_type = "error";

                } elseif (
                    $coat['status'] !== 'Available'
                    ||
                    $pant['status'] !== 'Available'
                ) {

                    $message =
                        "The complete suit is not available.";

                    $message_type = "error";
                }

            }


            elseif (
                $rental_type === "Coat Only"
            ) {

                if (!$coat) {

                    $message =
                        "Coat not found.";

                    $message_type = "error";

                } elseif (
                    $coat['status'] !== 'Available'
                ) {

                    $message =
                        "This coat is already rented.";

                    $message_type = "error";
                }

            }


            elseif (
                $rental_type === "Pant Only"
            ) {

                if (!$pant) {

                    $message =
                        "Pant not found.";

                    $message_type = "error";

                } elseif (
                    $pant['status'] !== 'Available'
                ) {

                    $message =
                        "This pant is already rented.";

                    $message_type = "error";
                }

            }


            else {

                $message =
                    "Invalid rental type.";

                $message_type = "error";

            }


            // ==================================
            // CREATE RENTAL
            // ==================================

            if ($message_type !== "error") {


                mysqli_begin_transaction(
                    $conn
                );


                try {


                    $component_id = null;


                    // ==========================
                    // COMPLETE SUIT
                    // ==========================

                    if (
                        $rental_type
                        === "Complete Suit"
                    ) {

                        $update_sql = "
                            UPDATE suit_components
                            SET status = 'Rented'
                            WHERE suit_id = ?
                        ";

                        $update_stmt =
                            mysqli_prepare(
                                $conn,
                                $update_sql
                            );

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "i",
                            $suit_id
                        );

                        if (
                            !mysqli_stmt_execute(
                                $update_stmt
                            )
                        ) {

                            throw new Exception(
                                "Could not rent suit pieces."
                            );
                        }

                        mysqli_stmt_close(
                            $update_stmt
                        );

                    }


                    // ==========================
                    // COAT ONLY
                    // ==========================

                    elseif (
                        $rental_type
                        === "Coat Only"
                    ) {

                        $component_id =
                            (int)$coat['id'];


                        $update_sql = "
                            UPDATE suit_components
                            SET status = 'Rented'
                            WHERE id = ?
                            AND status = 'Available'
                        ";

                        $update_stmt =
                            mysqli_prepare(
                                $conn,
                                $update_sql
                            );

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "i",
                            $component_id
                        );

                        if (
                            !mysqli_stmt_execute(
                                $update_stmt
                            )
                        ) {

                            throw new Exception(
                                "Could not rent coat."
                            );
                        }

                        mysqli_stmt_close(
                            $update_stmt
                        );

                    }


                    // ==========================
                    // PANT ONLY
                    // ==========================

                    elseif (
                        $rental_type
                        === "Pant Only"
                    ) {

                        $component_id =
                            (int)$pant['id'];


                        $update_sql = "
                            UPDATE suit_components
                            SET status = 'Rented'
                            WHERE id = ?
                            AND status = 'Available'
                        ";

                        $update_stmt =
                            mysqli_prepare(
                                $conn,
                                $update_sql
                            );

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "i",
                            $component_id
                        );

                        if (
                            !mysqli_stmt_execute(
                                $update_stmt
                            )
                        ) {

                            throw new Exception(
                                "Could not rent pant."
                            );
                        }

                        mysqli_stmt_close(
                            $update_stmt
                        );

                    }


                    // ==================================
                    // INSERT RENTAL
                    // ==================================

                    $rental_sql = "
                        INSERT INTO rentals
                        (
                            suit_id,
                            component_id,
                            rental_type,
                            customer_name,
                            customer_phone,
                            rental_date,
                            return_date,
                            price,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Rented')
                    ";


                    $rental_stmt =
                        mysqli_prepare(
                            $conn,
                            $rental_sql
                        );


                    mysqli_stmt_bind_param(
                        $rental_stmt,
                        "iisssssd",
                        $suit_id,
                        $component_id,
                        $rental_type,
                        $customer_name,
                        $customer_phone,
                        $rental_date,
                        $return_date,
                        $price
                    );


                    if (
                        !mysqli_stmt_execute(
                            $rental_stmt
                        )
                    ) {

                        throw new Exception(
                            "Could not save rental."
                        );
                    }


                    mysqli_stmt_close(
                        $rental_stmt
                    );


                    mysqli_commit(
                        $conn
                    );


                    header(
                        "Location: index.php?success=1"
                    );

                    exit;


                } catch (Exception $e) {


                    mysqli_rollback(
                        $conn
                    );


                    $message =
                        $e->getMessage();

                    $message_type =
                        "error";
                }

            }

        }

    }

}


// ==========================================
// GET ALL AVAILABLE SUITS
// ==========================================

$suits_sql = "
    SELECT *
    FROM suits
    ORDER BY name ASC
";

$suits_result =
    mysqli_query(
        $conn,
        $suits_sql
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

    <title>Add Rental - TMF</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .rental-page {
            padding: 30px;
            max-width: 850px;
            margin: auto;
        }

        .rental-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow:
                0 4px 15px
                rgba(0,0,0,.10);
        }

        .rental-card h1 {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .btn-save {
            border: none;
            background: #c9a227;
            color: white;
            padding: 13px 25px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-cancel {
            display: inline-block;
            padding: 13px 25px;
            margin-left: 10px;
            background: #555;
            color: white;
            text-decoration: none;
            border-radius: 7px;
        }

    </style>

</head>


<body>


<?php

require_once __DIR__ .
    "/../includes/sidebar.php";

?>


<div class="main-content">

    <div class="rental-page">

        <div class="rental-card">

            <h1>
                New Rental
            </h1>

            <p>
                Rent a complete suit, coat or pant.
            </p>


            <?php if ($message !== ""): ?>

                <div class="error-message">

                    <?php
                    echo htmlspecialchars(
                        $message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
            >


                <!-- CUSTOMER -->
                
                <div class="form-group">

                    <label>
                        Customer Name
                    </label>

                    <input
                        type="text"
                        name="customer_name"
                        placeholder="Enter customer name"
                        required
                    >

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label>
                        Customer Phone
                    </label>

                    <input
                        type="text"
                        name="customer_phone"
                        placeholder="Enter phone number"
                    >

                </div>


                <!-- SUIT -->

                <div class="form-group">

                    <label>
                        Select Suit
                    </label>

                    <select
                        name="suit_id"
                        required
                    >

                        <option value="">
                            -- Select Suit --
                        </option>


                        <?php while (
                            $suit =
                            mysqli_fetch_assoc(
                                $suits_result
                            )
                        ): ?>

                            <option
                                value="<?php
                                    echo (int)
                                        $suit['id'];
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $suit['name']
                                );

                                ?>

                                -
                                <?php

                                echo htmlspecialchars(
                                    $suit['suit_code']
                                );

                                ?>

                            </option>

                        <?php endwhile; ?>


                    </select>

                </div>


                <!-- RENTAL TYPE -->

                <div class="form-group">

                    <label>
                        Rental Type
                    </label>

                    <select
                        name="rental_type"
                        required
                    >

                        <option value="">
                            -- Select Rental Type --
                        </option>

                        <option value="Complete Suit">
                            Complete Suit (Coat + Pant)
                        </option>

                        <option value="Coat Only">
                            Coat Only
                        </option>

                        <option value="Pant Only">
                            Pant Only
                        </option>

                    </select>

                </div>


                <!-- PRICE -->

                <div class="form-group">

                    <label>
                        Rental Price (RWF)
                    </label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        placeholder="Enter rental price"
                        required
                    >

                </div>


                <!-- RENTAL DATE -->

                <div class="form-group">

                    <label>
                        Rental Date
                    </label>

                    <input
                        type="date"
                        name="rental_date"
                        value="<?php
                            echo date('Y-m-d');
                        ?>"
                        required
                    >

                </div>


                <!-- RETURN DATE -->

                <div class="form-group">

                    <label>
                        Expected Return Date
                    </label>

                    <input
                        type="date"
                        name="return_date"
                    >

                </div>


                <!-- BUTTONS -->

                <button
                    type="submit"
                    name="save_rental"
                    class="btn-save"
                >
                    Save Rental
                </button>


                <a
                    href="index.php"
                    class="btn-cancel"
                >
                    Cancel
                </a>


            </form>

        </div>

    </div>

</div>


</body>

</html>
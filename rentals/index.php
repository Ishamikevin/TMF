<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


// ==========================================
// SUCCESS MESSAGE
// ==========================================

$success = "";

if (isset($_GET['success'])) {

    if ($_GET['success'] == "1") {
        $success = "Rental saved successfully.";
    }

    if ($_GET['success'] == "2") {
        $success = "Item returned successfully.";
    }
}


// ==========================================
// RETURN RENTAL
// ==========================================

if (isset($_GET['return_id'])) {

    $rental_id = (int)$_GET['return_id'];

    if ($rental_id > 0) {

        mysqli_begin_transaction($conn);

        try {

            // Get rental information
            $sql = "
                SELECT *
                FROM rentals
                WHERE id = ?
                LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                throw new Exception("Could not prepare rental query.");
            }

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $rental_id
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $rental = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if (!$rental) {
                throw new Exception("Rental record not found.");
            }


            if ($rental['status'] === 'Returned') {
                throw new Exception("This rental has already been returned.");
            }


            // ======================================
            // RETURN COMPLETE SUIT
            // ======================================

            if ($rental['rental_type'] === 'Complete Suit') {

                $sql = "
                    UPDATE suit_components
                    SET status = 'Available'
                    WHERE suit_id = ?
                ";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $rental['suit_id']
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(
                        "Could not return suit pieces."
                    );
                }

                mysqli_stmt_close($stmt);
            }


            // ======================================
            // RETURN COAT / PANT
            // ======================================

            else {

                if (!empty($rental['component_id'])) {

                    $sql = "
                        UPDATE suit_components
                        SET status = 'Available'
                        WHERE id = ?
                    ";

                    $stmt = mysqli_prepare($conn, $sql);

                    mysqli_stmt_bind_param(
                        $stmt,
                        "i",
                        $rental['component_id']
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception(
                            "Could not return the item."
                        );
                    }

                    mysqli_stmt_close($stmt);
                }
            }


            // ======================================
            // UPDATE RENTAL
            // ======================================

            $return_date = date('Y-m-d');

            $sql = "
                UPDATE rentals
                SET
                    status = 'Returned',
                    return_date = ?
                WHERE id = ?
            ";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $return_date,
                $rental_id
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(
                    "Could not update rental."
                );
            }

            mysqli_stmt_close($stmt);


            mysqli_commit($conn);


            header(
                "Location: index.php?success=2"
            );

            exit;


        } catch (Exception $e) {

            mysqli_rollback($conn);

            die(
                "Error: " .
                htmlspecialchars($e->getMessage())
            );
        }
    }
}


// ==========================================
// GET RENTALS
// ==========================================

$sql = "
    SELECT
        rentals.*,
        suits.name AS suit_name,
        suits.suit_code
    FROM rentals
    LEFT JOIN suits
        ON rentals.suit_id = suits.id
    ORDER BY rentals.id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die(
        "Database error: " .
        mysqli_error($conn)
    );
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

    <title>Rentals - TMF</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }


        .rentals-page {
            padding: 30px;
        }


        /* =====================================
           HEADER
        ===================================== */

        .rentals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }


        .rentals-header h1 {
            margin: 0;
            font-size: 32px;
        }


        .rentals-header p {
            color: #777;
            margin-top: 8px;
        }


        .new-rental-btn {
            background: #c9a227;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 7px;
            font-weight: bold;
        }


        .new-rental-btn:hover {
            background: #a88618;
        }


        /* =====================================
           SUCCESS
        ===================================== */

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 7px;
            margin-bottom: 25px;
        }


        /* =====================================
           TABLE
        ===================================== */

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow:
                0 4px 15px
                rgba(0,0,0,.08);

            overflow-x: auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }


        th {
            background: #222;
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 13px;
        }


        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }


        tr:hover {
            background: #fafafa;
        }


        /* =====================================
           RENTAL TYPE
        ===================================== */

        .rental-type {
            font-weight: bold;
        }


        .type-complete {
            color: #8c6b00;
        }


        .type-coat {
            color: #444;
        }


        .type-pant {
            color: #444;
        }


        /* =====================================
           STATUS
        ===================================== */

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }


        .status-rented {
            background: #f8d7da;
            color: #721c24;
        }


        .status-returned {
            background: #d4edda;
            color: #155724;
        }


        /* =====================================
           RETURN BUTTON
        ===================================== */

        .return-btn {
            display: inline-block;
            padding: 8px 13px;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
        }


        .return-btn:hover {
            background: #146c43;
        }


        .returned-text {
            color: #777;
            font-size: 13px;
        }


        /* =====================================
           PRICE
        ===================================== */

        .price {
            font-weight: bold;
            color: #b28a18;
        }


        /* =====================================
           EMPTY
        ===================================== */

        .empty-box {
            background: white;
            padding: 60px;
            text-align: center;
            border-radius: 12px;
            box-shadow:
                0 4px 15px
                rgba(0,0,0,.08);
        }


        .empty-box p {
            color: #777;
        }


        /* =====================================
           MOBILE
        ===================================== */

        @media (max-width: 700px) {

            .rentals-page {
                padding: 15px;
            }

            .rentals-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

        }

    </style>

</head>


<body>


<?php

require_once __DIR__ .
    "/../includes/sidebar.php";

?>


<div class="main-content">

    <div class="rentals-page">


        <!-- ==================================
             HEADER
        ================================== -->

        <div class="rentals-header">

            <div>

                <h1>
                    Rentals
                </h1>

                <p>
                    Manage rented suits and suit pieces
                </p>

            </div>


            <a
                href="add.php"
                class="new-rental-btn"
            >
                + New Rental
            </a>

        </div>


        <!-- ==================================
             SUCCESS
        ================================== -->

        <?php if ($success !== ""): ?>

            <div class="success-message">

                <?php

                echo htmlspecialchars(
                    $success
                );

                ?>

            </div>

        <?php endif; ?>


        <!-- ==================================
             RENTAL TABLE
        ================================== -->

        <?php if (mysqli_num_rows($result) > 0): ?>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Customer
                            </th>

                            <th>
                                Phone
                            </th>

                            <th>
                                Suit
                            </th>

                            <th>
                                Rental Type
                            </th>

                            <th>
                                Rental Date
                            </th>

                            <th>
                                Return Date
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while (
                            $rental =
                            mysqli_fetch_assoc($result)
                        ): ?>


                            <tr>


                                <!-- CUSTOMER -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $rental[
                                                'customer_name'
                                            ]
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $rental[
                                            'customer_phone'
                                        ]
                                        ?? '-'
                                    );

                                    ?>

                                </td>


                                <!-- SUIT -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $rental['suit_name']
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $rental['suit_name']
                                        );

                                        echo "<br>";

                                        echo "<small>";

                                        echo htmlspecialchars(
                                            $rental['suit_code']
                                        );

                                        echo "</small>";

                                    } else {

                                        echo "Deleted Suit";
                                    }

                                    ?>

                                </td>


                                <!-- RENTAL TYPE -->

                                <td>

                                    <?php

                                    $type =
                                        $rental[
                                            'rental_type'
                                        ];

                                    $type_class =
                                        "type-coat";

                                    if (
                                        $type
                                        ===
                                        "Complete Suit"
                                    ) {

                                        $type_class =
                                            "type-complete";

                                    } elseif (
                                        $type
                                        ===
                                        "Pant Only"
                                    ) {

                                        $type_class =
                                            "type-pant";
                                    }

                                    ?>

                                    <span
                                        class="rental-type
                                        <?php
                                            echo $type_class;
                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $type
                                        );

                                        ?>

                                    </span>

                                </td>


                                <!-- RENTAL DATE -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $rental[
                                            'rental_date'
                                        ]
                                    );

                                    ?>

                                </td>


                                <!-- RETURN DATE -->

                                <td>

                                    <?php

                                    echo !empty(
                                        $rental[
                                            'return_date'
                                        ]
                                    )
                                    ?
                                    htmlspecialchars(
                                        $rental[
                                            'return_date'
                                        ]
                                    )
                                    :
                                    "-";

                                    ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    <span class="price">

                                        <?php

                                        echo number_format(
                                            (float)
                                            $rental[
                                                'price'
                                            ]
                                        );

                                        ?>

                                        RWF

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    if (
                                        $rental['status']
                                        === 'Rented'
                                    ):

                                    ?>

                                        <span
                                            class="status
                                            status-rented"
                                        >
                                            Rented
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="status
                                            status-returned"
                                        >
                                            Returned
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <?php

                                    if (
                                        $rental['status']
                                        === 'Rented'
                                    ):

                                    ?>

                                        <a
                                            href="index.php?return_id=<?php
                                                echo (int)
                                                    $rental['id'];
                                            ?>"
                                            class="return-btn"
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to mark this rental as returned?'
                                                );
                                            "
                                        >
                                            Return
                                        </a>

                                    <?php else: ?>

                                        <span
                                            class="returned-text"
                                        >
                                            Completed
                                        </span>

                                    <?php endif; ?>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-box">

                <h2>
                    No rentals yet
                </h2>

                <p>
                    Start by creating your first rental.
                </p>


                <a
                    href="add.php"
                    class="new-rental-btn"
                >
                    + Create Rental
                </a>

            </div>


        <?php endif; ?>


    </div>

</div>


</body>

</html>
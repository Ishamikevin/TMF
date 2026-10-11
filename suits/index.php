<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


// ==========================================
// SUCCESS MESSAGE
// ==========================================

$success = "";

if (isset($_GET['success']) && $_GET['success'] == "1") {
    $success = "Suit added successfully!";
}


// ==========================================
// GET SUITS
// ==========================================

$sql = "SELECT * FROM suits ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database error: " . mysqli_error($conn));
}


// ==========================================
// FUNCTION TO GET COMPONENT STATUS
// ==========================================

function getComponent(
    $conn,
    $suit_id,
    $component_type
) {

    $sql = "
        SELECT *
        FROM suit_components
        WHERE suit_id = ?
        AND component_type = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $suit_id,
        $component_type
    );

    mysqli_stmt_execute($stmt);

    $component_result =
        mysqli_stmt_get_result($stmt);

    $component =
        mysqli_fetch_assoc($component_result);

    mysqli_stmt_close($stmt);

    return $component;
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

    <title>Suits - TMF</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }


        .suits-page {
            padding: 30px;
        }


        /* =====================================
           HEADER
        ===================================== */

        .suits-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }


        .suits-header h1 {
            margin: 0;
            font-size: 32px;
        }


        .suits-header p {
            margin-top: 8px;
            color: #777;
        }


        .add-suit-btn {
            display: inline-block;
            padding: 12px 20px;
            background: #c9a227;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-weight: bold;
        }


        .add-suit-btn:hover {
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
           GRID
        ===================================== */

        .suits-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(320px, 1fr)
                );

            gap: 25px;
        }


        /* =====================================
           CARD
        ===================================== */

        .suit-card {
            background: white;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 4px 15px
                rgba(0, 0, 0, 0.10);

            border: 1px solid #e5e5e5;
        }


        /* =====================================
           IMAGE
        ===================================== */

        .suit-image {
            width: 100%;
            height: 300px;

            background: #f2f2f2;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;
        }


        .suit-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }


        .no-image {
            text-align: center;
            color: #888;
        }


        .no-image-icon {
            display: block;
            font-size: 55px;
            margin-bottom: 10px;
        }


        /* =====================================
           CONTENT
        ===================================== */

        .suit-content {
            padding: 20px;
        }


        .suit-name {
            margin: 0;
            font-size: 23px;
            color: #222;
        }


        .suit-code {
            margin-top: 7px;
            color: #777;
            font-size: 14px;
        }


        /* =====================================
           DETAILS
        ===================================== */

        .suit-details {
            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 10px;

            margin-top: 20px;
        }


        .detail-box {
            background: #f7f7f7;

            padding: 11px;

            border-radius: 6px;
        }


        .detail-title {
            display: block;

            color: #888;

            font-size: 11px;

            text-transform: uppercase;

            margin-bottom: 5px;
        }


        .detail-value {
            font-weight: bold;

            color: #333;
        }


        /* =====================================
           COMPONENTS
        ===================================== */

        .components-title {
            margin-top: 25px;

            margin-bottom: 12px;

            font-size: 17px;

            font-weight: bold;
        }


        .component {
            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 13px;

            margin-bottom: 8px;

            border-radius: 7px;

            background: #f7f7f7;
        }


        .component-left {
            display: flex;

            flex-direction: column;

            gap: 4px;
        }


        .component-name {
            font-weight: bold;

            color: #333;
        }


        .component-code {
            font-size: 12px;

            color: #888;
        }


        /* =====================================
           STATUS
        ===================================== */

        .status {
            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }


        .available {
            background: #d4edda;

            color: #155724;
        }


        .rented {
            background: #f8d7da;

            color: #721c24;
        }


        /* =====================================
           PRICE
        ===================================== */

        .suit-price {
            margin-top: 20px;

            font-size: 21px;

            font-weight: bold;

            color: #b28a18;
        }


        /* =====================================
           ACTIONS
        ===================================== */

        .suit-actions {
            display: flex;

            gap: 10px;

            margin-top: 20px;
        }


        .edit-btn,
        .delete-btn {
            flex: 1;

            padding: 10px;

            text-align: center;

            text-decoration: none;

            border-radius: 6px;

            color: white;

            font-weight: bold;
        }


        .edit-btn {
            background: #333;
        }


        .delete-btn {
            background: #dc3545;
        }


        .edit-btn:hover {
            background: #111;
        }


        .delete-btn:hover {
            background: #b02a37;
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
                rgba(0,0,0,0.08);
        }


        .empty-box p {
            color: #777;
        }


        /* =====================================
           MOBILE
        ===================================== */

        @media (max-width: 700px) {

            .suits-page {
                padding: 15px;
            }

            .suits-header {
                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

            .suits-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<?php

require_once __DIR__ . "/../includes/sidebar.php";

?>


<div class="main-content">

    <div class="suits-page">


        <!-- ==================================
             HEADER
        ================================== -->

        <div class="suits-header">

            <div>

                <h1>Suits</h1>

                <p>
                    Manage suits, coats and pants
                </p>

            </div>


            <a
                href="add.php"
                class="add-suit-btn"
            >
                + Add Suit
            </a>

        </div>


        <!-- ==================================
             SUCCESS
        ================================== -->

        <?php if ($success !== ""): ?>

            <div class="success-message">

                <?php
                echo htmlspecialchars($success);
                ?>

            </div>

        <?php endif; ?>


        <!-- ==================================
             SUITS
        ================================== -->

        <?php if (mysqli_num_rows($result) > 0): ?>


            <div class="suits-grid">


                <?php while ($suit = mysqli_fetch_assoc($result)): ?>


                    <?php

                    $suit_id =
                        (int)$suit['id'];


                    // Get coat

                    $coat =
                        getComponent(
                            $conn,
                            $suit_id,
                            "Coat"
                        );


                    // Get pant

                    $pant =
                        getComponent(
                            $conn,
                            $suit_id,
                            "Pant"
                        );

                    ?>


                    <div class="suit-card">


                        <!-- ==========================
                             IMAGE
                        =========================== -->

                        <div class="suit-image">


                            <?php

                            $image_name =
                                $suit['image'] ?? '';


                            $image_path =
                                __DIR__ .
                                "/../uploads/" .
                                $image_name;


                            $image_url =
                                "../uploads/" .
                                $image_name;

                            ?>


                            <?php if (
                                !empty($image_name)
                                &&
                                file_exists($image_path)
                            ): ?>


                                <img
                                    src="<?php
                                        echo htmlspecialchars(
                                            $image_url
                                        );
                                    ?>"
                                    alt="<?php
                                        echo htmlspecialchars(
                                            $suit['name']
                                        );
                                    ?>"
                                >


                            <?php else: ?>


                                <div class="no-image">

                                    <span class="no-image-icon">
                                        👔
                                    </span>

                                    No picture

                                </div>


                            <?php endif; ?>


                        </div>


                        <!-- ==========================
                             CONTENT
                        =========================== -->

                        <div class="suit-content">


                            <h2 class="suit-name">

                                <?php

                                echo htmlspecialchars(
                                    $suit['name']
                                );

                                ?>

                            </h2>


                            <div class="suit-code">

                                Code:

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $suit['suit_code']
                                    );

                                    ?>

                                </strong>

                            </div>


                            <!-- ======================
                                 SUIT DETAILS
                            ======================= -->

                            <div class="suit-details">


                                <div class="detail-box">

                                    <span class="detail-title">
                                        Type
                                    </span>

                                    <span class="detail-value">

                                        <?php

                                        echo htmlspecialchars(
                                            $suit['type']
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-box">

                                    <span class="detail-title">
                                        Color
                                    </span>

                                    <span class="detail-value">

                                        <?php

                                        echo htmlspecialchars(
                                            $suit['color']
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-box">

                                    <span class="detail-title">
                                        Size
                                    </span>

                                    <span class="detail-value">

                                        <?php

                                        echo htmlspecialchars(
                                            $suit['size']
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-box">

                                    <span class="detail-title">
                                        Condition
                                    </span>

                                    <span class="detail-value">

                                        <?php

                                        echo htmlspecialchars(
                                            $suit[
                                                'condition_status'
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                            </div>


                            <!-- ======================
                                 COMPONENTS
                            ======================= -->

                            <div class="components-title">

                                Suit Pieces

                            </div>


                            <!-- COAT -->

                            <div class="component">

                                <div class="component-left">

                                    <span class="component-name">

                                        🧥 Coat

                                    </span>


                                    <?php if ($coat): ?>

                                        <span
                                            class="component-code"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $coat[
                                                    'component_code'
                                                ]
                                            );

                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <?php if ($coat): ?>


                                    <?php if (
                                        $coat['status']
                                        === 'Available'
                                    ): ?>


                                        <span
                                            class="status available"
                                        >
                                            Available
                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="status rented"
                                        >
                                            Rented
                                        </span>


                                    <?php endif; ?>


                                <?php else: ?>


                                    <span
                                        class="status rented"
                                    >
                                        Not Added
                                    </span>


                                <?php endif; ?>


                            </div>


                            <!-- PANT -->

                            <div class="component">

                                <div class="component-left">

                                    <span class="component-name">

                                        👖 Pant

                                    </span>


                                    <?php if ($pant): ?>

                                        <span
                                            class="component-code"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $pant[
                                                    'component_code'
                                                ]
                                            );

                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <?php if ($pant): ?>


                                    <?php if (
                                        $pant['status']
                                        === 'Available'
                                    ): ?>


                                        <span
                                            class="status available"
                                        >
                                            Available
                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="status rented"
                                        >
                                            Rented
                                        </span>


                                    <?php endif; ?>


                                <?php else: ?>


                                    <span
                                        class="status rented"
                                    >
                                        Not Added
                                    </span>


                                <?php endif; ?>


                            </div>


                            <!-- PRICE -->

                            <div class="suit-price">

                                <?php

                                echo number_format(
                                    (float)$suit['price']
                                );

                                ?>

                                RWF

                            </div>


                            <!-- ACTIONS -->

                            <div class="suit-actions">


                                <a
                                    href="edit.php?id=<?php
                                        echo $suit_id;
                                    ?>"
                                    class="edit-btn"
                                >
                                    Edit
                                </a>


                                <a
                                    href="delete.php?id=<?php
                                        echo $suit_id;
                                    ?>"
                                    class="delete-btn"
                                    onclick="
                                        return confirm(
                                            'Are you sure you want to delete this suit?'
                                        );
                                    "
                                >
                                    Delete
                                </a>


                            </div>


                        </div>

                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <div class="empty-box">

                <h2>
                    No suits found
                </h2>

                <p>
                    You have not added any suits yet.
                </p>


                <a
                    href="add.php"
                    class="add-suit-btn"
                >
                    + Add Your First Suit
                </a>

            </div>


        <?php endif; ?>


    </div>

</div>


</body>

</html>
<?php

session_start();

require_once __DIR__ . "/../includes/db.php";


$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Invalid suit ID.");
}


// ==========================================
// GET SUIT
// ==========================================

$sql = "
    SELECT *
    FROM suits
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$suit =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$suit) {
    die("Suit not found.");
}


$message = "";
$message_type = "";


// ==========================================
// UPDATE
// ==========================================

if (isset($_POST['update_suit'])) {

    $suit_code =
        trim($_POST['suit_code'] ?? '');

    $name =
        trim($_POST['name'] ?? '');

    $type =
        trim($_POST['type'] ?? '');

    $color =
        trim($_POST['color'] ?? '');

    $size =
        trim($_POST['size'] ?? '');

    $condition_status =
        trim(
            $_POST['condition_status'] ?? ''
        );

    $price =
        (float)($_POST['price'] ?? 0);


    $image_name =
        $suit['image'];


    if (
        $suit_code === "" ||
        $name === ""
    ) {

        $message =
            "Suit code and name are required.";

        $message_type =
            "error";

    } else {


        // ==================================
        // IMAGE
        // ==================================

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error']
            === UPLOAD_ERR_OK
        ) {

            $upload_dir =
                __DIR__ . "/../uploads/";

            if (!is_dir($upload_dir)) {

                mkdir(
                    $upload_dir,
                    0777,
                    true
                );
            }


            $extension =
                strtolower(
                    pathinfo(
                        $_FILES['image']['name'],
                        PATHINFO_EXTENSION
                    )
                );


            $allowed =
                [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];


            if (
                !in_array(
                    $extension,
                    $allowed
                )
            ) {

                $message =
                    "Invalid image type.";

                $message_type =
                    "error";

            } else {

                $image_name =
                    uniqid(
                        "suit_",
                        true
                    )
                    . "."
                    . $extension;


                move_uploaded_file(
                    $_FILES['image']['tmp_name'],
                    $upload_dir .
                    $image_name
                );
            }
        }


        if ($message_type !== "error") {

            $sql = "
                UPDATE suits
                SET
                    suit_code = ?,
                    name = ?,
                    type = ?,
                    color = ?,
                    size = ?,
                    condition_status = ?,
                    price = ?,
                    image = ?
                WHERE id = ?
            ";


            $stmt =
                mysqli_prepare(
                    $conn,
                    $sql
                );


            mysqli_stmt_bind_param(
                $stmt,
                "ssssssdsi",
                $suit_code,
                $name,
                $type,
                $color,
                $size,
                $condition_status,
                $price,
                $image_name,
                $id
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                header(
                    "Location: index.php?success=2"
                );

                exit;

            } else {

                $message =
                    "Database error: "
                    . mysqli_error($conn);

                $message_type =
                    "error";
            }


            mysqli_stmt_close($stmt);
        }
    }


    // Refresh data

    $suit['suit_code'] =
        $suit_code;

    $suit['name'] =
        $name;

    $suit['type'] =
        $type;

    $suit['color'] =
        $color;

    $suit['size'] =
        $size;

    $suit['condition_status'] =
        $condition_status;

    $suit['price'] =
        $price;

    $suit['image'] =
        $image_name;
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

    <title>Edit Suit - SuitHub</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >
<style>
    /* ================================
   ACTION BUTTONS
================================ */

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    align-items: center;
}

/* Update Suit button */
.btn-update {
    display: inline-block;
    padding: 13px 28px;

    background: #d4af37;
    color: #111;

    border: none;
    border-radius: 8px;

    font-size: 16px;
    font-weight: bold;

    text-decoration: none;
    cursor: pointer;

    transition: all 0.3s ease;

    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.btn-update:hover {
    background: #b9972e;
    color: white;

    transform: translateY(-2px);

    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}


/* Cancel button */
.btn-cancel {
    display: inline-block;
    padding: 13px 28px;

    background: #222;
    color: white;

    border: none;
    border-radius: 8px;

    font-size: 16px;
    font-weight: bold;

    text-decoration: none;
    cursor: pointer;

    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #444;

    transform: translateY(-2px);

    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}
</style>
</head>

<body>


<?php

require_once __DIR__ .
    "/../includes/sidebar.php";

?>


<div class="main-content">


    <div class="form-container">


        <div class="form-card">


            <h1>
                Edit Suit
            </h1>


            <p class="form-description">
                Update suit information
            </p>


            <?php if ($message !== ""): ?>

                <div class="<?php
                    echo $message_type === "error"
                        ? "error-message"
                        : "success-message";
                ?>">

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php endif; ?>


            <?php if (!empty($suit['image'])): ?>

                <img
                    src="../uploads/<?php
                        echo htmlspecialchars(
                            $suit['image']
                        );
                    ?>"
                    class="suit-image-preview"
                    alt="Suit"
                >

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <div class="form-group">

                    <label>
                        Suit Code
                    </label>

                    <input
                        type="text"
                        name="suit_code"
                        value="<?php
                            echo htmlspecialchars(
                                $suit['suit_code']
                            );
                        ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Suit Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?php
                            echo htmlspecialchars(
                                $suit['name']
                            );
                        ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Suit Type
                    </label>

                    <select name="type">

                        <option value="">
                            Select type
                        </option>

                        <?php

                        $types = [
                            'Business',
                            'Wedding',
                            'Tuxedo',
                            'Casual'
                        ];

                        foreach ($types as $item):

                        ?>

                            <option
                                value="<?php
                                    echo $item;
                                ?>"
                                <?php
                                echo
                                    $suit['type']
                                    === $item
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo $item;
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Color
                    </label>

                    <input
                        type="text"
                        name="color"
                        value="<?php
                            echo htmlspecialchars(
                                $suit['color']
                                ?? ''
                            );
                        ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Size
                    </label>

                    <select name="size">

                        <?php

                        $sizes = [
                            '',
                            'S',
                            'M',
                            'L',
                            'XL',
                            'XXL'
                        ];

                        foreach ($sizes as $item):

                        ?>

                            <option
                                value="<?php
                                    echo $item;
                                ?>"
                                <?php
                                echo
                                    $suit['size']
                                    === $item
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php

                                echo $item === ''
                                    ? 'Select size'
                                    : $item;

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Condition
                    </label>

                    <select
                        name="condition_status"
                    >

                        <?php

                        $conditions = [
                            '',
                            'New',
                            'Good',
                            'Used'
                        ];

                        foreach (
                            $conditions
                            as $item
                        ):

                        ?>

                            <option
                                value="<?php
                                    echo $item;
                                ?>"
                                <?php
                                echo
                                    $suit[
                                        'condition_status'
                                    ]
                                    === $item
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php

                                echo $item === ''
                                    ? 'Select condition'
                                    : $item;

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Rental Price
                    </label>

                    <input
                        type="number"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?php
                            echo htmlspecialchars(
                                $suit['price']
                            );
                        ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Change Picture
                    </label>

                    <input
                        type="file"
                        name="image"
                        accept="image/*"
                    >

                </div>


<div class="form-actions">

    <button type="submit" name="update_suit" class="btn-update">
        Update Suit
    </button>

    <a href="index.php" class="btn-cancel">
        Cancel
    </a>

</div>


            </form>


        </div>

    </div>

</div>


</body>

</html>
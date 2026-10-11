<?php

session_start();

require_once __DIR__ . "/../includes/db.php";

$message = "";
$message_type = "";


/* =========================================
   GET USER ID
========================================= */

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id && isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
}

if (!$user_id && isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
}


/* =========================================
   SAVE SUIT
========================================= */

if (isset($_POST['save_suit'])) {

    $suit_code = trim($_POST['suit_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $condition_status = trim($_POST['condition_status'] ?? '');
    $price = trim($_POST['price'] ?? '');

    $status = "Available";


    /* =========================================
       VALIDATION
    ========================================= */

    if (!$user_id) {

        $message = "You are not logged in.";
        $message_type = "error";

    } elseif (
        $suit_code === "" ||
        $name === "" ||
        $type === "" ||
        $color === "" ||
        $size === "" ||
        $condition_status === "" ||
        $price === ""
    ) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif (!is_numeric($price) || $price < 0) {

        $message = "Please enter a valid price.";
        $message_type = "error";

    } else {


        /* =========================================
           CHECK DUPLICATE CODE
        ========================================= */

        $check_sql = "SELECT id FROM suits WHERE suit_code = ?";

        $check_stmt = $conn->prepare($check_sql);

        if ($check_stmt === false) {

            $message =
                "Database error: " .
                $conn->error;

            $message_type = "error";

        } else {

            $check_stmt->bind_param(
                "s",
                $suit_code
            );

            $check_stmt->execute();

            $check_stmt->store_result();


            if ($check_stmt->num_rows > 0) {

                $message =
                    "A suit with this code already exists.";

                $message_type = "error";

            }


            $check_stmt->close();
        }


        /* =========================================
           IMAGE
        ========================================= */

        $image_name = null;


        if ($message_type !== "error") {

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES['image']['error']
                    !== UPLOAD_ERR_OK
                ) {

                    $message =
                        "There was an error uploading the image.";

                    $message_type = "error";

                } else {

                    $allowed_extensions = [
                        "jpg",
                        "jpeg",
                        "png",
                        "gif",
                        "webp"
                    ];


                    $original_name =
                        $_FILES['image']['name'];


                    $extension = strtolower(
                        pathinfo(
                            $original_name,
                            PATHINFO_EXTENSION
                        )
                    );


                    $file_size =
                        $_FILES['image']['size'];


                    if (
                        !in_array(
                            $extension,
                            $allowed_extensions
                        )
                    ) {

                        $message =
                            "Only JPG, JPEG, PNG, GIF and WEBP images are allowed.";

                        $message_type = "error";

                    } elseif (
                        $file_size > 5 * 1024 * 1024
                    ) {

                        $message =
                            "Image must be less than 5MB.";

                        $message_type = "error";

                    } else {


                        /* =================================
                           UPLOAD DIRECTORY
                        ================================= */

                        $upload_directory =
                            __DIR__ . "/../uploads/";


                        if (!is_dir($upload_directory)) {

                            mkdir(
                                $upload_directory,
                                0777,
                                true
                            );
                        }


                        /* =================================
                           IMAGE NAME
                        ================================= */

                        $image_name =
                            "suit_" .
                            time() .
                            "_" .
                            uniqid() .
                            "." .
                            $extension;


                        $image_path =
                            $upload_directory .
                            $image_name;


                        /* =================================
                           MOVE IMAGE
                        ================================= */

                        if (
                            !move_uploaded_file(
                                $_FILES['image']['tmp_name'],
                                $image_path
                            )
                        ) {

                            $message =
                                "Failed to save the image.";

                            $message_type = "error";

                            $image_name = null;
                        }
                    }
                }
            }
        }


        /* =========================================
           INSERT SUIT
        ========================================= */

        if ($message_type !== "error") {

            $sql = "
                INSERT INTO suits
                (
                    user_id,
                    suit_code,
                    name,
                    type,
                    color,
                    size,
                    condition_status,
                    price,
                    status,
                    image
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";


            $stmt = $conn->prepare($sql);


            if ($stmt === false) {

                $message =
                    "Database error: " .
                    $conn->error;

                $message_type = "error";

            } else {

                $price = (float)$price;


                $stmt->bind_param(
                    "issssssiss",
                    $user_id,
                    $suit_code,
                    $name,
                    $type,
                    $color,
                    $size,
                    $condition_status,
                    $price,
                    $status,
                    $image_name
                );


if ($stmt->execute()) {

    // Get the ID of the newly created suit
    $suit_id = $conn->insert_id;


    // ==========================================
    // CREATE COAT
    // ==========================================

    $coat_code = "COAT-" . $suit_code;

    $coat_sql = "
        INSERT INTO suit_components
        (
            suit_id,
            component_type,
            component_code,
            status,
            price,
            image
        )
        VALUES (?, 'Coat', ?, 'Available', ?, ?)
    ";

    $coat_stmt = $conn->prepare($coat_sql);

    if ($coat_stmt) {

        $coat_stmt->bind_param(
            "isds",
            $suit_id,
            $coat_code,
            $price,
            $image_name
        );

        $coat_stmt->execute();

        $coat_stmt->close();
    }


    // ==========================================
    // CREATE PANT
    // ==========================================

    $pant_code = "PANT-" . $suit_code;

    $pant_sql = "
        INSERT INTO suit_components
        (
            suit_id,
            component_type,
            component_code,
            status,
            price,
            image
        )
        VALUES (?, 'Pant', ?, 'Available', ?, ?)
    ";

    $pant_stmt = $conn->prepare($pant_sql);

    if ($pant_stmt) {

        $pant_stmt->bind_param(
            "isds",
            $suit_id,
            $pant_code,
            $price,
            $image_name
        );

        $pant_stmt->execute();

        $pant_stmt->close();
    }


    // Close original suit statement
    $stmt->close();


    // Go back to suits page
    header("Location: index.php?success=1");

    exit;
} else {

                    $message =
                        "Failed to save suit: " .
                        $stmt->error;

                    $message_type = "error";

                    $stmt->close();


                    /* Delete image if database failed */

                    if (
                        $image_name &&
                        file_exists(
                            __DIR__ .
                            "/../uploads/" .
                            $image_name
                        )
                    ) {

                        unlink(
                            __DIR__ .
                            "/../uploads/" .
                            $image_name
                        );
                    }
                }
            }
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

    <title>Add Suit - SuitHub</title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .image-preview {
            display: none;
            width: 200px;
            height: 250px;
            object-fit: cover;
            margin-top: 15px;
            border-radius: 10px;
            border: 2px solid #d4af37;
        }

        .file-help {
            display: block;
            margin-top: 5px;
            color: #777;
            font-size: 13px;
        }

    </style>

</head>


<body>


<?php

require_once __DIR__ . "/../includes/sidebar.php";

?>


<div class="main-content">


    <div class="topbar">

        <div>

            <h1>Add Suit</h1>

            <p>
                Add a new suit to your inventory
            </p>

        </div>

    </div>


    <?php if ($message !== ""): ?>

        <div class="alert alert-error">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <div class="form-card">

        <h2>Suit Information</h2>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- SUIT CODE -->

            <div class="form-group">

                <label>
                    Suit Code
                </label>

                <input
                    type="text"
                    name="suit_code"
                    placeholder="Example: S001"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST['suit_code'] ?? ''
                        );
                    ?>"
                    required
                >

            </div>


            <!-- NAME -->

            <div class="form-group">

                <label>
                    Suit Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="Example: Classic Black Suit"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST['name'] ?? ''
                        );
                    ?>"
                    required
                >

            </div>


            <!-- TYPE -->

            <div class="form-group">

                <label>
                    Suit Type
                </label>

                <select
                    name="type"
                    required
                >

                    <option value="">
                        Select type
                    </option>

                    <option value="Business">
                        Business
                    </option>

                    <option value="Wedding">
                        Wedding
                    </option>

                    <option value="Tuxedo">
                        Tuxedo
                    </option>

                    <option value="Formal">
                        Formal
                    </option>

                    <option value="Casual">
                        Casual
                    </option>

                </select>

            </div>


            <!-- COLOR -->

            <div class="form-group">

                <label>
                    Color
                </label>

                <input
                    type="text"
                    name="color"
                    placeholder="Example: Black"
                    required
                >

            </div>


            <!-- SIZE -->

            <div class="form-group">

                <label>
                    Size
                </label>

                <select
                    name="size"
                    required
                >

                    <option value="">
                        Select size
                    </option>

                    <option value="S">
                        44
                    </option>

                    <option value="M">
                        46
                    </option>

                    <option value="L">
                        48
                    </option>

                    <option value="XL">
                        50
                    </option>

                    <option value="XXL">
                        52
                    </option>

                    <option value="XXL">
                        54
                    </option>

                    <option value="XXL">
                        56
                    </option>
                </select>

            </div>


            <!-- CONDITION -->

            <div class="form-group">

                <label>
                    Condition
                </label>

                <select
                    name="condition_status"
                    required
                >

                    <option value="">
                        Select condition
                    </option>

                    <option value="Excellent">
                        Excellent
                    </option>

                    <option value="Good">
                        Good
                    </option>

                    <option value="Fair">
                        Fair
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
                    placeholder="Example: 25000"
                    min="0"
                    step="0.01"
                    required
                >

            </div>


            <!-- IMAGE -->

            <div class="form-group">

                <label>
                    Suit Picture
                </label>

                <input
                    type="file"
                    name="image"
                    id="image"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                >

                <span class="file-help">
                    JPG, PNG, GIF or WEBP. Maximum 5MB.
                </span>


                <img
                    id="imagePreview"
                    class="image-preview"
                    alt="Suit preview"
                >

            </div>


            <!-- BUTTON -->

            <div style="margin-top:25px;">

                <button
                    type="submit"
                    name="save_suit"
                    class="btn btn-gold"
                >
                    Save Suit
                </button>


                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>

</div>


<script>

const imageInput =
    document.getElementById("image");

const imagePreview =
    document.getElementById("imagePreview");


imageInput.addEventListener(
    "change",
    function () {

        const file = this.files[0];

        if (file) {

            imagePreview.src =
                URL.createObjectURL(file);

            imagePreview.style.display =
                "block";

        } else {

            imagePreview.style.display =
                "none";

        }

    }
);

</script>


</body>

</html>
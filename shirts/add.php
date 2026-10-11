<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| CREATE UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

$upload_dir = "../uploads/shirts/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/*
|--------------------------------------------------------------------------
| ADD SHIRT
|--------------------------------------------------------------------------
*/

if (isset($_POST['add_shirt'])) {

    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $shirt_code = trim($_POST['shirt_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $condition_status = trim($_POST['condition_status'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $made_in_rwanda = $_POST['made_in_rwanda'] ?? 'No';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        empty($shirt_code) ||
        empty($name) ||
        empty($type) ||
        empty($color) ||
        empty($size) ||
        empty($condition_status) ||
        empty($price)
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!is_numeric($price) || $price < 0) {

        $message = "Please enter a valid rental price.";
        $message_type = "error";

    } elseif (!in_array($made_in_rwanda, ['Yes', 'No'])) {

        $message = "Invalid Made in Rwanda selection.";
        $message_type = "error";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE SHIRT CODE
        |--------------------------------------------------------------------------
        */

        $check_sql = "SELECT id FROM shirts WHERE shirt_code = ?";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $message = "Database error: " . $conn->error;
            $message_type = "error";

        } else {

            $check_stmt->bind_param("s", $shirt_code);

            $check_stmt->execute();

            $check_result = $check_stmt->get_result();


            if ($check_result->num_rows > 0) {

                $message = "A shirt with this code already exists.";
                $message_type = "error";

                $check_stmt->close();

            } else {

                $check_stmt->close();


                $image_name = null;


                if (
                    isset($_FILES['image']) &&
                    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
                ) {

                    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

                        $message = "There was an error uploading the shirt picture.";
                        $message_type = "error";

                    } else {

                        $file_tmp = $_FILES['image']['tmp_name'];
                        $original_name = $_FILES['image']['name'];
                        $file_size = $_FILES['image']['size'];


                        /*
                        |--------------------------------------------------------------------------
                        | CHECK FILE SIZE
                        |--------------------------------------------------------------------------
                        */

                        if ($file_size > 5 * 1024 * 1024) {

                            $message = "The image must be less than 5MB.";
                            $message_type = "error";

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | CHECK IMAGE TYPE
                            |--------------------------------------------------------------------------
                            */

                            $image_info = getimagesize($file_tmp);

                            if ($image_info === false) {

                                $message = "Please upload a valid image.";
                                $message_type = "error";

                            } else {

                                $allowed_types = [
                                    'image/jpeg' => 'jpg',
                                    'image/png'  => 'png',
                                    'image/webp' => 'webp'
                                ];

                                $mime_type = $image_info['mime'];


                                if (!array_key_exists($mime_type, $allowed_types)) {

                                    $message = "Only JPG, PNG and WEBP images are allowed.";
                                    $message_type = "error";

                                } else {

                                    /*
                                    |--------------------------------------------------------------------------
                                    | CREATE UNIQUE IMAGE NAME
                                    |--------------------------------------------------------------------------
                                    */

                                    $extension = $allowed_types[$mime_type];

                                    $image_name =
                                        'shirt_' .
                                        time() .
                                        '_' .
                                        bin2hex(random_bytes(5)) .
                                        '.' .
                                        $extension;

                                    $image_path = $upload_dir . $image_name;


                                    /*
                                    |--------------------------------------------------------------------------
                                    | MOVE IMAGE
                                    |--------------------------------------------------------------------------
                                    */

                                    if (!move_uploaded_file($file_tmp, $image_path)) {

                                        $message = "Failed to save the shirt picture.";
                                        $message_type = "error";

                                        $image_name = null;
                                    }
                                }
                            }
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | INSERT SHIRT
                |--------------------------------------------------------------------------
                */

                if (empty($message)) {

                    $status = "Available";

                    $insert_sql = "
                        INSERT INTO shirts
                        (
                            user_id,
                            shirt_code,
                            name,
                            type,
                            color,
                            size,
                            condition_status,
                            price,
                            status,
                            image,
                            made_in_rwanda
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";

                    $stmt = $conn->prepare($insert_sql);


                    if (!$stmt) {

                        /*
                        |--------------------------------------------------------------------------
                        | DELETE IMAGE IF DATABASE PREPARATION FAILS
                        |--------------------------------------------------------------------------
                        */

                        if ($image_name !== null) {

                            $uploaded_file = $upload_dir . $image_name;

                            if (file_exists($uploaded_file)) {
                                unlink($uploaded_file);
                            }
                        }

                        $message = "Database error: " . $conn->error;
                        $message_type = "error";

                    } else {

                        $price = (float)$price;

                        $stmt->bind_param(
                            "issssssssss",
                            $user_id,
                            $shirt_code,
                            $name,
                            $type,
                            $color,
                            $size,
                            $condition_status,
                            $price,
                            $status,
                            $image_name,
                            $made_in_rwanda
                        );


                        if ($stmt->execute()) {

                            /*
                            |--------------------------------------------------------------------------
                            | SUCCESS
                            |--------------------------------------------------------------------------
                            */

                            $stmt->close();

                            header("Location: index.php?success=shirt_added");
                            exit();

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | DELETE IMAGE IF INSERT FAILS
                            |--------------------------------------------------------------------------
                            */

                            if ($image_name !== null) {

                                $uploaded_file = $upload_dir . $image_name;

                                if (file_exists($uploaded_file)) {
                                    unlink($uploaded_file);
                                }
                            }

                            $message = "Failed to add shirt: " . $stmt->error;
                            $message_type = "error";

                            $stmt->close();
                        }
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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Shirt - SuitHub</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;

            font-family: Arial, Helvetica, sans-serif;

            background: #f5f5f5;

            color: #222;
        }


        /* =========================================
           MAIN CONTENT
        ========================================= */

        .main-content {

            margin-left: 260px;

            padding: 40px;

            min-height: 100vh;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .page-header {

            margin-bottom: 30px;
        }


        .page-header h1 {

            margin: 0 0 8px;

            font-size: 36px;

            color: #111;
        }


        .page-header p {

            margin: 0;

            color: #777;

            font-size: 16px;
        }


        /* =========================================
           FORM CARD
        ========================================= */

        .form-card {

            max-width: 850px;

            margin: 0 auto;

            background: white;

            padding: 40px;

            border-radius: 16px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.08);
        }


        /* =========================================
           FORM GRID
        ========================================= */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 24px;
        }


        .form-group {

            width: 100%;
        }


        .form-group.full-width {

            grid-column: 1 / -1;
        }


        .form-group label {

            display: block;

            margin-bottom: 9px;

            font-size: 16px;

            font-weight: 700;

            color: #222;
        }


        .required {

            color: #d4af37;
        }


        /* =========================================
           INPUTS
        ========================================= */

        .form-group input,
        .form-group select {

            width: 100%;

            height: 52px;

            padding: 0 15px;

            border: 1px solid #d1d1d1;

            border-radius: 8px;

            background: white;

            color: #222;

            font-size: 15px;

            outline: none;

            transition: 0.25s ease;
        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #d4af37;

            box-shadow:
                0 0 0 3px rgba(212, 175, 55, 0.12);
        }


        /* =========================================
           FILE UPLOAD
        ========================================= */

        .file-input {

            padding: 10px !important;

            height: auto !important;

            cursor: pointer;
        }


        .file-help {

            display: block;

            margin-top: 7px;

            color: #888;

            font-size: 13px;
        }


        /* =========================================
           MADE IN RWANDA
        ========================================= */

        .rwanda-box {

            display: flex;

            gap: 12px;
        }


        .rwanda-option {

            flex: 1;

            border: 1px solid #d1d1d1;

            border-radius: 8px;

            padding: 14px;

            cursor: pointer;

            text-align: center;

            transition: 0.25s ease;
        }


        .rwanda-option:hover {

            border-color: #d4af37;

            background: #fffaf0;
        }


        .rwanda-option input {

            width: auto;

            height: auto;

            margin-right: 7px;
        }


        /* =========================================
           MESSAGE
        ========================================= */

        .message {

            max-width: 850px;

            margin: 0 auto 25px;

            padding: 15px;

            border-radius: 8px;

            text-align: center;

            font-weight: 600;
        }


        .message.error {

            background: #f8d7da;

            border: 1px solid #f1aeb5;

            color: #842029;
        }


        .message.success {

            background: #d1e7dd;

            border: 1px solid #a3cfbb;

            color: #0f5132;
        }


        /* =========================================
           BUTTONS
        ========================================= */

        .form-actions {

            display: flex;

            gap: 15px;

            margin-top: 30px;

            padding-top: 25px;

            border-top: 1px solid #eee;
        }


        .btn {

            display: inline-flex;

            justify-content: center;

            align-items: center;

            min-width: 150px;

            height: 50px;

            padding: 0 25px;

            border: none;

            border-radius: 8px;

            font-size: 16px;

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            transition: all 0.25s ease;
        }


        .btn-save {

            background: #d4af37;

            color: #111;

            box-shadow:
                0 5px 12px rgba(0, 0, 0, 0.15);
        }


        .btn-save:hover {

            background: #bd9826;

            color: white;

            transform: translateY(-2px);

            box-shadow:
                0 8px 18px rgba(0, 0, 0, 0.2);
        }


        .btn-cancel {

            background: #222;

            color: white;
        }


        .btn-cancel:hover {

            background: #444;

            transform: translateY(-2px);
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 900px) {

            .main-content {

                margin-left: 0;

                padding: 25px;
            }

            .form-grid {

                grid-template-columns: 1fr;
            }

            .form-group.full-width {

                grid-column: auto;
            }
        }


        @media (max-width: 600px) {

            .main-content {

                padding: 15px;
            }

            .form-card {

                padding: 25px 20px;
            }

            .page-header h1 {

                font-size: 28px;
            }

            .rwanda-box {

                flex-direction: column;
            }

            .form-actions {

                flex-direction: column;
            }

            .btn {

                width: 100%;
            }
        }

    </style>

</head>


<body>


<?php
/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
|
| This requires:
| TMF/includes/sidebar.php
|
*/

require_once "../includes/sidebar.php";
?>


<div class="main-content">


    <!-- =========================================
         PAGE HEADER
    ========================================= -->

    <div class="page-header">

        <h1>Add Shirt</h1>

        <p>
            Add a new shirt to your SuitHub inventory.
        </p>

    </div>


    <!-- =========================================
         MESSAGE
    ========================================= -->

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- =========================================
         FORM CARD
    ========================================= -->

    <div class="form-card">


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="form-grid">


                <!-- =================================
                     SHIRT CODE
                ================================== -->

                <div class="form-group">

                    <label for="shirt_code">

                        Shirt Code
                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        id="shirt_code"
                        name="shirt_code"
                        placeholder="Example: SH001"
                        value="<?php echo htmlspecialchars($_POST['shirt_code'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- =================================
                     SHIRT NAME
                ================================== -->

                <div class="form-group">

                    <label for="name">

                        Shirt Name
                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Example: Classic White Shirt"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- =================================
                     SHIRT TYPE
                ================================== -->

                <div class="form-group">

                    <label for="type">

                        Shirt Type
                        <span class="required">*</span>

                    </label>

                    <select
                        id="type"
                        name="type"
                        required
                    >

                        <option value="">
                            Select shirt type
                        </option>

                        <option value="Formal"
                            <?php
                            echo (($_POST['type'] ?? '') === 'Formal')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Formal Shirt
                        </option>

                        <option value="Casual"
                            <?php
                            echo (($_POST['type'] ?? '') === 'Casual')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Casual Shirt
                        </option>

                        <option value="Dress Shirt"
                            <?php
                            echo (($_POST['type'] ?? '') === 'Dress Shirt')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Dress Shirt
                        </option>

                        <option value="Traditional"
                            <?php
                            echo (($_POST['type'] ?? '') === 'Traditional')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Traditional Shirt
                        </option>

                        <option value="Other"
                            <?php
                            echo (($_POST['type'] ?? '') === 'Other')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <!-- =================================
                     COLOR
                ================================== -->

                <div class="form-group">

                    <label for="color">

                        Color
                        <span class="required">*</span>

                    </label>

                    <input
                        type="text"
                        id="color"
                        name="color"
                        placeholder="Example: White"
                        value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- =================================
                     SIZE
                ================================== -->

                <div class="form-group">

                    <label for="size">

                        Size
                        <span class="required">*</span>

                    </label>

                    <select
                        id="size"
                        name="size"
                        required
                    >

                        <option value="">
                            Select size
                        </option>

                        <?php
                        $sizes = [
                            'XS',
                            'S',
                            'M',
                            'L',
                            'XL',
                            'XXL',
                            'XXXL'
                        ];

                        foreach ($sizes as $shirt_size):

                        ?>

                            <option
                                value="<?php echo $shirt_size; ?>"
                                <?php
                                echo (($_POST['size'] ?? '') === $shirt_size)
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php echo $shirt_size; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- =================================
                     CONDITION
                ================================== -->

                <div class="form-group">

                    <label for="condition_status">

                        Condition
                        <span class="required">*</span>

                    </label>

                    <select
                        id="condition_status"
                        name="condition_status"
                        required
                    >

                        <option value="">
                            Select condition
                        </option>

                        <option value="New"
                            <?php
                            echo (($_POST['condition_status'] ?? '') === 'New')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            New
                        </option>

                        <option value="Excellent"
                            <?php
                            echo (($_POST['condition_status'] ?? '') === 'Excellent')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Excellent
                        </option>

                        <option value="Good"
                            <?php
                            echo (($_POST['condition_status'] ?? '') === 'Good')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Good
                        </option>

                        <option value="Fair"
                            <?php
                            echo (($_POST['condition_status'] ?? '') === 'Fair')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Fair
                        </option>

                    </select>

                </div>


                <!-- =================================
                     RENTAL PRICE
                ================================== -->

                <div class="form-group">

                    <label for="price">

                        Rental Price (RWF)
                        <span class="required">*</span>

                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        placeholder="Example: 5000"
                        min="0"
                        step="0.01"
                        value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- =================================
                     MADE IN RWANDA
                ================================== -->

                <div class="form-group full-width">

                    <label>

                        Made in Rwanda?
                        <span class="required">*</span>

                    </label>


                    <div class="rwanda-box">


                        <label class="rwanda-option">

                            <input
                                type="radio"
                                name="made_in_rwanda"
                                value="Yes"
                                <?php
                                echo (($_POST['made_in_rwanda'] ?? 'Yes') === 'Yes')
                                    ? 'checked'
                                    : '';
                                ?>
                            >

                            🇷🇼 Yes, Made in Rwanda

                        </label>


                        <label class="rwanda-option">

                            <input
                                type="radio"
                                name="made_in_rwanda"
                                value="No"
                                <?php
                                echo (($_POST['made_in_rwanda'] ?? '') === 'No')
                                    ? 'checked'
                                    : '';
                                ?>
                            >

                            No, Imported

                        </label>


                    </div>

                </div>


                <!-- =================================
                     SHIRT IMAGE
                ================================== -->

                <div class="form-group full-width">

                    <label for="image">

                        Shirt Picture

                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        class="file-input"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <span class="file-help">

                        JPG, PNG or WEBP. Maximum size: 5MB.

                    </span>

                </div>


            </div>


            <!-- =================================
                 ACTION BUTTONS
            ================================== -->

            <div class="form-actions">


                <button
                    type="submit"
                    name="add_shirt"
                    class="btn btn-save"
                >

                    Add Shirt

                </button>


                <a
                    href="index.php"
                    class="btn btn-cancel"
                >

                    Cancel

                </a>


            </div>


        </form>


    </div>


</div>


</body>

</html>
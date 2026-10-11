<?php

session_start();
require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {

        // Check if email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "Email already exists.";
        } else {

            // Secure password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert user
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $email,
                $hashed_password
            ]);

            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TMF</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>

        * {
            box-sizing: border-box;
        } 
    body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;

            background: #f4f4f4;

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .auth-container {

            width: 100%;
            max-width: 600px;

            background: white;

            padding: 50px;

            border-radius: 15px;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .auth-container h1 {

            text-align: center;

            margin: 0 0 30px 0;

            font-size: 42px;

            color: #111;
        }

        .auth-container h2 {

            text-align: center;

            margin-bottom: 40px;

            font-size: 32px;

            color: #d4af37;
        }

        .form-group {

            margin-bottom: 20px;
        }

        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-size: 18px;

            font-weight: bold;

            color: #222;
        }

        .form-group input {

            width: 100%;

            padding: 15px;

            border: 1px solid #ccc;

            border-radius: 8px;

            font-size: 16px;

            outline: none;
        }

        .form-group input:focus {

            border-color: #d4af37;

            box-shadow: 0 0 5px rgba(212, 175, 55, 0.4);
        }

        button {

            width: 100%;

            padding: 15px;

            margin-top: 10px;

            border: none;

            border-radius: 8px;

            background: #d4af37;

            color: #000;

            font-size: 18px;

            font-weight: bold;

            cursor: pointer;
        }

        button:hover {

            background: #c19b2e;
        }

        .message {

            padding: 12px;

            margin-bottom: 20px;

            border-radius: 6px;

            text-align: center;

            font-size: 16px;
        }

        .error {

            background: #f8d7da;

            color: #842029;
        }

        .success {

            background: #d1e7dd;

            color: #0f5132;
        }

        .login-link {

            text-align: center;

            margin-top: 25px;

            font-size: 17px;
        }

        .login-link a {

            color: #5b1aa8;

            text-decoration: none;

            font-weight: bold;
        }

        .login-link a:hover {

            text-decoration: underline;
        }

        @media (max-width: 650px) {

            .auth-container {

                width: 90%;

                padding: 30px 25px;
            }

            .auth-container h1 {

                font-size: 34px;
            }

            .auth-container h2 {

                font-size: 27px;
            }
        }

    </style>
</head>

<body>

<div class="auth-container">

    <h1>TMF</h1>
    <h2>Create Account</h2>

    <?php if (!empty($message)): ?>
        <p class="error">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Name</label>
        <input
            type="text"
            name="name"
            required
        >

        <label>Email</label>
        <input
            type="email"
            name="email"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            required
        >

        <label>Confirm Password</label>
        <input
            type="password"
            name="confirm_password"
            required
        >

        <button type="submit">
            Register
        </button>

    </form>

    <p>
        Already have an account?
        <a href="login.php">Login</a>
    </p>

</div>

</body>
</html>
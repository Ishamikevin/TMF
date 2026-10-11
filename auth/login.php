<?php

session_start();
require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ?"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];

        header("Location: ../dashboard/index.php");
        exit;

    } else {

        $message = "Invalid email or password.";
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

    <title>Login - TMF</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >
    <style>
        .auth-container {
    width: 100%;
    max-width: 520px;

    background: transparent;

    padding: 90px 45px;

    margin: auto;

    text-align: left;
}
    </style>

</head>

<body>

<div class="auth-container">

    <h1>TMF</h1>

    <h2>Login</h2>

    <?php if (!empty($message)): ?>

        <p class="error">
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>

    <form method="POST">

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

        <button type="submit">
            Login
        </button>

    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register</a>
    </p>

</div>

</body>
</html>
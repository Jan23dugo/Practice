<?php
    session_start(); // Start session if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Linking Google Fonts For Icons -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>

<div class="container">

<?php include 'sidebar.php'; ?>

<div class="main">
    <h1>Welcome to Dashboard</h1>
    <p>This is your dashboard page.</p>
</div>
</div>
<script src="assets/js/side.js"></script>
</body>
</html>

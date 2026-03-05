<?php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APPANE - Il Pane che ti Appaga</title>
    <link rel="stylesheet" href="/APPANE.V1/httpdocs/css/stile.css">
    <!-- DA USARE A SCUOLA!!  <link rel="stylesheet" href="../httpdocs/css/stile.css"> -->
</head>
<body>

<header class="main-header">
    <div class="header-content">
        <a href="index.php" class="logo">🍞 APPANE</a>
        <nav class="nav-links">
            <?php if (is_logged_in()): ?>
                <span class="welcome-msg">Ciao, <?= htmlspecialchars($_SESSION['user_nome']) ?>!</span>
                <a href="orders.php">I Miei Ordini</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Accedi / Registrati</a>
            <?php endif; ?>
            
            <a href="cart.php" class="cart-link">
                🛒 Carrello <span class="cart-badge">
                    <?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?>
                </span>
            </a>
        </nav>
    </div>
</header>
<main class="main-container">

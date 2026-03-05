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
                <?php
                $hasUnreadNotes = false;
                if (isset($db)) {
                    $stmtNotes = $db->prepare("SELECT COUNT(*) FROM tordine WHERE id_utente = ? AND nota_fornitore IS NOT NULL AND nota_fornitore != '' AND nota_letta = 0");
                    $stmtNotes->execute([$_SESSION['user_id']]);
                    if ($stmtNotes->fetchColumn() > 0) {
                        $hasUnreadNotes = true;
                    }
                }
                ?>
                <span class="welcome-msg">Ciao, <?= htmlspecialchars($_SESSION['user_nome']) ?>!</span>
                <a href="orders.php" style="position: relative;">
                    I Miei Ordini
                    <?php if ($hasUnreadNotes): ?>
                        <span style="position: absolute; top: -5px; right: -10px; width: 10px; height: 10px; background-color: var(--error-color, red); border-radius: 50%; box-shadow: 0 0 5px rgba(200,0,0,0.5);"></span>
                    <?php endif; ?>
                </a>
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

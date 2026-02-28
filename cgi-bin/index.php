<?php
require_once __DIR__ . '/include/config.php';

$stmt = $db->query("
    SELECT p.id_prodotto, p.nome_prodotto, p.prezzo, p.confezione, p.tipologia,
           GROUP_CONCAT(i.nome_ingrediente SEPARATOR ', ') as ingredienti
    FROM tprodotto p
    LEFT JOIN tricetta r ON p.id_prodotto = r.id_prodotto
    LEFT JOIN tingrediente i ON r.id_ingrediente = i.id_ingrediente
    GROUP BY p.id_prodotto
");
$prodotti = $stmt->fetchAll();

$isOrderActive = is_order_open();

require_once __DIR__ . '/include/header.php';
?>

<div class="text-center" style="margin-bottom: 2rem;">
    <h1 style="color: var(--primary-color);">Benvenuto da APPANE 🍞</h1>
    <p>Il pane speciale artigianale, le pizze e i croissant consegnati direttamente a casa tua.</p>
</div>

<?php if (!$isOrderActive): ?>
    <div class="banner-closed">
        ⏳ Gli ordini sono chiusi. <br>
        Accettiamo ordini dal Lunedì al Giovedì entro le 23:59 per le consegne del weekend.
    </div>
<?php endif; ?>

<div class="product-grid">
    <?php foreach ($prodotti as $p): ?>
        <div class="product-card">
            <?php 
                $emoji = "🍞";
                if ($p['tipologia'] === 'Pizza intera') $emoji = "🍕";
                if ($p['tipologia'] === 'Brioche') $emoji = "🥐";
            ?>
            <div class="product-type"><?= $emoji ?> <?= htmlspecialchars($p['tipologia']) ?></div>
            <h3 class="product-name"><?= htmlspecialchars($p['nome_prodotto']) ?></h3>
            <p class="product-ingredients">
                <small>Ingredienti principali: <?= htmlspecialchars($p['ingredienti'] ?? 'N/A') ?></small><br>
                <small>Confezione: <?= htmlspecialchars($p['confezione']) ?></small>
            </p>
            
            <div class="product-meta">
                <span class="product-price">€ <?= number_format($p['prezzo'], 2, ',', '.') ?></span>
                
                <?php if ($isOrderActive): ?>
                    <form action="cart_action.php" method="POST" class="controlli">
                        <input type="hidden" name="id_prodotto" value="<?= $p['id_prodotto'] ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="qty-controls">
                            <button type="button" class="qty-btn" onclick="this.nextElementSibling.stepDown()">-</button>
                            <input type="number" name="quantita" value="1" min="1" max="50" class="qty-input">
                            <button type="button" class="qty-btn" onclick="this.previousElementSibling.stepUp()">+</button>
                        </div>
                        
                        <button type="submit" class="btn btn-accent" style="margin-left:10px;">Aggiungi</button>
                    </form>
                <?php else: ?>
                    <button class="btn" disabled style="background-color: #ccc; cursor: not-allowed;">Non Ordinabile Ora</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (isset($_SESSION['cart']) && array_sum($_SESSION['cart']) > 0): ?>
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <a href="cart.php" class="btn btn-accent" style="box-shadow: 0 4px 10px rgba(0,0,0,0.5); font-size: 1.2rem; border-radius: 50px; padding: 15px 30px; display: flex; align-items: center; gap: 8px;">
            🛒 Procedi all'Ordine (<?= array_sum($_SESSION['cart']) ?>)
        </a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/include/footer.php'; ?>

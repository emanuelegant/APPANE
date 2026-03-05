<?php
require_once __DIR__ . '/include/config.php';

$cartItems = [];
$total = 0.0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $db->prepare("SELECT id_prodotto, nome_prodotto, prezzo FROM tprodotto WHERE id_prodotto IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    
    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id_prodotto']];
        $subtotal = $qty * $p['prezzo'];
        $total += $subtotal;
        
        $p['quantita'] = $qty;
        $p['subtotale'] = $subtotal;
        $cartItems[] = $p;
    }
}

require_once __DIR__ . '/include/header.php';
?>

<div class="form-container" style="max-width: 800px; margin-bottom: 3rem;">
    <h2 style="color: var(--primary-color);">🛒 Il tuo Carrello</h2>
    
    <?php if (empty($cartItems)): ?>
        <p class="mt-1">Il carrello è vuoto. <a href="index.php">Torna ai prodotti</a>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Prodotto</th>
                        <th class="text-center">Quantità</th>
                        <th style="text-align: right;">Prezzo</th>
                        <th style="text-align: right;">Subtotale</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome_prodotto']) ?></td>
                            <td class="text-center">
                                <form action="cart_action.php" method="POST" style="display:inline-flex; align-items:center; gap:5px;">
                                    <input type="hidden" name="id_prodotto" value="<?= $item['id_prodotto'] ?>">
                                    <input type="hidden" name="action" value="update">
                                    <button type="button" class="qty-btn" style="width:25px; height:25px;" onclick="this.nextElementSibling.stepDown(); this.form.submit()">-</button>
                                    <input type="number" name="quantita" value="<?= $item['quantita'] ?>" min="1" max="50" class="qty-input" style="width:40px; height:25px;" readonly>
                                    <button type="button" class="qty-btn" style="width:25px; height:25px;" onclick="this.previousElementSibling.stepUp(); this.form.submit()">+</button>
                                </form>
                            </td>
                            <td style="text-align: right;">€ <?= number_format($item['prezzo'], 2, ',', '.') ?></td>
                            <td style="text-align: right;"><strong>€ <?= number_format($item['subtotale'], 2, ',', '.') ?></strong></td>
                            <td class="text-center">
                                <form action="cart_action.php" method="POST">
                                    <input type="hidden" name="id_prodotto" value="<?= $item['id_prodotto'] ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Rimuovi</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="cart-total">
            Totale Ordine: € <?= number_format($total, 2, ',', '.') ?>
        </div>
        
        <div class="cart-actions mt-1 mb-2" style="justify-content: flex-end;">
            <?php if (!is_order_open()): ?>
                <button class="btn btn-accent" disabled style="opacity: 0.5; cursor:not-allowed;">Ordini chiusi. Torna lunedì!</button>
            <?php else: ?>
                <a href="checkout.php" class="btn btn-accent" style="font-size: 1.1rem; padding: 10px 20px;">Procedi al Checkout</a>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>

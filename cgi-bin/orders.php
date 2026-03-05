<?php
require_once __DIR__ . '/include/config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$isOrderActive = is_order_open();

// Gestione azioni ordine: cancel o modify
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_ordine = (int)($_POST['id_ordine'] ?? 0);
    
    if ($isOrderActive && $id_ordine > 0) {
        $stmtCheck = $db->prepare("SELECT stato FROM tordine WHERE id_ordine = ? AND id_utente = ?");
        $stmtCheck->execute([$id_ordine, $user_id]);
        $stato = $stmtCheck->fetchColumn();
        
        if ($stato === 'non_confermato' || $stato === 'confermato_in_preparazione') {
            if ($action === 'cancel') {
                $stmtCancel = $db->prepare("UPDATE tordine SET stato = 'annullato' WHERE id_ordine = ?");
                $stmtCancel->execute([$id_ordine]);
                $msgSuccess = "Ordine #$id_ordine annullato con successo.";
            } elseif ($action === 'modify') {
                // Annulla vecchio ordine
                $stmtCancel = $db->prepare("UPDATE tordine SET stato = 'annullato' WHERE id_ordine = ?");
                $stmtCancel->execute([$id_ordine]);
                
                // Pulisci carrello DB
                $stmtEmptyCart = $db->prepare("DELETE FROM tistanza_prodotto WHERE id_carrello = (SELECT id_carrello FROM tcarrello WHERE id_utente = ?)");
                $stmtEmptyCart->execute([$user_id]);
                
                // Ricarica carrello in sessione
                $_SESSION['cart'] = [];
                $stmtDettagli = $db->prepare("SELECT id_prodotto, quantita FROM tdettaglio_ordine WHERE id_ordine = ?");
                $stmtDettagli->execute([$id_ordine]);
                while($d = $stmtDettagli->fetch()) {
                    $_SESSION['cart'][$d['id_prodotto']] = (int)$d['quantita'];
                }
                sync_cart_to_db($db, $user_id);
                
                header("Location: cart.php");
                exit;
            }
        }
    }
}

// Recupero storico ordini
$stmt = $db->prepare("
    SELECT o.id_ordine, o.data_ordine, o.stato, o.totale, o.nota_fornitore
    FROM tordine o
    WHERE o.id_utente = ?
    ORDER BY o.data_ordine DESC
");
$stmt->execute([$user_id]);
$ordini = $stmt->fetchAll();

$stmtDettagli = $db->prepare("
    SELECT d.id_prodotto, p.nome_prodotto, d.quantita, d.prezzo_unitario_storico
    FROM tdettaglio_ordine d
    JOIN tprodotto p ON d.id_prodotto = p.id_prodotto
    WHERE d.id_ordine = ?
");

require_once __DIR__ . '/include/header.php';
?>

<div class="main-container" style="max-width: 900px;">
    <h2 style="color: var(--primary-color);">📦 I Miei Ordini</h2>
    
    <?php if (isset($msgSuccess)): ?>
        <div class="alert alert-success mt-1"><?= htmlspecialchars($msgSuccess) ?></div>
    <?php endif; ?>

    <?php if (empty($ordini)): ?>
        <p class="mt-1">Non hai ancora effettuato ordini.</p>
    <?php else: ?>
        <?php foreach ($ordini as $o): ?>
            <?php
            $stmtDettagli->execute([$o['id_ordine']]);
            $dettagli = $stmtDettagli->fetchAll();
            $dataFormatted = date('d/m/Y H:i', strtotime($o['data_ordine']));
            
            $statoBadgeColor = '#ccc';
            if ($o['stato'] === 'non_confermato') $statoBadgeColor = '#f39c12'; // Arancione
            if ($o['stato'] === 'confermato_in_preparazione') $statoBadgeColor = '#3498db'; // Blu
            if ($o['stato'] === 'in_consegna') $statoBadgeColor = '#9b59b6';
            if ($o['stato'] === 'consegnato') $statoBadgeColor = '#2ecc71';
            if ($o['stato'] === 'annullato') $statoBadgeColor = '#e74c3c';
            ?>
            
            <div style="border: 1px solid #ddd; padding: 15px; margin-top: 20px; border-radius: 8px; background: white;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                    <div>
                        <strong>Ordine #<?= $o['id_ordine'] ?></strong> <br>
                        <small>Effettuato il <?= $dataFormatted ?></small>
                    </div>
                    <div style="text-align: right;">
                        <span style="display:inline-block; padding: 4px 10px; border-radius: 20px; color: white; font-size: 0.85rem; font-weight: bold; background-color: <?= $statoBadgeColor ?>;">
                            <?= strtoupper($o['stato']) ?>
                        </span>
                        <div style="font-size: 1.2rem; font-weight: bold; margin-top: 5px;">Totale: € <?= number_format($o['totale'], 2, ',', '.') ?></div>
                    </div>
                </div>
                
                <table class="cart-table" style="box-shadow: none; margin-top: 0;">
                    <tbody>
                        <?php foreach ($dettagli as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['nome_prodotto']) ?></td>
                                <td class="text-center"><?= $d['quantita'] ?> pz</td>
                                <td style="text-align: right;">€ <?= number_format($d['prezzo_unitario_storico'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($o['nota_fornitore'])): ?>
                    <div class="mt-1" style="background-color: #fdfaf6; border-left: 4px solid var(--accent-color); padding: 12px; border-radius: 4px;">
                        <strong style="color: var(--primary-color);">📝 Nota dal Fornitore:</strong>
                        <p style="margin-top: 5px; font-size: 0.95rem;"><?= nl2br(htmlspecialchars($o['nota_fornitore'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($isOrderActive && ($o['stato'] === 'non_confermato' || $o['stato'] === 'confermato_in_preparazione')): ?>
                    <div class="mt-1" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <form method="POST" action="orders.php">
                            <input type="hidden" name="action" value="modify">
                            <input type="hidden" name="id_ordine" value="<?= $o['id_ordine'] ?>">
                            <button type="submit" class="btn btn-accent">Modifica</button>
                        </form>
                        <form method="POST" action="orders.php" onsubmit="return confirm('Sei sicuro di voler annullare questo ordine? L\'azione è irreversibile.');">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="id_ordine" value="<?= $o['id_ordine'] ?>">
                            <button type="submit" class="btn btn-danger">Annulla Ordine</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php 
// Imposta le note come lette per questo utente
$stmtUpdateNotes = $db->prepare("UPDATE tordine SET nota_letta = 1 WHERE id_utente = ? AND nota_fornitore IS NOT NULL AND nota_fornitore != '' AND nota_letta = 0");
$stmtUpdateNotes->execute([$user_id]);

require_once __DIR__ . '/include/footer.php'; 
?>

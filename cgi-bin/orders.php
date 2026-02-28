<?php
require_once __DIR__ . '/include/config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$isOrderActive = is_order_open();

// Gestione annullamento ordine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $id_ordine = (int)($_POST['id_ordine'] ?? 0);
    
    if ($isOrderActive && $id_ordine > 0) {
        $stmtCheck = $db->prepare("SELECT stato FROM tordine WHERE id_ordine = ? AND id_utente = ?");
        $stmtCheck->execute([$id_ordine, $user_id]);
        $stato = $stmtCheck->fetchColumn();
        
        if ($stato === 'ricevuto') {
            $stmtCancel = $db->prepare("UPDATE tordine SET stato = 'annullato' WHERE id_ordine = ?");
            $stmtCancel->execute([$id_ordine]);
            $msgSuccess = "Ordine #$id_ordine annullato con successo.";
        }
    }
}

// Recupero storico ordini
$stmt = $db->prepare("
    SELECT o.id_ordine, o.data_ordine, o.stato, o.totale
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
            if ($o['stato'] === 'ricevuto') $statoBadgeColor = '#3498db';
            if ($o['stato'] === 'in_preparazione') $statoBadgeColor = '#f39c12';
            if ($o['stato'] === 'in_consegna') $statoBadgeColor = '#9b59b6';
            if ($o['stato'] === 'consegnato') $statoBadgeColor = '#2ecc71';
            if ($o['stato'] === 'annullato') $statoBadgeColor = '#e74c3c';
            ?>
            
            <div style="border: 1px solid #ddd; padding: 15px; margin-top: 20px; border-radius: 8px; background: white;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
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
                
                <?php if ($isOrderActive && $o['stato'] === 'ricevuto'): ?>
                    <div class="mt-1" style="text-align: right;">
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

<?php require_once __DIR__ . '/include/footer.php'; ?>

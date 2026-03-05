<?php
require_once __DIR__ . '/include/config.php';

$id_ordine = $_GET['id_ordine'] ?? null;
$guest_data = null;

if ($id_ordine) {
    $stmt = $db->prepare("SELECT nome_guest, cognome_guest, email_guest, telefono_guest, via_civico_guest, cap_guest FROM tordine WHERE id_ordine = ? AND id_utente IS NULL");
    $stmt->execute([$id_ordine]);
    $guest_data = $stmt->fetch();
}

require_once __DIR__ . '/include/header.php';
?>

<div class="form-container text-center mt-2 mb-2">
    <h2 style="color: var(--success-color);">🏁 Ordine Ricevuto con Successo!</h2>
    <p class="mt-1 mb-1">
        Grazie per aver scelto <strong style="color: var(--primary-color);">APPANE</strong> e i nostri impasti freschi.<br>
        Il tuo ordine <strong>#<?= htmlspecialchars($id_ordine) ?></strong> è stato registrato ed è ora nello stato "Ricevuto". 
    </p>

    <div style="background-color: var(--bg-color); padding: 15px; border-radius: 8px; margin-top: 15px; text-align: left; border: 2px solid var(--secondary-color);">
        <p><strong>Dettagli Consegna & Pagamento:</strong></p>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Ti ricordiamo che il pagamento avviene <strong>esclusivamente alla consegna in qualsiasi forma</strong>.</li>
            <li>Consegniamo solo nella provincia di Trieste (CAP 34***).</li>
            <li>Le consegne avvengono comodamente nel weekend direttamente a casa tua.</li>
        </ul>
    </div>

    <div class="mt-2" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; flex-direction: column; align-items: center;">
        
        <?php if ($guest_data): ?>
            <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 15px; width: 100%;">
                <h4 style="color: #856404; margin-bottom: 10px;">Vuoi fare prima al prossimo ordine?</h4>
                <p style="font-size: 0.9rem; margin-bottom: 10px;">Crea subito un account usando i dati appena inseriti!</p>
                <?php 
                    $params = http_build_query([
                        'id_ordine' => $id_ordine,
                        'nome' => $guest_data['nome_guest'],
                        'cognome' => $guest_data['cognome_guest'],
                        'email' => $guest_data['email_guest'],
                        'telefono' => $guest_data['telefono_guest'],
                        'via_civico' => $guest_data['via_civico_guest'],
                        'cap' => $guest_data['cap_guest']
                    ]);
                ?>
                <a href="register.php?<?= $params ?>" class="btn" style="background-color: #28a745; width: 100%;">Crea Account Ora</a>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <?php if (is_logged_in()): ?>
                <a href="orders.php" class="btn btn-accent">Visualizza I Miei Ordini</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>

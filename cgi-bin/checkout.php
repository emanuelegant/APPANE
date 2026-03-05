<?php
require_once __DIR__ . '/include/config.php';

if (!is_order_open() || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

class FormCheckout {
    public ?string $nome = null;
    public ?string $nome_err = null;
    
    public ?string $cognome = null;
    public ?string $cognome_err = null;
    
    public ?string $email = null;
    public ?string $email_err = null;
    
    public ?string $telefono = null;
    public ?string $telefono_err = null;
    
    public ?string $via_civico = null;
    public ?string $via_civico_err = null;
    
    public ?string $cap = null;
    public ?string $cap_err = null;
    
    public ?string $gen_err = null;
}

if (isset($_SESSION['checkout_form_state'])) {
    $form_input = unserialize($_SESSION['checkout_form_state']);
    unset($_SESSION['checkout_form_state']);
} else {
    $form_input = new FormCheckout();
}

require_once __DIR__ . '/include/header.php';
?>

<div class="form-container" style="max-width: 800px; margin-bottom: 3rem;">
    <h2 style="color: var(--primary-color);">Conferma il tuo Ordine</h2>
    
    <?php if (is_logged_in()): ?>
        <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: var(--box-shadow);">
            <h3>Procedi con l'ordine</h3>
            <p>I tuoi dati di spedizione sono già salvati.</p>
            <form action="checkout_process.php" method="POST" class="mt-1">
                <button type="submit" class="btn btn-accent" style="width: 100%; font-size: 1.1rem; padding: 12px;">Conferma e Invia Ordine</button>
            </form>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: var(--box-shadow);">
            <h3 style="color: var(--primary-color);">Checkout Ospite</h3>
            <p style="margin-bottom: 15px;">Inserisci i tuoi dati per la consegna o <a href="login.php?redirect=checkout" style="color: var(--primary-color); font-weight: bold; text-decoration: underline;">Accedi</a> se hai un account.</p>

            <?php if (!is_null($form_input->gen_err)): ?>
                <div class="alert alert-error" style="margin-bottom: 15px;"><?= htmlspecialchars((string)$form_input->gen_err) ?></div>
            <?php endif; ?>

            <form action="checkout_process.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <input type="hidden" name="is_guest" value="1">
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Nome</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars((string)$form_input->nome) ?>" maxlength="100" class="<?= is_null($form_input->nome_err) ? '' : 'campo-errore' ?>">
                    <div style="margin-top: 4px; <?= is_null($form_input->nome_err) ? 'display:none' : '' ?>">
                        <span class="errore"><?= htmlspecialchars((string)$form_input->nome_err) ?></span>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Cognome</label>
                    <input type="text" name="cognome" value="<?= htmlspecialchars((string)$form_input->cognome) ?>" maxlength="100" class="<?= is_null($form_input->cognome_err) ? '' : 'campo-errore' ?>">
                    <div style="margin-top: 4px; <?= is_null($form_input->cognome_err) ? 'display:none' : '' ?>">
                        <span class="errore"><?= htmlspecialchars((string)$form_input->cognome_err) ?></span>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars((string)$form_input->email) ?>" maxlength="320" class="<?= is_null($form_input->email_err) ? '' : 'campo-errore' ?>">
                    <div style="margin-top: 4px; <?= is_null($form_input->email_err) ? 'display:none' : '' ?>">
                        <span class="errore"><?= htmlspecialchars((string)$form_input->email_err) ?></span>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Telefono</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars((string)$form_input->telefono) ?>" maxlength="20" class="<?= is_null($form_input->telefono_err) ? '' : 'campo-errore' ?>">
                    <div style="margin-top: 4px; <?= is_null($form_input->telefono_err) ? 'display:none' : '' ?>">
                        <span class="errore"><?= htmlspecialchars((string)$form_input->telefono_err) ?></span>
                    </div>
                </div>
                
                <div class="form-group" style="grid-column: span 2; margin-bottom: 0;">
                    <label>Via e Civico</label>
                    <input type="text" name="via_civico" value="<?= htmlspecialchars((string)$form_input->via_civico) ?>" maxlength="255" class="<?= is_null($form_input->via_civico_err) ? '' : 'campo-errore' ?>">
                    <div style="margin-top: 4px; <?= is_null($form_input->via_civico_err) ? 'display:none' : '' ?>">
                        <span class="errore"><?= htmlspecialchars((string)$form_input->via_civico_err) ?></span>
                    </div>
                </div>
                
                <div class="form-group" style="grid-column: span 2; margin-bottom: 0;">
                    <label>CAP (Provincia di Trieste)</label>
                    <input type="text" name="cap" placeholder="Es. 34100" value="<?= htmlspecialchars((string)$form_input->cap) ?>" maxlength="5" class="<?= is_null($form_input->cap_err) ? '' : 'campo-errore' ?>">
                    <div style="margin-top: 4px; <?= is_null($form_input->cap_err) ? 'display:none' : '' ?>">
                        <span class="errore"><?= htmlspecialchars((string)$form_input->cap_err) ?></span>
                    </div>
                </div>

                <div style="grid-column: span 2; display:flex; justify-content: flex-end; margin-top: 15px;">
                   <button type="submit" class="btn btn-accent" style="width: 100%; font-size: 1.1rem; padding: 12px;">Conferma Ordine come Ospite</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>

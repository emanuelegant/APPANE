<?php
require_once __DIR__ . '/include/config.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

class FormRegistrazione {
    public ?string $nome = null;
    public ?string $nome_err = null;
    
    public ?string $cognome = null;
    public ?string $cognome_err = null;
    
    public ?string $email = null;
    public ?string $email_err = null;
    
    public ?string $password = null;
    public ?string $password_err = null;
    
    public ?string $telefono = null;
    public ?string $telefono_err = null;
    
    public ?string $via_civico = null;
    public ?string $via_civico_err = null;
    
    public ?string $cap = null;
    public ?string $cap_err = null;
    
    public ?string $gen_err = null;
}

$form_input = new FormRegistrazione();
$successMessage = '';

if (isset($_GET['nome'])) $form_input->nome = $_GET['nome'];
if (isset($_GET['cognome'])) $form_input->cognome = $_GET['cognome'];
if (isset($_GET['email'])) $form_input->email = $_GET['email'];
if (isset($_GET['telefono'])) $form_input->telefono = $_GET['telefono'];
if (isset($_GET['via_civico'])) $form_input->via_civico = $_GET['via_civico'];
if (isset($_GET['cap'])) $form_input->cap = $_GET['cap'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schema = [
        'nome'       => 'string(2,100)',
        'cognome'    => 'string(2,100)',
        'email'      => 'email',
        'password'   => 'string(6,100)',
        'telefono'   => 'string(5,20)',
        'via_civico' => 'string(5,255)',
        'cap'        => 'numeric(5)'
    ];

    $validation_result = validate_post($schema);
    
    foreach ($validation_result->missing_required_params as $missing) {
        $err_prop = $missing . '_err';
        $form_input->$err_prop = "Questo campo è obbligatorio.";
    }

    foreach ($validation_result->errors as $field => $errObj) {
        $err_prop = $field . '_err';
        if ($errObj instanceof StringErrors) {
            if ($errObj->less_than_minlen) $form_input->$err_prop = "Il testo è troppo corto.";
            if ($errObj->greater_than_maxlen) $form_input->$err_prop = "Il testo è troppo lungo.";
            if ($errObj->differs_from_tgtlen) $form_input->$err_prop = "Lunghezza non valida.";
        } elseif ($errObj instanceof NumericErrors) {
            if ($errObj->not_numeric) $form_input->$err_prop = "Deve contenere solo numeri.";
            if ($errObj->less_than_minlen || $errObj->greater_than_maxlen || $errObj->differs_from_tgtlen) {
                $form_input->$err_prop = "Lunghezza numerica non valida.";
            }
        } elseif ($errObj instanceof EmailErrors) {
            if ($errObj->is_empty) $form_input->$err_prop = "L'email non può essere vuota.";
            if ($errObj->missing_at_symbol) $form_input->$err_prop = "Manca la chiocciola (@) nell'email.";
            if ($errObj->is_too_long) $form_input->$err_prop = "L'email è troppo lunga.";
        }
    }

    foreach (['nome', 'cognome', 'email', 'telefono', 'via_civico', 'cap'] as $f) {
        if (isset($validation_result->sanitized_params[$f])) {
            $form_input->$f = $validation_result->sanitized_params[$f];
        } elseif (isset($_POST[$f])) {
            $form_input->$f = htmlspecialchars((string)$_POST[$f]);
        }
    }

    if (is_null($form_input->cap_err) && isset($form_input->cap)) {
        if (!str_starts_with($form_input->cap, '34')) {
            $form_input->cap_err = "Consegniamo solo nella provincia di Trieste (CAP 34xxx).";
        }
    }

    if (is_null($form_input->telefono_err) && isset($form_input->telefono)) {
        if (!preg_match('/^\+?[0-9\s]+$/', $form_input->telefono)) {
            $form_input->telefono_err = "Il numero di telefono può contenere solo numeri e il prefisso (+).";
        }
    }

    $has_errors = !is_null($form_input->nome_err) || !is_null($form_input->cognome_err) || 
                  !is_null($form_input->email_err) || !is_null($form_input->password_err) || 
                  !is_null($form_input->telefono_err) || !is_null($form_input->via_civico_err) || 
                  !is_null($form_input->cap_err);

    if (!$has_errors) {
        $s = $validation_result->sanitized_params;

        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM tutente WHERE email = ?");
        $stmtCheck->execute([$s['email']]);
        if ($stmtCheck->fetchColumn() > 0) {
            $form_input->email_err = "Questa email è già registrata nell'applicazione.";
        } else {
            $hashedPassword = password_hash($s['password'], PASSWORD_BCRYPT);
            $stmtInsert = $db->prepare("
                INSERT INTO tutente (nome, cognome, email, password, telefono, via_civico, cap)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            try {
                $db->beginTransaction();
                $stmtInsert->execute([$s['nome'], $s['cognome'], $s['email'], $hashedPassword, $s['telefono'], $s['via_civico'], $s['cap']]);
                
                $new_user_id = $db->lastInsertId();
                $id_ordine_claim = $_POST['id_ordine_claim'] ?? null;
                
                if ($id_ordine_claim) {
                    $stmtUpdateOrdine = $db->prepare("
                        UPDATE tordine 
                        SET id_utente = ?, 
                            nome_guest = NULL, cognome_guest = NULL, email_guest = NULL, 
                            telefono_guest = NULL, via_civico_guest = NULL, cap_guest = NULL
                        WHERE id_ordine = ? AND id_utente IS NULL
                    ");
                    $stmtUpdateOrdine->execute([$new_user_id, $id_ordine_claim]);
                }
                
                $db->commit();
                $successMessage = "Registrazione completata con successo! Ora puoi accedere.";
                $form_input = new FormRegistrazione();
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $form_input->gen_err = "Errore durante la registrazione. Riprova più tardi.";
            }
        }
    }
}

require_once __DIR__ . '/include/header.php';
?>

<div class="form-container mt-2 mb-2">
    <h2 class="text-center" style="color: var(--primary-color);">Crea un Account</h2>
    
    <?php if ($successMessage): ?>
        <div class="alert alert-success mt-1 text-center">
            <?= htmlspecialchars($successMessage) ?><br>
            <a href="login.php" class="btn btn-accent mt-1">Vai al Login</a>
        </div>
    <?php else: ?>
        <?php if (!is_null($form_input->gen_err)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($form_input->gen_err) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="mt-1">
            <?php if (isset($_GET['id_ordine']) || isset($_POST['id_ordine_claim'])): ?>
                <input type="hidden" name="id_ordine_claim" value="<?= htmlspecialchars($_GET['id_ordine'] ?? $_POST['id_ordine_claim'] ?? '') ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" name="nome" id="nome" value="<?= htmlspecialchars((string)$form_input->nome) ?>" maxlength="100" class="<?= is_null($form_input->nome_err) ? '' : 'campo-errore' ?>">
                <div style="margin-top: 4px; <?= is_null($form_input->nome_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->nome_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="cognome">Cognome</label>
                <input type="text" name="cognome" id="cognome" value="<?= htmlspecialchars((string)$form_input->cognome) ?>" maxlength="100" class="<?= is_null($form_input->cognome_err) ? '' : 'campo-errore' ?>">
                <div style="margin-top: 4px; <?= is_null($form_input->cognome_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->cognome_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars((string)$form_input->email) ?>" maxlength="320" class="<?= is_null($form_input->email_err) ? '' : 'campo-errore' ?>">
                <div style="margin-top: 4px; <?= is_null($form_input->email_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->email_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" maxlength="100" class="<?= is_null($form_input->password_err) ? '' : 'campo-errore' ?>">
                <div style="margin-top: 4px; <?= is_null($form_input->password_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->password_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="telefono">Telefono</label>
                <input type="text" name="telefono" id="telefono" value="<?= htmlspecialchars((string)$form_input->telefono) ?>" maxlength="20" class="<?= is_null($form_input->telefono_err) ? '' : 'campo-errore' ?>">
                <div style="margin-top: 4px; <?= is_null($form_input->telefono_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->telefono_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="via_civico">Via e Civico</label>
                <input type="text" name="via_civico" id="via_civico" value="<?= htmlspecialchars((string)$form_input->via_civico) ?>" maxlength="255" class="<?= is_null($form_input->via_civico_err) ? '' : 'campo-errore' ?>">
                <div style="margin-top: 4px; <?= is_null($form_input->via_civico_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->via_civico_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="cap">CAP (Consegne solo 34***)</label>
                <input type="text" name="cap" id="cap" placeholder="Es. 34100" value="<?= htmlspecialchars((string)$form_input->cap) ?>" maxlength="5" class="<?= is_null($form_input->cap_err) ? '' : 'campo-errore' ?>">
                <div style="margin-top: 4px; <?= is_null($form_input->cap_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->cap_err) ?></span>
                </div>
            </div>

            <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 10px;">Registrati</button>
        </form>
        
        <div class="text-center mt-1" style="font-size: 0.9rem;">
            Hai già un account? <a href="login.php" style="color: var(--primary-color); font-weight: bold;">Accedi</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>

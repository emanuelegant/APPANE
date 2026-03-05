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

    // Salviamo subito quello che ha scritto l'utente (sanificato se valido)
    $form_input->nome       = $validation_result->sanitized_params['nome'] ?? htmlspecialchars((string)($_POST['nome'] ?? ''));
    $form_input->cognome    = $validation_result->sanitized_params['cognome'] ?? htmlspecialchars((string)($_POST['cognome'] ?? ''));
    $form_input->email      = $validation_result->sanitized_params['email'] ?? htmlspecialchars((string)($_POST['email'] ?? ''));
    $form_input->telefono   = $validation_result->sanitized_params['telefono'] ?? htmlspecialchars((string)($_POST['telefono'] ?? ''));
    $form_input->via_civico = $validation_result->sanitized_params['via_civico'] ?? htmlspecialchars((string)($_POST['via_civico'] ?? ''));
    $form_input->cap        = $validation_result->sanitized_params['cap'] ?? htmlspecialchars((string)($_POST['cap'] ?? ''));

    // GESTIONE ERRORI - Stile esatto PCTO ad IF / ELSE IF a cascata

    // NOME
    if (in_array('nome', $validation_result->missing_required_params) || trim($_POST['nome'] ?? '') === '') {
        $form_input->nome_err = "Questo campo è obbligatorio.";
    } elseif (isset($validation_result->errors['nome'])) {
        $nome_err = $validation_result->errors['nome'];
        if ($nome_err->less_than_minlen) {
            $form_input->nome_err = "Il nome deve avere almeno 2 caratteri.";
        } elseif ($nome_err->greater_than_maxlen) {
            $form_input->nome_err = "Il nome supera i 100 caratteri ammessi.";
        }
    }

    // COGNOME
    if (in_array('cognome', $validation_result->missing_required_params) || trim($_POST['cognome'] ?? '') === '') {
        $form_input->cognome_err = "Questo campo è obbligatorio.";
    } elseif (isset($validation_result->errors['cognome'])) {
        $cognome_err = $validation_result->errors['cognome'];
        if ($cognome_err->less_than_minlen) {
            $form_input->cognome_err = "Il cognome deve avere almeno 2 caratteri.";
        } elseif ($cognome_err->greater_than_maxlen) {
            $form_input->cognome_err = "Il cognome supera i 100 caratteri ammessi.";
        }
    }

    // EMAIL
    if (in_array('email', $validation_result->missing_required_params) || trim($_POST['email'] ?? '') === '') {
        $form_input->email_err = "Questo campo è obbligatorio.";
    } elseif (isset($validation_result->errors['email'])) {
        $email_err = $validation_result->errors['email'];
        if ($email_err->is_empty) {
            $form_input->email_err = "Questo campo è obbligatorio.";
        } elseif ($email_err->missing_at_symbol) {
             $form_input->email_err = "L'email deve contenere la chiocciola (@).";
        } elseif ($email_err->is_too_long) {
            $form_input->email_err = "L'email inserita è troppo lunga.";
        }
    }

    // PASSWORD
    if (in_array('password', $validation_result->missing_required_params) || ($_POST['password'] ?? '') === '') {
        $form_input->password_err = "Questo campo è obbligatorio.";
    } elseif (isset($validation_result->errors['password'])) {
        $password_err = $validation_result->errors['password'];
        if ($password_err->less_than_minlen) {
            $form_input->password_err = "La password deve avere almeno 6 caratteri.";
        } elseif ($password_err->greater_than_maxlen) {
            $form_input->password_err = "La password inserita è troppo lunga (max 100).";
        }
    }

    // TELEFONO
    if (in_array('telefono', $validation_result->missing_required_params) || trim($_POST['telefono'] ?? '') === '') {
        $form_input->telefono_err = "Questo campo è obbligatorio.";
    } elseif (isset($validation_result->errors['telefono'])) {
        $telefono_err = $validation_result->errors['telefono'];
        if ($telefono_err->less_than_minlen) {
            $form_input->telefono_err = "Il numero di telefono è troppo corto.";
        } elseif ($telefono_err->greater_than_maxlen) {
            $form_input->telefono_err = "Il numero di telefono inserito è troppo lungo.";
        }
    } elseif (isset($validation_result->sanitized_params['telefono'])) {
        if (!preg_match('/^\+?[0-9\s]+$/', $validation_result->sanitized_params['telefono'])) {
            $form_input->telefono_err = "Il telefono può contenere solo numeri e il prefisso (+).";
        }
    }

    // VIA CIVICO
    if (in_array('via_civico', $validation_result->missing_required_params) || trim($_POST['via_civico'] ?? '') === '') {
        $form_input->via_civico_err = "Questo campo è obbligatorio.";
    } elseif (isset($validation_result->errors['via_civico'])) {
        $via_civico_err = $validation_result->errors['via_civico'];
        if ($via_civico_err->less_than_minlen) {
            $form_input->via_civico_err = "L'indirizzo inserito è troppo corto (min. 5 caratteri).";
        } elseif ($via_civico_err->greater_than_maxlen) {
            $form_input->via_civico_err = "L'indirizzo inserito è troppo lungo.";
        }
    }

    // CAP
    if (in_array('cap', $validation_result->missing_required_params) || trim($_POST['cap'] ?? '') === '') {
        $form_input->cap_err = "Questo campo è obbligatorio.";
    } elseif (isset($validation_result->errors['cap'])) {
        $cap_err = $validation_result->errors['cap'];
        if ($cap_err->not_numeric) {
            $form_input->cap_err = "Il CAP inserito deve contenere esclusivamente numeri.";
        } elseif ($cap_err->less_than_minlen) {
            $form_input->cap_err = "Il CAP deve essere di esattamente 5 cifre.";
        } elseif ($cap_err->greater_than_maxlen) {
            $form_input->cap_err = "Il CAP deve essere di esattamente 5 cifre.";
        } elseif ($cap_err->differs_from_tgtlen) {
            $form_input->cap_err = "Il CAP deve essere di esattamente 5 cifre.";
        }
    }


    $has_errors = !is_null($form_input->nome_err) || !is_null($form_input->cognome_err) || 
                  !is_null($form_input->email_err) || !is_null($form_input->password_err) || 
                  !is_null($form_input->telefono_err) || !is_null($form_input->via_civico_err) || 
                  !is_null($form_input->cap_err) || !is_null($form_input->gen_err);

    if (!$has_errors) {
        $s = $validation_result->sanitized_params;

        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM tutente WHERE email = ?");
        $stmtCheck->execute([$s['email']]);
        if ($stmtCheck->fetchColumn() > 0) {
            $form_input->email_err = "Questa email è già registrata.";
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
                $form_input->gen_err = "Errore database. Riprova più tardi.";
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
            <div class="alert alert-error text-center mb-1">
                <strong>⚠️</strong> <?= htmlspecialchars((string)$form_input->gen_err) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="mt-1">
            <?php if (isset($_GET['id_ordine']) || isset($_POST['id_ordine_claim'])): ?>
                <input type="hidden" name="id_ordine_claim" value="<?= htmlspecialchars($_GET['id_ordine'] ?? $_POST['id_ordine_claim'] ?? '') ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" name="nome" id="nome" value="<?= htmlspecialchars((string)$form_input->nome) ?>" maxlength="100" class="<?= is_null($form_input->nome_err) ? '' : 'campo-errore' ?>">
                <div style="<?= is_null($form_input->nome_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->nome_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="cognome">Cognome</label>
                <input type="text" name="cognome" id="cognome" value="<?= htmlspecialchars((string)$form_input->cognome) ?>" maxlength="100" class="<?= is_null($form_input->cognome_err) ? '' : 'campo-errore' ?>">
                <div style="<?= is_null($form_input->cognome_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->cognome_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars((string)$form_input->email) ?>" maxlength="320" class="<?= is_null($form_input->email_err) ? '' : 'campo-errore' ?>">
                <div style="<?= is_null($form_input->email_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->email_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" maxlength="100" class="<?= is_null($form_input->password_err) ? '' : 'campo-errore' ?>">
                <div style="<?= is_null($form_input->password_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->password_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="telefono">Telefono</label>
                <input type="text" name="telefono" id="telefono" value="<?= htmlspecialchars((string)$form_input->telefono) ?>" maxlength="20" class="<?= is_null($form_input->telefono_err) ? '' : 'campo-errore' ?>">
                <div style="<?= is_null($form_input->telefono_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->telefono_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="via_civico">Via e Civico</label>
                <input type="text" name="via_civico" id="via_civico" value="<?= htmlspecialchars((string)$form_input->via_civico) ?>" maxlength="255" class="<?= is_null($form_input->via_civico_err) ? '' : 'campo-errore' ?>">
                <div style="<?= is_null($form_input->via_civico_err) ? 'display:none' : '' ?>">
                    <span class="errore"><?= htmlspecialchars((string)$form_input->via_civico_err) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="cap">CAP (Consegne assicurate: 34***)</label>
                <input type="text" name="cap" id="cap" placeholder="Es. 34100" value="<?= htmlspecialchars((string)$form_input->cap) ?>" maxlength="5" class="<?= is_null($form_input->cap_err) ? '' : 'campo-errore' ?>">
                <div style="<?= is_null($form_input->cap_err) ? 'display:none' : '' ?>">
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

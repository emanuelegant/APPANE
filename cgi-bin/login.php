<?php
require_once __DIR__ . '/include/config.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

class FormLogin {
    public ?string $email = null;
    public ?string $email_err = null;
    
    public ?string $password = null;
    public ?string $password_err = null;
    
    public ?string $gen_err = null;
}

$form_input = new FormLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $schema = [
        'email'    => 'email',
        'password' => 'string(1,100)'
    ];

    $val_result = validate_post($schema);

    // Mantenimento input (mail)
    $form_input->email = $val_result->sanitized_params['email'] ?? htmlspecialchars((string)($_POST['email'] ?? ''));

    // GESTIONE ERRORI
    
    // EMAIL
    if (in_array('email', $val_result->missing_required_params) || trim($_POST['email'] ?? '') === '') {
        $form_input->email_err = "L'email è obbligatoria.";
    } elseif (isset($val_result->errors['email'])) {
        $email_err = $val_result->errors['email'];
        if ($email_err->is_empty) {
            $form_input->email_err = "L'email è obbligatoria.";
        } elseif ($email_err->missing_at_symbol) {
             $form_input->email_err = "L'email deve contenere la chiocciola (@).";
        } elseif ($email_err->is_too_long) {
            $form_input->email_err = "L'email inserita è troppo lunga.";
        }
    }

    // PASSWORD
    if (in_array('password', $val_result->missing_required_params) || ($_POST['password'] ?? '') === '') {
        $form_input->password_err = "La password è obbligatoria.";
    } elseif (isset($val_result->errors['password'])) {
        $password_err = $val_result->errors['password'];
        if ($password_err->less_than_minlen) {
            $form_input->password_err = "Password analizzata vuota.";
        } elseif ($password_err->greater_than_maxlen) {
            $form_input->password_err = "La password inserita è troppo lunga (max 100).";
        }
    }


    $has_errors = !is_null($form_input->email_err) || !is_null($form_input->password_err);

    // La password in chiaro dal POST
    $raw_password = (string)($_POST['password'] ?? ''); 

    if (!$has_errors) {
        $stmt = $db->prepare("SELECT id_utente, nome, password, ruolo FROM tutente WHERE email = ?");
        $stmt->execute([$form_input->email]);
        $user = $stmt->fetch();

        if ($user && password_verify($raw_password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_utente'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_ruolo'] = $user['ruolo'];
            
            sync_cart_to_db($db, $user['id_utente']);
            
            $redirect = $_GET['redirect'] ?? 'index.php';
            header("Location: " . ($redirect === 'cart' ? 'cart.php' : 'index.php'));
            exit;
        } else {
            $form_input->gen_err = "Credenziali non valide o utente inesistente.";
        }
    }
}

require_once __DIR__ . '/include/header.php';
?>

<div class="form-container mt-2">
    <h2 class="text-center" style="color: var(--primary-color);">Accedi ad APPANE</h2>
    
    <?php if (!is_null($form_input->gen_err)): ?>
        <div class="alert alert-error text-center mb-1">
            <strong>⚠️</strong> <?= htmlspecialchars((string)$form_input->gen_err) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode(htmlspecialchars($_GET['redirect'])) : '' ?>" class="mt-1">
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="La tua email Registrata" 
                   value="<?= htmlspecialchars((string)$form_input->email) ?>" maxlength="320" 
                   class="<?= is_null($form_input->email_err) ? '' : 'campo-errore' ?>">
            
            <div style="<?= is_null($form_input->email_err) ? 'display:none' : '' ?>">
                <span class="errore"><?= htmlspecialchars((string)$form_input->email_err) ?></span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="La tua password" 
                   maxlength="100" class="<?= is_null($form_input->password_err) ? '' : 'campo-errore' ?>">
            
            <div style="<?= is_null($form_input->password_err) ? 'display:none' : '' ?>">
                <span class="errore"><?= htmlspecialchars((string)$form_input->password_err) ?></span>
            </div>
        </div>
        
        <button type="submit" class="btn btn-accent" style="width: 100%;">Accedi al Profilo</button>
    </form>
    
    <div class="text-center mt-1" style="font-size: 0.9rem;">
        Non hai un account? <a href="register.php" style="color: var(--primary-color); font-weight: bold; text-decoration: underline;">Registrati ora</a>
    </div>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>

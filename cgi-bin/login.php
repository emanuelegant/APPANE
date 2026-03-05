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
    $email_input = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    
    $form_input->email = htmlspecialchars($email_input);
    // Non salviamo la password per sicurezza

    if (empty($email_input)) {
        $form_input->email_err = "L'email è obbligatoria.";
    }
    
    if (empty($password_input)) {
        $form_input->password_err = "La password è obbligatoria.";
    }

    if (is_null($form_input->email_err) && is_null($form_input->password_err)) {
        $stmt = $db->prepare("SELECT id_utente, nome, password, ruolo FROM tutente WHERE email = ?");
        $stmt->execute([$email_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password_input, $user['password'])) {
            $_SESSION['user_id'] = $user['id_utente'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_ruolo'] = $user['ruolo'];
            
            // Sincronizza il carrello
            sync_cart_to_db($db, $user['id_utente']);
            
            $redirect = $_GET['redirect'] ?? 'index.php';
            header("Location: " . ($redirect === 'cart' ? 'cart.php' : 'index.php'));
            exit;
        } else {
            $form_input->gen_err = "Credenziali non valide.";
        }
    }
}

require_once __DIR__ . '/include/header.php';
?>

<div class="form-container mt-2">
    <h2 class="text-center" style="color: var(--primary-color);">Accedi ad APPANE</h2>
    
    <?php if (!is_null($form_input->gen_err)): ?>
        <div class="alert alert-error text-center">
            <?= htmlspecialchars((string)$form_input->gen_err) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" class="mt-1">
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="La tua email" value="<?= htmlspecialchars((string)$form_input->email) ?>" maxlength="320" class="<?= is_null($form_input->email_err) ? '' : 'campo-errore' ?>">
            <div style="margin-top: 4px; <?= is_null($form_input->email_err) ? 'display:none' : '' ?>">
                <span class="errore"><?= htmlspecialchars((string)$form_input->email_err) ?></span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="La tua password" maxlength="100" class="<?= is_null($form_input->password_err) ? '' : 'campo-errore' ?>">
            <div style="margin-top: 4px; <?= is_null($form_input->password_err) ? 'display:none' : '' ?>">
                <span class="errore"><?= htmlspecialchars((string)$form_input->password_err) ?></span>
            </div>
        </div>
        
        <button type="submit" class="btn btn-accent" style="width: 100%;">Accedi</button>
    </form>
    
    <div class="text-center mt-1" style="font-size: 0.9rem;">
        Non hai un account? <a href="register.php" style="color: var(--primary-color); font-weight: bold;">Registrati ora</a>
    </div>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>

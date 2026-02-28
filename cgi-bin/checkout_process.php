<?php
require_once __DIR__ . '/include/config.php';

if (!is_order_open() || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$is_guest = isset($_POST['is_guest']) && $_POST['is_guest'] === '1';

if (!$is_guest && !is_logged_in()) {
    header("Location: cart.php");
    exit;
}

// 0. Eseguiamo un fallback silente per alterare il DB in caso di ospite
try {
    $db->exec("ALTER TABLE tordine MODIFY id_utente INT NULL");
} catch(Exception $e) {}
try {
    $db->exec("ALTER TABLE tordine ADD COLUMN nome_guest VARCHAR(100) NULL");
    $db->exec("ALTER TABLE tordine ADD COLUMN cognome_guest VARCHAR(100) NULL");
    $db->exec("ALTER TABLE tordine ADD COLUMN email_guest VARCHAR(320) NULL");
    $db->exec("ALTER TABLE tordine ADD COLUMN telefono_guest VARCHAR(20) NULL");
    $db->exec("ALTER TABLE tordine ADD COLUMN via_civico_guest VARCHAR(255) NULL");
    $db->exec("ALTER TABLE tordine ADD COLUMN cap_guest VARCHAR(10) NULL");
} catch(Exception $e) {}

// Includiamo la classe di stato qui per poterla serializzare e usare nel processo
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

$guest_data = [];

// 1. Validazione dati Ospite usando il Pattern Architetturale richiesto
if ($is_guest) {
    $form_input = new FormCheckout();
    
    $schema = [
        'nome'       => 'string(2,100)',
        'cognome'    => 'string(2,100)',
        'email'      => 'email',
        'telefono'   => 'string(5,20)',
        'via_civico' => 'string(5,255)',
        'cap'        => 'numeric(5)'
    ];
    $val_result = validate_post($schema);
    
    foreach ($val_result->missing_required_params as $missing) {
        $err_prop = $missing . '_err';
        $form_input->$err_prop = "Questo campo è obbligatorio.";
    }

    foreach ($val_result->errors as $field => $errObj) {
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

    // Salvataggio input per non perderli
    foreach (['nome', 'cognome', 'email', 'telefono', 'via_civico', 'cap'] as $f) {
        if (isset($val_result->sanitized_params[$f])) {
            $form_input->$f = $val_result->sanitized_params[$f];
        } elseif (isset($_POST[$f])) {
            $form_input->$f = htmlspecialchars((string)$_POST[$f]);
        }
    }

    // Regola Custom APPANE per il CAP
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

    // Verifica se ci sono errori
    $has_errors = !is_null($form_input->nome_err) || !is_null($form_input->cognome_err) || 
                  !is_null($form_input->email_err) || !is_null($form_input->telefono_err) || 
                  !is_null($form_input->via_civico_err) || !is_null($form_input->cap_err);

    if ($has_errors) {
        $_SESSION['checkout_form_state'] = serialize($form_input);
        header("Location: checkout.php");
        exit;
    }
    
    $guest_data = $val_result->sanitized_params;
}

try {
    $db->beginTransaction();
    
    // 2. Calcola il totale dal DB per sicurezza
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtProd = $db->prepare("SELECT id_prodotto, prezzo FROM tprodotto WHERE id_prodotto IN ($placeholders)");
    $stmtProd->execute($ids);
    
    $totale = 0.0;
    $dettagli = [];
    while ($row = $stmtProd->fetch()) {
        $id_p = $row['id_prodotto'];
        $qty = $_SESSION['cart'][$id_p];
        $prezzo = (float)$row['prezzo'];
        $totale += ($qty * $prezzo);
        $dettagli[] = [
            'id_prodotto' => $id_p,
            'quantita' => $qty,
            'prezzo_storico' => $prezzo
        ];
    }
    
    // 3. Crea l'ordine (Loggato vs Guest)
    if ($is_guest) {
        $stmtOrdine = $db->prepare("
            INSERT INTO tordine (id_utente, totale, nome_guest, cognome_guest, email_guest, telefono_guest, via_civico_guest, cap_guest) 
            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtOrdine->execute([
            $totale,
            $guest_data['nome'], $guest_data['cognome'], $guest_data['email'],
            $guest_data['telefono'], $guest_data['via_civico'], $guest_data['cap']
        ]);
    } else {
        $user_id = $_SESSION['user_id'];
        $stmtOrdine = $db->prepare("INSERT INTO tordine (id_utente, totale) VALUES (?, ?)");
        $stmtOrdine->execute([$user_id, $totale]);
    }
    
    $id_ordine = $db->lastInsertId();
    
    // 4. Inserisci i dettagli dell'ordine
    $stmtDettaglio = $db->prepare("INSERT INTO tdettaglio_ordine (id_ordine, id_prodotto, quantita, prezzo_unitario_storico) VALUES (?, ?, ?, ?)");
    foreach ($dettagli as $d) {
        $stmtDettaglio->execute([$id_ordine, $d['id_prodotto'], $d['quantita'], $d['prezzo_storico']]);
    }
    
    // 5. Svuota DB carrello se loggato
    if (!$is_guest) {
        $stmtSvuota = $db->prepare("
            DELETE ip FROM tistanza_prodotto ip
            JOIN tcarrello c ON ip.id_carrello = c.id_carrello
            WHERE c.id_utente = ?
        ");
        $stmtSvuota->execute([$_SESSION['user_id']]);
    }
    
    // 6. Svuota sessione
    $_SESSION['cart'] = [];
    
    $db->commit();
    header("Location: success.php?id_ordine=" . $id_ordine);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die("Errore durante il checkout: " . $e->getMessage());
}

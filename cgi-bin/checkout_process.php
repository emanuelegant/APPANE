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

// Classe Stato
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

// 1. Validazione dati Ospite usando il Pattern PCTO
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

    $form_input->nome       = $val_result->sanitized_params['nome'] ?? htmlspecialchars((string)($_POST['nome'] ?? ''));
    $form_input->cognome    = $val_result->sanitized_params['cognome'] ?? htmlspecialchars((string)($_POST['cognome'] ?? ''));
    $form_input->email      = $val_result->sanitized_params['email'] ?? htmlspecialchars((string)($_POST['email'] ?? ''));
    $form_input->telefono   = $val_result->sanitized_params['telefono'] ?? htmlspecialchars((string)($_POST['telefono'] ?? ''));
    $form_input->via_civico = $val_result->sanitized_params['via_civico'] ?? htmlspecialchars((string)($_POST['via_civico'] ?? ''));
    $form_input->cap        = $val_result->sanitized_params['cap'] ?? htmlspecialchars((string)($_POST['cap'] ?? ''));
    
    // NOME
    if (in_array('nome', $val_result->missing_required_params) || trim($_POST['nome'] ?? '') === '') {
        $form_input->nome_err = "Questo campo è obbligatorio.";
    } elseif (isset($val_result->errors['nome'])) {
        $nome_err = $val_result->errors['nome'];
        if ($nome_err->less_than_minlen) {
            $form_input->nome_err = "Il nome deve avere almeno 2 caratteri.";
        } elseif ($nome_err->greater_than_maxlen) {
            $form_input->nome_err = "Il nome supera i 100 caratteri ammessi.";
        }
    }

    // COGNOME
    if (in_array('cognome', $val_result->missing_required_params) || trim($_POST['cognome'] ?? '') === '') {
        $form_input->cognome_err = "Questo campo è obbligatorio.";
    } elseif (isset($val_result->errors['cognome'])) {
        $cognome_err = $val_result->errors['cognome'];
        if ($cognome_err->less_than_minlen) {
            $form_input->cognome_err = "Il cognome deve avere almeno 2 caratteri.";
        } elseif ($cognome_err->greater_than_maxlen) {
            $form_input->cognome_err = "Il cognome supera i 100 caratteri ammessi.";
        }
    }

    // EMAIL
    if (in_array('email', $val_result->missing_required_params) || trim($_POST['email'] ?? '') === '') {
        $form_input->email_err = "Questo campo è obbligatorio.";
    } elseif (isset($val_result->errors['email'])) {
        $email_err = $val_result->errors['email'];
        if ($email_err->is_empty) {
            $form_input->email_err = "Questo campo è obbligatorio.";
        } elseif ($email_err->missing_at_symbol) {
             $form_input->email_err = "L'email deve contenere la chiocciola (@).";
        } elseif ($email_err->is_too_long) {
            $form_input->email_err = "L'email inserita è troppo lunga.";
        }
    }

    // TELEFONO
    if (in_array('telefono', $val_result->missing_required_params) || trim($_POST['telefono'] ?? '') === '') {
        $form_input->telefono_err = "Questo campo è obbligatorio.";
    } elseif (isset($val_result->errors['telefono'])) {
        $telefono_err = $val_result->errors['telefono'];
        if ($telefono_err->less_than_minlen) {
            $form_input->telefono_err = "Il numero di telefono è troppo corto.";
        } elseif ($telefono_err->greater_than_maxlen) {
            $form_input->telefono_err = "Il numero di telefono inserito è troppo lungo.";
        }
    } elseif (isset($val_result->sanitized_params['telefono'])) {
        if (!preg_match('/^\+?[0-9\s]+$/', $val_result->sanitized_params['telefono'])) {
            $form_input->telefono_err = "Il telefono può contenere solo numeri e il prefisso (+).";
        }
    }

    // VIA CIVICO
    if (in_array('via_civico', $val_result->missing_required_params) || trim($_POST['via_civico'] ?? '') === '') {
        $form_input->via_civico_err = "Questo campo è obbligatorio.";
    } elseif (isset($val_result->errors['via_civico'])) {
        $via_civico_err = $val_result->errors['via_civico'];
        if ($via_civico_err->less_than_minlen) {
            $form_input->via_civico_err = "L'indirizzo inserito è troppo corto (min. 5 caratteri).";
        } elseif ($via_civico_err->greater_than_maxlen) {
            $form_input->via_civico_err = "L'indirizzo inserito è troppo lungo.";
        }
    }

    // CAP
    if (in_array('cap', $val_result->missing_required_params) || trim($_POST['cap'] ?? '') === '') {
        $form_input->cap_err = "Questo campo è obbligatorio.";
    } elseif (isset($val_result->errors['cap'])) {
        $cap_err = $val_result->errors['cap'];
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
        $cap_for_status = $guest_data['cap'];
        $stato_ordine = str_starts_with($cap_for_status, '34') ? 'confermato_in_preparazione' : 'non_confermato';

        $stmtOrdine = $db->prepare("
            INSERT INTO tordine (id_utente, totale, stato, nome_guest, cognome_guest, email_guest, telefono_guest, via_civico_guest, cap_guest) 
            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtOrdine->execute([
            $totale, $stato_ordine,
            $guest_data['nome'], $guest_data['cognome'], $guest_data['email'],
            $guest_data['telefono'], $guest_data['via_civico'], $guest_data['cap']
        ]);
    } else {
        $user_id = $_SESSION['user_id'];
        $stmtUser = $db->prepare("SELECT cap FROM tutente WHERE id_utente = ?");
        $stmtUser->execute([$user_id]);
        $user_cap = (string)$stmtUser->fetchColumn();
        $stato_ordine = str_starts_with($user_cap, '34') ? 'confermato_in_preparazione' : 'non_confermato';

        $stmtOrdine = $db->prepare("INSERT INTO tordine (id_utente, totale, stato) VALUES (?, ?, ?)");
        $stmtOrdine->execute([$user_id, $totale, $stato_ordine]);
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

<?php
session_start();

require_once __DIR__ . '/pcto.php';

// Inizializza il DB Globale
$db = connectToDb();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}


function is_order_open(): bool
{
    $current_day = (int)date('N');
    return $current_day >= 3 && $current_day <= 4;
    return true;

}

/**
 * Sincronizza il carrello della sessione con il database (tistanza_prodotto e tcarrello).
 * Se l'utente ha un carrello nel DB che non è in sessione, lo unisce/carica.
 */
function sync_cart_to_db(PDO $db, int $user_id)
{
    if (empty($_SESSION['cart']) && is_logged_in()) {
        load_cart_from_db($db, $user_id);
    }
    elseif (!empty($_SESSION['cart']) && is_logged_in()) {
        // Cerca o crea carrello associato all'utente
        $stmtCart = $db->prepare("SELECT id_carrello FROM tcarrello WHERE id_utente = ?");
        $stmtCart->execute([$user_id]);
        $id_carrello = $stmtCart->fetchColumn();

        if (!$id_carrello) {
            $stmtInsertCart = $db->prepare("INSERT INTO tcarrello (id_utente) VALUES (?)");
            $stmtInsertCart->execute([$user_id]);
            $id_carrello = $db->lastInsertId();
        }

        // Recupera le istanze già presenti
        $stmtGetItems = $db->prepare("SELECT id_prodotto, quantita FROM tistanza_prodotto WHERE id_carrello = ?");
        $stmtGetItems->execute([$id_carrello]);
        $dbItems = [];
        while ($row = $stmtGetItems->fetch()) {
            $dbItems[$row['id_prodotto']] = (int)$row['quantita'];
        }

        $stmtInsertItem = $db->prepare("INSERT INTO tistanza_prodotto (id_carrello, id_prodotto, quantita) VALUES (?, ?, ?)");
        $stmtUpdateItem = $db->prepare("UPDATE tistanza_prodotto SET quantita = ? WHERE id_carrello = ? AND id_prodotto = ?");

        foreach ($_SESSION['cart'] as $id_prodotto => $qty) {
            if (isset($dbItems[$id_prodotto])) {
                // Aggiorna se la quantità in sessione è maggiore
                if ($qty > $dbItems[$id_prodotto]) {
                    $stmtUpdateItem->execute([$qty, $id_carrello, $id_prodotto]);
                }
                else {
                    $_SESSION['cart'][$id_prodotto] = $dbItems[$id_prodotto];
                }
            }
            else {
                $stmtInsertItem->execute([$id_carrello, $id_prodotto, $qty]);
            }
        }
    }
}

/**
 * Carica il carrello dal Database in sessione.
 */
function load_cart_from_db(PDO $db, int $user_id)
{
    $stmtCart = $db->prepare("SELECT id_carrello FROM tcarrello WHERE id_utente = ?");
    $stmtCart->execute([$user_id]);
    $id_carrello = $stmtCart->fetchColumn();

    if ($id_carrello) {
        $stmtGetItems = $db->prepare("SELECT id_prodotto, quantita FROM tistanza_prodotto WHERE id_carrello = ?");
        $stmtGetItems->execute([$id_carrello]);
        while ($row = $stmtGetItems->fetch()) {
            $_SESSION['cart'][$row['id_prodotto']] = (int)$row['quantita'];
        }
    }
}

/**
 * Aggiorna la quantità di un prodotto nel carrello.
 * Aggiunge se non esiste, rimuove se qt_diff porta la quantità a <= 0, o aggiorna.
 */
function update_cart(PDO $db, int $id_prodotto, int $qty_diff)
{
    $current = $_SESSION['cart'][$id_prodotto] ?? 0;
    $newQty = $current + $qty_diff;

    if ($newQty <= 0) {
        unset($_SESSION['cart'][$id_prodotto]);
        if (is_logged_in()) {
            // Rimuovi iterazione dal DB
            $stmtCart = $db->prepare("SELECT id_carrello FROM tcarrello WHERE id_utente = ?");
            $stmtCart->execute([$_SESSION['user_id']]);
            $id_carrello = $stmtCart->fetchColumn();

            if ($id_carrello) {
                $stmtDel = $db->prepare("DELETE FROM tistanza_prodotto WHERE id_carrello = ? AND id_prodotto = ?");
                $stmtDel->execute([$id_carrello, $id_prodotto]);
            }
        }
    }
    else {
        $_SESSION['cart'][$id_prodotto] = $newQty;
        if (is_logged_in()) {
            // Aggiorna o Inserisci su DB
            $stmtCart = $db->prepare("SELECT id_carrello FROM tcarrello WHERE id_utente = ?");
            $stmtCart->execute([$_SESSION['user_id']]);
            $id_carrello = $stmtCart->fetchColumn();

            if ($id_carrello) {
                // Controllo se esiste già
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM tistanza_prodotto WHERE id_carrello = ? AND id_prodotto = ?");
                $stmtCheck->execute([$id_carrello, $id_prodotto]);

                if ($stmtCheck->fetchColumn() > 0) {
                    $stmtUpd = $db->prepare("UPDATE tistanza_prodotto SET quantita = ? WHERE id_carrello = ? AND id_prodotto = ?");
                    $stmtUpd->execute([$newQty, $id_carrello, $id_prodotto]);
                }
                else {
                    $stmtIns = $db->prepare("INSERT INTO tistanza_prodotto (id_carrello, id_prodotto, quantita) VALUES (?, ?, ?)");
                    $stmtIns->execute([$id_carrello, $id_prodotto, $newQty]);
                }
            }
            else {
                $stmtInsertCart = $db->prepare("INSERT INTO tcarrello (id_utente) VALUES (?)");
                $stmtInsertCart->execute([$_SESSION['user_id']]);
                $id_c = $db->lastInsertId();
                $stmtIns = $db->prepare("INSERT INTO tistanza_prodotto (id_carrello, id_prodotto, quantita) VALUES (?, ?, ?)");
                $stmtIns->execute([$id_c, $id_prodotto, $newQty]);
            }
        }
    }
}

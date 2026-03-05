<?php
require_once __DIR__ . '/include/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_prodotto = (int)($_POST['id_prodotto'] ?? 0);

    if ($id_prodotto > 0) {
        if ($action === 'add') {
            $quantita = (int)($_POST['quantita'] ?? 1);
            update_cart($db, $id_prodotto, $quantita);

        }
        elseif ($action === 'update') {
            $nuova_quantita = (int)($_POST['quantita'] ?? 0);

            $qty_attuale = 0;
            if (isset($_SESSION['cart'][$id_prodotto])) {
                $qty_attuale = $_SESSION['cart'][$id_prodotto];
            }

            $differenza = $nuova_quantita - $qty_attuale;
            if ($differenza !== 0) {
                update_cart($db, $id_prodotto, $differenza);
            }

        }
        elseif ($action === 'remove') {
            if (isset($_SESSION['cart'][$id_prodotto])) {
                $qty_attuale = $_SESSION['cart'][$id_prodotto];
                update_cart($db, $id_prodotto, -$qty_attuale);
            }
        }
    }

    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cart_count' => isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0
        ]);
        exit;
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
header("Location: " . $referer);
exit;

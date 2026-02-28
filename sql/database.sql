-- Creazione del database
CREATE DATABASE IF NOT EXISTS appane_parodi;
USE appane_parodi;

SET default_storage_engine=InnoDB;

DROP TABLE IF EXISTS tdettaglio_ordine;
DROP TABLE IF EXISTS tordine;
DROP TABLE IF EXISTS tistanza_prodotto;
DROP TABLE IF EXISTS tcarrello;
DROP TABLE IF EXISTS tricetta;
DROP TABLE IF EXISTS tingrediente;
DROP TABLE IF EXISTS tprodotto;
DROP TABLE IF EXISTS tutente;

-- 1. Tabelle Anagrafiche
CREATE TABLE tutente (
    id_utente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(320) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Per hashing sicuro
    telefono VARCHAR(20) NOT NULL,
    via_civico VARCHAR(255) NOT NULL,
    cap VARCHAR(10) NOT NULL, -- Verrà validato lato PHP per iniziare con '34'
    ruolo ENUM('cliente', 'admin') DEFAULT 'cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tprodotto (
    id_prodotto INT AUTO_INCREMENT PRIMARY KEY,
    nome_prodotto VARCHAR(150) NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    confezione VARCHAR(50) NOT NULL, -- es. 'Pezzo', 'Kg', '500g'
    tipologia ENUM('Pane speciale', 'Pizza intera', 'Brioche') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tingrediente (
    id_ingrediente INT AUTO_INCREMENT PRIMARY KEY,
    nome_ingrediente VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella di raccordo Prodotto <-> Ingrediente (Ricetta segreta, solo nomi visibili)
CREATE TABLE tricetta (
    id_prodotto INT NOT NULL,
    id_ingrediente INT NOT NULL,
    PRIMARY KEY (id_prodotto, id_ingrediente),
    FOREIGN KEY (id_prodotto) REFERENCES tprodotto(id_prodotto) ON DELETE CASCADE,
    FOREIGN KEY (id_ingrediente) REFERENCES tingrediente(id_ingrediente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Carrello Persistente (solo per utenti registrati/loggati)
CREATE TABLE tcarrello (
    id_carrello INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL UNIQUE, -- Un utente ha un solo carrello attivo (se esiste nel DB)
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utente) REFERENCES tutente(id_utente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tistanza_prodotto (
    id_istanza INT AUTO_INCREMENT PRIMARY KEY,
    id_carrello INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_carrello) REFERENCES tcarrello(id_carrello) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES tprodotto(id_prodotto) ON DELETE CASCADE,
    UNIQUE(id_carrello, id_prodotto) -- Evita duplicati dello stesso prodotto nello stesso carrello
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Ordini
CREATE TABLE tordine (
    id_ordine INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    data_ordine DATETIME DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('ricevuto', 'in_preparazione', 'in_consegna', 'consegnato', 'annullato') DEFAULT 'ricevuto',
    totale DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_utente) REFERENCES tutente(id_utente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tdettaglio_ordine (
    id_dettaglio INT AUTO_INCREMENT PRIMARY KEY,
    id_ordine INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita INT NOT NULL,
    prezzo_unitario_storico DECIMAL(10,2) NOT NULL, -- Fotografia del prezzo al momento dell'ordine
    FOREIGN KEY (id_ordine) REFERENCES tordine(id_ordine) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES tprodotto(id_prodotto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- INSERIMENTO DATI MOCK (SEED)
-- --------------------------------------------------------

-- Prodotti
INSERT INTO tprodotto (nome_prodotto, prezzo, confezione, tipologia) VALUES
('Pane bianco (mix farine)', 6.50, 'Pezzo 800g', 'Pane speciale'),
('Brown sugar', 7.50, 'Pezzo 800g', 'Pane speciale'),
('Semi biando', 7.50, 'Pezzo 800g', 'Pane speciale'),
('Artù (mix di semi girasole, lino)', 6.50, 'Pezzo 800g', 'Pane speciale'),
('Pan oliven', 7.50, 'Pezzo 800g', 'Pane speciale'),
('Alfa (farro e bianca integrale)', 6.50, 'Pezzo 800g', 'Pane speciale'),
('\'A PIZZZ Marghe (classica)', 13.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ Tina Cipollari (cipolla rosa di tropea scottata, debolmente rossa)', 14.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ VerdeVerde (bianca, mix formaggi, zucchine saltate e menta)', 15.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ Rossalsiccia (rossa con salsiccia)', 14.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ Teufel', 14.50, 'Intera', 'Pizza intera'),
('Croissant classico 126 sfoglie', 2.50, 'Pezzo', 'Brioche');

-- Ingredienti (Estrazione logica dai nomi per dimostrazione visiva)
INSERT INTO tingrediente (nome_ingrediente) VALUES
('Mix Farine'), ('Farina di Segale'), ('Zucchero Brown'), 
('Semi di Girasole'), ('Semi di Lino'), ('Olive'), ('Farina di Farro'),
('Farina Bianca Integrale'), ('Pomodoro'), ('Mozzarella'),
('Cipolla Rosa di Tropea'), ('Mix Formaggi'), ('Parmigiano Grattugiato'),
('Zucchine'), ('Menta'), ('Salsiccia'), ('Sfoglia Classica (Burro)');

-- Ricette (Associazioni logiche per l'esposizione)
-- Pane bianco
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (1, 1);
-- Brown sugar
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (2, 2), (2, 3);
-- Semi biando
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (3, 4), (3, 5);
-- Artù
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (4, 4), (4, 5);
-- Pan oliven
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (5, 6);
-- Alfa
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (6, 7), (6, 8);
-- 'A PIZZZ Marghe
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (7, 9), (7, 10);
-- 'A PIZZZ Tina Cipollari
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (8, 9), (8, 10), (8, 11);
-- 'A PIZZZ VerdeVerde
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (9, 10), (9, 12), (9, 13), (9, 14), (9, 15);
-- 'A PIZZZ Rossalsiccia
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (10, 9), (10, 10), (10, 16);
-- 'A PIZZZ Teufel
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (11, 9), (11, 10);
-- Croissant
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (12, 17);

-- Utente Mock (Password: password123, hashata con BYCHRYPT)
INSERT INTO tutente (nome, cognome, email, password, telefono, via_civico, cap, ruolo) VALUES
('Mario', 'Rossi', 'mario@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3331234567', 'Via Roma 1', '34100', 'cliente');

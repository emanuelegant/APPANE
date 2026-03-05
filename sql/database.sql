DROP TABLE IF EXISTS tdettaglio_ordine;
DROP TABLE IF EXISTS tordine;
DROP TABLE IF EXISTS tistanza_prodotto;
DROP TABLE IF EXISTS tcarrello;
DROP TABLE IF EXISTS tricetta;
DROP TABLE IF EXISTS tingrediente;
DROP TABLE IF EXISTS tprodotto;
DROP TABLE IF EXISTS tutente;

CREATE TABLE tutente (
    id_utente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(320) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, 
    telefono VARCHAR(20) NOT NULL,
    via_civico VARCHAR(255) NOT NULL,
    cap VARCHAR(10) NOT NULL, 
    ruolo ENUM('cliente', 'admin') DEFAULT 'cliente'
);

CREATE TABLE tprodotto (
    id_prodotto INT AUTO_INCREMENT PRIMARY KEY,
    nome_prodotto VARCHAR(150) NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    confezione VARCHAR(50) NOT NULL, 
    tipologia ENUM('Pane speciale', 'Pizza intera', 'Brioche') NOT NULL
);

CREATE TABLE tingrediente (
    id_ingrediente INT AUTO_INCREMENT PRIMARY KEY,
    nome_ingrediente VARCHAR(150) NOT NULL
);

CREATE TABLE tricetta (
    id_prodotto INT NOT NULL,
    id_ingrediente INT NOT NULL,
    PRIMARY KEY (id_prodotto, id_ingrediente),
    FOREIGN KEY (id_prodotto) REFERENCES tprodotto(id_prodotto) ON DELETE CASCADE,
    FOREIGN KEY (id_ingrediente) REFERENCES tingrediente(id_ingrediente) ON DELETE CASCADE
);

CREATE TABLE tcarrello (
    id_carrello INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL UNIQUE, 
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utente) REFERENCES tutente(id_utente) ON DELETE CASCADE
);

CREATE TABLE tistanza_prodotto (
    id_istanza INT AUTO_INCREMENT PRIMARY KEY,
    id_carrello INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_carrello) REFERENCES tcarrello(id_carrello) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES tprodotto(id_prodotto) ON DELETE CASCADE,
    UNIQUE(id_carrello, id_prodotto)
);

CREATE TABLE tordine (
    id_ordine INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    data_ordine DATETIME DEFAULT CURRENT_TIMESTAMP,
    stato ENUM('ricevuto', 'in_preparazione', 'in_consegna', 'consegnato', 'annullato') DEFAULT 'ricevuto',
    totale DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_utente) REFERENCES tutente(id_utente) ON DELETE CASCADE
);

CREATE TABLE tdettaglio_ordine (
    id_dettaglio INT AUTO_INCREMENT PRIMARY KEY,
    id_ordine INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita INT NOT NULL,
    prezzo_unitario_storico DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_ordine) REFERENCES tordine(id_ordine) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES tprodotto(id_prodotto) ON DELETE CASCADE
);

CREATE TABLE tmenu (
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    nome_menu VARCHAR(100) NOT NULL,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tmenu_prodotto (
    id_menu INT NOT NULL,
    id_prodotto INT NOT NULL,
    PRIMARY KEY (id_menu, id_prodotto),
    FOREIGN KEY (id_menu) REFERENCES tmenu(id_menu) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES tprodotto(id_prodotto) ON DELETE CASCADE
);


-- Pulizia tabelle per un inserimento pulito
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE tmenu_prodotto;
TRUNCATE TABLE tmenu;
TRUNCATE TABLE tdettaglio_ordine;
TRUNCATE TABLE tordine;
TRUNCATE TABLE tistanza_prodotto;
TRUNCATE TABLE tcarrello;
TRUNCATE TABLE tricetta;
TRUNCATE TABLE tingrediente;
TRUNCATE TABLE tprodotto;
TRUNCATE TABLE tutente;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. UTENTI (Credenziali aggiornate come richiesto)
INSERT INTO tutente (nome, cognome, email, password, telefono, via_civico, cap, ruolo) VALUES
('admin', 'admin', 'admin@appane.it', '$2y$10$Pnu38CYrgFu703wTO6YrFuxNBGPuQ/vYW3oCIolXTfaaZzUK1T7Qi', '0123456789', 'Sede Centrale', '34100', 'admin'),
('cliente', 'cliente', 'cliente@appane.it', '$2y$10$ROKFxIRHDAiBGEfwvvW5bO9m2EXG7VTStrMWM0vToG3oLKFz37Alu', '3331234567', 'Via Roma 1', '34100', 'cliente');

-- 2. PRODOTTI (Percorsi per cgi-bin ed estensione .png)
INSERT INTO tprodotto (id_prodotto, nome_prodotto, prezzo, confezione, tipologia, url_immagine) VALUES
(1, 'Pane biango (mix farine)', 6.50, 'Pezzo 800g', 'Pane speciale', '../httpdocs/img/prodotti/pane_biango.png'),
(2, 'Brown sugar', 7.50, 'Pezzo 800g', 'Pane speciale', '../httpdocs/img/prodotti/brown_sugar.png'),
(3, 'Semi biango', 7.50, 'Pezzo 800g', 'Pane speciale', '../httpdocs/img/prodotti/semi_biango.png'),
(4, 'Artù (mix di semi girasole, lino)', 6.50, 'Pezzo 800g', 'Pane speciale', '../httpdocs/img/prodotti/artu.png'),
(5, 'Pan oliven', 7.50, 'Pezzo 800g', 'Pane speciale', '../httpdocs/img/prodotti/pan_oliven.png'),
(6, 'Alfa (farro e bianca integrale)', 6.50, 'Pezzo 800g', 'Pane speciale', '../httpdocs/img/prodotti/alfa.png'),
(7, '\'A PIZZZ Marghe (classica)', 13.50, 'Intera', 'Pizza intera', '../httpdocs/img/prodotti/margherita.png'),
(8, '\'A PIZZZ Tina Cipollari', 14.50, 'Intera', 'Pizza intera', '../httpdocs/img/prodotti/tina_cipollari.png'),
(9, '\'A PIZZZ VerdeVerde', 15.50, 'Intera', 'Pizza intera', '../httpdocs/img/prodotti/verde_verde.png'),
(10, '\'A PIZZZ Rossalsiccia', 14.50, 'Intera', 'Pizza intera', '../httpdocs/img/prodotti/rossalsiccia.png'),
(11, '\'A PIZZZ Teufel', 14.50, 'Intera', 'Pizza intera', '../httpdocs/img/prodotti/teufel.png'),
(12, 'La Briocha', 2.50, 'Pezzo', 'Brioche', '../httpdocs/img/prodotti/briocha.png');

-- 3. INGREDIENTI
INSERT INTO tingrediente (id_ingrediente, nome_ingrediente) VALUES
(1, 'Mix Farine'), (2, 'Farina di Segale'), (3, 'Zucchero Brown'), 
(4, 'Semi di Girasole'), (5, 'Semi di Lino'), (6, 'Olive'), (7, 'Farina di Farro'),
(8, 'Farina Bianca Integrale'), (9, 'Pomodoro'), (10, 'Mozzarella'),
(11, 'Cipolla Rosa di Tropea'), (12, 'Mix Formaggi'), (13, 'Parmigiano Grattugiato'),
(14, 'Zucchine'), (15, 'Menta'), (16, 'Salsiccia'), (17, 'Sfoglia Classica (Burro)');

-- 4. RICETTE
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES 
(1, 1), (2, 2), (2, 3), (3, 4), (3, 5), (4, 4), (4, 5), (5, 6), (6, 7), (6, 8), 
(7, 9), (7, 10), (8, 9), (8, 10), (8, 11), (9, 10), (9, 12), (9, 14), (9, 15), 
(10, 9), (10, 10), (10, 16), (11, 9), (11, 10), (12, 17);

-- 5. MENU
INSERT INTO tmenu (id_menu, nome_menu, stato) VALUES 
(1, 'Menù Settimanale Corrente', 'attivo');

-- 6. ASSOCIAZIONE PRODOTTI AL MENU
INSERT INTO tmenu_prodotto (id_menu, id_prodotto) VALUES
(1, 1), (1, 2), (1, 3), (1, 5),(1, 7), (1, 8), (1, 9);


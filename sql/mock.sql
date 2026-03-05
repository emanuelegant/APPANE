-- Prodotti
INSERT INTO tprodotto (nome_prodotto, prezzo, confezione, tipologia) VALUES
('Pane biango (mix farine)', 6.50, 'Pezzo 800g', 'Pane speciale'),
('Brown sugar', 7.50, 'Pezzo 800g', 'Pane speciale'),
('Semi biango', 7.50, 'Pezzo 800g', 'Pane speciale'),
('Artù (mix di semi girasole, lino)', 6.50, 'Pezzo 800g', 'Pane speciale'),
('Pan oliven', 7.50, 'Pezzo 800g', 'Pane speciale'),
('Alfa (farro e bianca integrale)', 6.50, 'Pezzo 800g', 'Pane speciale'),
('\'A PIZZZ Marghe (classica)', 13.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ Tina Cipollari (cipolla rosa di tropea scottata, debolmente rossa)', 14.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ VerdeVerde (bianca, mix formaggi, zucchine saltate e menta)', 15.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ Rossalsiccia (rossa con salsiccia)', 14.50, 'Intera', 'Pizza intera'),
('\'A PIZZZ Teufel', 14.50, 'Intera', 'Pizza intera'),
('La Briocha', 2.50, 'Pezzo', 'Brioche');

-- Ingredienti (Estrazione logica dai nomi per dimostrazione visiva)
INSERT INTO tingrediente (nome_ingrediente) VALUES
('Mix Farine'), ('Farina di Segale'), ('Zucchero Brown'), 
('Semi di Girasole'), ('Semi di Lino'), ('Olive'), ('Farina di Farro'),
('Farina Bianca Integrale'), ('Pomodoro'), ('Mozzarella'),
('Cipolla Rosa di Tropea'), ('Mix Formaggi'), ('Parmigiano Grattugiato'),
('Zucchine'), ('Menta'), ('Salsiccia'), ('Sfoglia Classica (Burro)');

-- Ricette (Associazioni logiche per l'esposizione)
-- Pane biango
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (1, 1);
-- Brown sugar
INSERT INTO tricetta (id_prodotto, id_ingrediente) VALUES (2, 2), (2, 3);
-- Semi biango
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

INSERT INTO tmenu (nome_menu) VALUES ('Menù Classico');

INSERT INTO tmenu_prodotto (id_menu, id_prodotto) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 9), (1, 11);


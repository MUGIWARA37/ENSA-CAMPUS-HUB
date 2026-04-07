USE club_management;

-- Optional: clean tables before seeding (respect FK order)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE EVENEMENT_ETUDIANT;
TRUNCATE TABLE ETUDIANT_CLUB;
TRUNCATE TABLE PASSWORD_RESET;
TRUNCATE TABLE EVENEMENT;
TRUNCATE TABLE CLUB;
TRUNCATE TABLE ETUDIANT;
SET FOREIGN_KEY_CHECKS = 1;

-- =========================
-- ETUDIANT
-- =========================
INSERT INTO ETUDIANT (etudiant_id, fist_name, last_name, privilege, classe, email, password) VALUES
('ETU001', 'Luffy',   'Monkey',   'A', 'CS1', 'luffy@school.test',   '$2y$10$testhash_luffy'),
('ETU002', 'Zoro',    'Roronoa',  'U', 'CS2', 'zoro@school.test',    '$2y$10$testhash_zoro'),
('ETU003', 'Nami',    'Cat',      'U', 'CS1', 'nami@school.test',    '$2y$10$testhash_nami'),
('ETU004', 'Sanji',   'Vinsmoke', 'U', 'CS3', 'sanji@school.test',   '$2y$10$testhash_sanji'),
('ETU005', 'Robin',   'Nico',     'U', 'CS2', 'robin@school.test',   '$2y$10$testhash_robin'),
('ETU006', 'Usopp',   'Sogeking', 'U', 'CS1', 'usopp@school.test',   '$2y$10$testhash_usopp');

-- =========================
-- CLUB
-- id_admin_etud references ETUDIANT(etudiant_id)
-- =========================
INSERT INTO CLUB (club_name, descriptoin, incs_fees, id_admin_etud) VALUES
('Robotics Club',    'Build robots and compete in challenges', 100.00, 'ETU001'),
('Music Club',       'Band practice and live performances',      50.00, 'ETU003'),
('Sports Club',      'Football, basketball and training',        30.00, 'ETU002'),
('Art Club',         'Drawing, painting and design workshops',   40.00, 'ETU005');

-- =========================
-- ETUDIANT_CLUB (membership)
-- status: ENUM('PENDING','APPROVED','REJECTED','ACTIVE','INACTIVE','BANNED','LEFT','EXPELLED')
-- =========================
INSERT INTO ETUDIANT_CLUB (etudiant_id, club_id, status, registration_date) VALUES
('ETU001', 1, 'ACTIVE',   '2026-01-10 09:00:00'),
('ETU002', 1, 'APPROVED', '2026-01-12 10:00:00'),
('ETU003', 1, 'PENDING',  '2026-01-15 11:00:00'),

('ETU003', 2, 'ACTIVE',   '2026-01-05 14:00:00'),
('ETU004', 2, 'APPROVED', '2026-01-07 15:00:00'),
('ETU006', 2, 'REJECTED', '2026-01-08 16:00:00'),

('ETU002', 3, 'ACTIVE',   '2026-01-03 08:00:00'),
('ETU004', 3, 'ACTIVE',   '2026-01-04 08:30:00'),
('ETU005', 3, 'LEFT',     '2026-01-20 12:00:00'),

('ETU005', 4, 'ACTIVE',   '2026-01-02 13:00:00'),
('ETU006', 4, 'APPROVED', '2026-01-06 13:30:00');

-- =========================
-- EVENEMENT
-- status: ENUM('PLANNED','OPEN','CLOSED','CANCELLED','POSTPONED','DONE','DRAFT','ARCHIVED')
-- =========================
INSERT INTO EVENEMENT
(event_name, event_type, event_date_start, event_date_end, place, participation_fees, event_budget, club_id, status) VALUES
('Line Follower Challenge', 'Competition', '2026-05-10 09:00:00', '2026-05-10 17:00:00', 'Lab A',      10.00,  500.00, 1, 'OPEN'),
('Spring Concert',          'Show',        '2026-05-20 18:00:00', '2026-05-20 21:00:00', 'Main Hall',   5.00,  300.00, 2, 'PLANNED'),
('Interclass Tournament',   'Sports',      '2026-06-01 08:00:00', '2026-06-03 18:00:00', 'Stadium',    15.00, 1200.00, 3, 'OPEN'),
('Watercolor Workshop',     'Workshop',    '2026-04-25 14:00:00', '2026-04-25 17:00:00', 'Art Room',    0.00,  150.00, 4, 'DONE');

-- =========================
-- EVENEMENT_ETUDIANT
-- status: ENUM('REGISTERED','WAITING','CONFIRMED','ATTENDED','ABSENT','CANCELLED','REJECTED','REFUNDED')
-- =========================
INSERT INTO EVENEMENT_ETUDIANT (event_id, etudiant_id, status, registered_at) VALUES
(1, 'ETU001', 'CONFIRMED', '2026-04-01 10:00:00'),
(1, 'ETU002', 'REGISTERED','2026-04-02 10:00:00'),
(1, 'ETU003', 'WAITING',   '2026-04-03 10:00:00'),

(2, 'ETU003', 'CONFIRMED', '2026-04-05 11:00:00'),
(2, 'ETU004', 'REGISTERED','2026-04-06 11:00:00'),

(3, 'ETU002', 'CONFIRMED', '2026-04-07 09:00:00'),
(3, 'ETU004', 'CONFIRMED', '2026-04-07 09:05:00'),
(3, 'ETU005', 'CANCELLED', '2026-04-08 09:10:00'),

(4, 'ETU005', 'ATTENDED',  '2026-04-10 14:00:00'),
(4, 'ETU006', 'ABSENT',    '2026-04-10 14:05:00');

-- =========================
-- PASSWORD_RESET
-- one row per student (PK = etudiant_id)
-- =========================
INSERT INTO PASSWORD_RESET
(etudiant_id, question1, answer1_hash, question2, answer2_hash, question3, answer3_hash) VALUES
('ETU001', 'Your first pet?',      '$2y$10$ans1_etu001', 'Favorite teacher?', '$2y$10$ans2_etu001', 'Birth city?', '$2y$10$ans3_etu001'),
('ETU002', 'Your first pet?',      '$2y$10$ans1_etu002', 'Favorite teacher?', '$2y$10$ans2_etu002', 'Birth city?', '$2y$10$ans3_etu002'),
('ETU003', 'Your first pet?',      '$2y$10$ans1_etu003', 'Favorite teacher?', '$2y$10$ans2_etu003', 'Birth city?', '$2y$10$ans3_etu003'),
('ETU004', 'Your first pet?',      '$2y$10$ans1_etu004', 'Favorite teacher?', '$2y$10$ans2_etu004', 'Birth city?', '$2y$10$ans3_etu004'),
('ETU005', 'Your first pet?',      '$2y$10$ans1_etu005', 'Favorite teacher?', '$2y$10$ans2_etu005', 'Birth city?', '$2y$10$ans3_etu005'),
('ETU006', 'Your first pet?',      '$2y$10$ans1_etu006', 'Favorite teacher?', '$2y$10$ans2_etu006', 'Birth city?', '$2y$10$ans3_etu006');

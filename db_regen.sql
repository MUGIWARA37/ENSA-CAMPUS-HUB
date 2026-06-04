-- =========================
-- CREATE DATABASE
-- =========================
CREATE DATABASE IF NOT EXISTS club_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE club_management;

-- Optional: avoid FK order issues while creating
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS PASSWORD_RESET;
DROP TABLE IF EXISTS EVENEMENT_ETUDIANT;
DROP TABLE IF EXISTS EVENEMENT;
DROP TABLE IF EXISTS ETUDIANT_CLUB;
DROP TABLE IF EXISTS CLUB;
DROP TABLE IF EXISTS ETUDIANT;

-- =========================
-- TABLE: ETUDIANT
-- =========================
CREATE TABLE ETUDIANT (
    etudiant_id   VARCHAR(50)  NOT NULL,
    fist_name     VARCHAR(50)  NOT NULL,   -- kept as in your screenshot
    last_name     VARCHAR(50)  NOT NULL,
    privilege     VARCHAR(1)   NOT NULL,
    classe        VARCHAR(6)   NOT NULL,
    email         VARCHAR(150) NOT NULL,
    password      VARCHAR(150) NOT NULL,
    PRIMARY KEY (etudiant_id),
    UNIQUE KEY uq_etudiant_email (email)
) ENGINE=InnoDB;

-- =========================
-- TABLE: CLUB
-- =========================
CREATE TABLE CLUB (
    club_id          INT           NOT NULL AUTO_INCREMENT,
    club_name        VARCHAR(255)  NOT NULL,
    descriptoin      TEXT,                     -- kept as in your screenshot
    incs_fees        DECIMAL(10,2) DEFAULT 0,  -- set precision
    id_admin_etudiant    VARCHAR(50)   NOT NULL,
    PRIMARY KEY (club_id),
    KEY idx_club_admin (id_admin_etudiant),
    CONSTRAINT fk_club_admin_etudiant
        FOREIGN KEY (id_admin_etudiant)
        REFERENCES ETUDIANT(etudiant_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================
-- TABLE: ETUDIANT_CLUB (many-to-many)
-- =========================
CREATE TABLE ETUDIANT_CLUB (
    etudiant_id       VARCHAR(50) NOT NULL,
    club_id           INT         NOT NULL,
    status            ENUM('pending','accepted','rejected','ACTIVE','INACTIVE','BANNED','LEFT','EXPELLED') DEFAULT 'pending',
    registration_date DATETIME    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (etudiant_id, club_id),
    KEY idx_ec_club (club_id),
    CONSTRAINT fk_ec_etudiant
        FOREIGN KEY (etudiant_id)
        REFERENCES ETUDIANT(etudiant_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_ec_club
        FOREIGN KEY (club_id)
        REFERENCES CLUB(club_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- TABLE: EVENEMENT
-- =========================
CREATE TABLE EVENEMENT (
    event_id            INT           NOT NULL AUTO_INCREMENT,
    event_name          VARCHAR(255)  NOT NULL,
    event_type          VARCHAR(100),
    event_date_start    DATETIME      NOT NULL,
    event_date_end      DATETIME      NOT NULL,
    place               VARCHAR(255),
    participation_fees  DECIMAL(10,2) DEFAULT 0,
    event_budget        DECIMAL(12,2) DEFAULT 0,
    club_id             INT           NOT NULL,
    status              ENUM('pending','approved','PLANNED','OPEN','CLOSED','CANCELLED','POSTPONED','DONE','DRAFT','ARCHIVED') DEFAULT 'pending',
    PRIMARY KEY (event_id),
    KEY idx_event_club (club_id),
    CONSTRAINT fk_event_club
        FOREIGN KEY (club_id)
        REFERENCES CLUB(club_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_event_dates CHECK (event_date_end >= event_date_start)
) ENGINE=InnoDB;

-- =========================
-- TABLE: EVENEMENT_ETUDIANT (many-to-many)
-- =========================
CREATE TABLE EVENEMENT_ETUDIANT (
    event_id       INT         NOT NULL,
    etudiant_id    VARCHAR(50) NOT NULL,
    status         ENUM('pending','accepted','rejected','REGISTERED','WAITING','CONFIRMED','ATTENDED','ABSENT','CANCELLED','REFUNDED') DEFAULT 'pending',
    registered_at  DATETIME    DEFAULT CURRENT_TIMESTAMP,
    student_rating INT         DEFAULT 0,
    PRIMARY KEY (event_id, etudiant_id),
    KEY idx_ee_etudiant (etudiant_id),
    CONSTRAINT fk_ee_event
        FOREIGN KEY (event_id)
        REFERENCES EVENEMENT(event_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_ee_etudiant
        FOREIGN KEY (etudiant_id)
        REFERENCES ETUDIANT(etudiant_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- TABLE: PASSWORD_RESET
-- =========================
CREATE TABLE PASSWORD_RESET (
    etudiant_id   VARCHAR(50)  NOT NULL,
    question1     VARCHAR(255) NOT NULL,
    answer1_hash  VARCHAR(255) NOT NULL,
    question2     VARCHAR(255) NOT NULL,
    answer2_hash  VARCHAR(255) NOT NULL,
    question3     VARCHAR(255) NOT NULL,
    answer3_hash  VARCHAR(255) NOT NULL,
    PRIMARY KEY (etudiant_id),
    CONSTRAINT fk_pr_etudiant
        FOREIGN KEY (etudiant_id)
        REFERENCES ETUDIANT(etudiant_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

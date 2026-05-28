-- ============================================================
-- Banc de Llibres — IES PORÇONS
-- Esquema de base de dades — v4.0
-- Curs 2025/2026
--
-- Ús:
--   mysql -u root -p -e "CREATE DATABASE banc_llibres CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
--   mysql -u root -p banc_llibres < schema.sql
--
-- Usuari d'aplicació (opcional):
--   CREATE USER 'banc_user'@'localhost' IDENTIFIED BY 'contrasenya';
--   GRANT ALL PRIVILEGES ON banc_llibres.* TO 'banc_user'@'localhost';
--   FLUSH PRIVILEGES;
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ------------------------------------------------------------
-- usuaris
-- Rols: admin | professor | alumne
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuaris` (
  `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username`   varchar(60)  NOT NULL,
  `password`   varchar(255) NOT NULL,
  `nom`        varchar(100) NOT NULL,
  `cognoms`    varchar(150) NOT NULL,
  `email`      varchar(150) DEFAULT NULL,
  `rol`        enum('admin','professor','alumne') NOT NULL DEFAULT 'professor',
  `actiu`      tinyint(1) NOT NULL DEFAULT 1,
  `document`   varchar(20)  DEFAULT NULL,
  `telefon`    varchar(20)  DEFAULT NULL,
  `dark_mode`  tinyint(1)   NOT NULL DEFAULT 0,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Usuari admin per defecte (password: Admin1234!)
INSERT INTO `usuaris` (`username`, `password`, `nom`, `cognoms`, `email`, `rol`, `actiu`)
VALUES ('admin@admin.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Administrador', 'Sistema', 'admin@admin.com', 'admin', 1);

-- ------------------------------------------------------------
-- cursos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cursos` (
  `id`    int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codi`  varchar(20)  NOT NULL,
  `nom`   varchar(100) NOT NULL,
  `actiu` tinyint(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codi` (`codi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- classes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classes` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `curs_id`     int(10) unsigned NOT NULL,
  `nom`         varchar(50) NOT NULL,
  `tutor_id`    int(10) unsigned DEFAULT NULL,
  `curs_escolar` varchar(9) NOT NULL DEFAULT '2025/2026',
  PRIMARY KEY (`id`),
  KEY `curs_id`  (`curs_id`),
  KEY `tutor_id` (`tutor_id`),
  CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`curs_id`)  REFERENCES `cursos`  (`id`),
  CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `usuaris` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- professor_classe  (relació N:M professor ↔ classe)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `professor_classe` (
  `professor_id` int(10) unsigned NOT NULL,
  `classe_id`    int(10) unsigned NOT NULL,
  PRIMARY KEY (`professor_id`, `classe_id`),
  KEY `classe_id` (`classe_id`),
  CONSTRAINT `professor_classe_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuaris` (`id`),
  CONSTRAINT `professor_classe_ibfk_2` FOREIGN KEY (`classe_id`)    REFERENCES `classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- alumnes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alumnes` (
  `id`                  int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuari_id`           int(10) unsigned DEFAULT NULL,
  `nom`                 varchar(100) NOT NULL,
  `cognoms`             varchar(150) NOT NULL,
  `classe_id`           int(10) unsigned NOT NULL,
  `email_familia`       varchar(150) DEFAULT NULL,
  `telefon_familia`     varchar(20)  DEFAULT NULL,
  `actiu`               tinyint(1)   NOT NULL DEFAULT 1,
  `nia`                 varchar(20)  DEFAULT NULL,
  `dni`                 varchar(20)  DEFAULT NULL,
  `email_institucional` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuari_id`  (`usuari_id`),
  KEY `classe_id`  (`classe_id`),
  CONSTRAINT `alumnes_ibfk_1` FOREIGN KEY (`usuari_id`) REFERENCES `usuaris` (`id`),
  CONSTRAINT `alumnes_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- materies
-- tipus: comuna (pertany a lot) | optativa (sense lot)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materies` (
  `id`     int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codi`   varchar(10)  NOT NULL,
  `nom`    varchar(150) NOT NULL,
  `tipus`  enum('comuna','optativa') NOT NULL DEFAULT 'comuna',
  `curs_id` int(10) unsigned DEFAULT NULL,
  `actiu`  tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codi` (`codi`),
  KEY `curs_id` (`curs_id`),
  CONSTRAINT `materies_ibfk_1` FOREIGN KEY (`curs_id`) REFERENCES `cursos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- llibres
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `llibres` (
  `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titol`      varchar(255) NOT NULL,
  `isbn`       varchar(20)  DEFAULT NULL,
  `editorial`  varchar(150) DEFAULT NULL,
  `materia_id` int(10) unsigned NOT NULL,
  `curs_id`    int(10) unsigned NOT NULL,
  `actiu`      tinyint(1)   NOT NULL DEFAULT 1,
  `tipus`      enum('obligatori','optativa','modul') DEFAULT 'obligatori',
  PRIMARY KEY (`id`),
  KEY `materia_id` (`materia_id`),
  KEY `curs_id`    (`curs_id`),
  CONSTRAINT `llibres_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materies` (`id`),
  CONSTRAINT `llibres_ibfk_2` FOREIGN KEY (`curs_id`)    REFERENCES `cursos`   (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- exemplars  (còpies físiques de cada llibre)
-- codi permanent: LOT-[CURS]-[NUM]-[MAT]-[EX] o OPT-[CURS]-[MAT]-[EX]
-- estat: nou | bo | deteriorat | perdut
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exemplars` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codi`        varchar(60) NOT NULL,
  `llibre_id`   int(10) unsigned NOT NULL,
  `estat`       enum('nou','bo','deteriorat','perdut') NOT NULL DEFAULT 'nou',
  `disponible`  tinyint(1) NOT NULL DEFAULT 1,
  `lot_id`      int(11) DEFAULT NULL,
  `alumne_id`   int(11) DEFAULT NULL,
  `desperfectes` text DEFAULT NULL,
  `notes`       text DEFAULT NULL,
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`  timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codi` (`codi`),
  KEY `llibre_id` (`llibre_id`),
  CONSTRAINT `exemplars_ibfk_1` FOREIGN KEY (`llibre_id`) REFERENCES `llibres` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- lots  (conjunt d'exemplars de matèries comunes per alumne)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lots` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codi`        varchar(40) NOT NULL,
  `alumne_id`   int(10) unsigned NOT NULL,
  `curs_id`     int(10) unsigned NOT NULL,
  `curs_escolar` varchar(9) NOT NULL DEFAULT '2025/2026',
  `creat_per`   int(10) unsigned DEFAULT NULL,
  `creat_at`    timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codi` (`codi`),
  KEY `alumne_id` (`alumne_id`),
  KEY `curs_id`   (`curs_id`),
  KEY `creat_per` (`creat_per`),
  CONSTRAINT `lots_ibfk_1` FOREIGN KEY (`alumne_id`) REFERENCES `alumnes` (`id`),
  CONSTRAINT `lots_ibfk_2` FOREIGN KEY (`curs_id`)   REFERENCES `cursos`  (`id`),
  CONSTRAINT `lots_ibfk_3` FOREIGN KEY (`creat_per`) REFERENCES `usuaris` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- prestecs
-- estat: actiu | retornat | perdut | pendent
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prestecs` (
  `id`                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alumne_id`            int(10) unsigned NOT NULL,
  `exemplar_id`          int(10) unsigned NOT NULL,
  `lot_id`               int(10) unsigned DEFAULT NULL,
  `estat`                enum('actiu','retornat','perdut','pendent') NOT NULL DEFAULT 'actiu',
  `estat_inicial`        enum('nou','bo','deteriorat') NOT NULL DEFAULT 'bo',
  `desperfectes_inicials` text DEFAULT NULL,
  `data_prestec`         timestamp NOT NULL DEFAULT current_timestamp(),
  `data_devolucio`       timestamp NULL DEFAULT NULL,
  `estat_final`          enum('nou','bo','deteriorat','perdut') DEFAULT NULL,
  `desperfectes_finals`  text DEFAULT NULL,
  `professor_id`         int(10) unsigned DEFAULT NULL,
  `curs_escolar`         varchar(9) NOT NULL DEFAULT '2025/2026',
  `estat_prestec`        varchar(20) NOT NULL DEFAULT 'pendent',
  PRIMARY KEY (`id`),
  KEY `alumne_id`   (`alumne_id`),
  KEY `exemplar_id` (`exemplar_id`),
  KEY `lot_id`      (`lot_id`),
  KEY `professor_id` (`professor_id`),
  CONSTRAINT `prestecs_ibfk_1` FOREIGN KEY (`alumne_id`)   REFERENCES `alumnes`  (`id`),
  CONSTRAINT `prestecs_ibfk_2` FOREIGN KEY (`exemplar_id`) REFERENCES `exemplars` (`id`),
  CONSTRAINT `prestecs_ibfk_3` FOREIGN KEY (`lot_id`)      REFERENCES `lots`      (`id`),
  CONSTRAINT `prestecs_ibfk_4` FOREIGN KEY (`professor_id`) REFERENCES `usuaris`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- incidencies
-- tipus: perdua | deteriorament_greu | extraviu | altre
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencies` (
  `id`              int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alumne_id`       int(10) unsigned NOT NULL,
  `exemplar_id`     int(10) unsigned NOT NULL,
  `prestec_id`      int(10) unsigned DEFAULT NULL,
  `tipus`           enum('perdua','deteriorament_greu','extraviu','altre') NOT NULL,
  `descripcio`      text NOT NULL,
  `ha_de_pagar`     tinyint(1) NOT NULL DEFAULT 0,
  `import_pagament` decimal(8,2) DEFAULT NULL,
  `pagat`           tinyint(1) NOT NULL DEFAULT 0,
  `data_pagament`   date DEFAULT NULL,
  `motiu_carrec`    text DEFAULT NULL,
  `registrat_per`   int(10) unsigned DEFAULT NULL,
  `data_incidencia` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `alumne_id`     (`alumne_id`),
  KEY `exemplar_id`   (`exemplar_id`),
  KEY `prestec_id`    (`prestec_id`),
  KEY `registrat_per` (`registrat_per`),
  CONSTRAINT `incidencies_ibfk_1` FOREIGN KEY (`alumne_id`)   REFERENCES `alumnes`  (`id`),
  CONSTRAINT `incidencies_ibfk_2` FOREIGN KEY (`exemplar_id`) REFERENCES `exemplars` (`id`),
  CONSTRAINT `incidencies_ibfk_3` FOREIGN KEY (`prestec_id`)  REFERENCES `prestecs`  (`id`),
  CONSTRAINT `incidencies_ibfk_4` FOREIGN KEY (`registrat_per`) REFERENCES `usuaris` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- historial  (log d'accions sobre alumnes i exemplars)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alumne_id`   int(10) unsigned DEFAULT NULL,
  `exemplar_id` int(10) unsigned DEFAULT NULL,
  `accio`       varchar(100) NOT NULL,
  `detalls`     text DEFAULT NULL,
  `usuari_id`   int(10) unsigned DEFAULT NULL,
  `creat_at`    timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `alumne_id`   (`alumne_id`),
  KEY `exemplar_id` (`exemplar_id`),
  KEY `usuari_id`   (`usuari_id`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`alumne_id`)   REFERENCES `alumnes`  (`id`),
  CONSTRAINT `historial_ibfk_2` FOREIGN KEY (`exemplar_id`) REFERENCES `exemplars` (`id`),
  CONSTRAINT `historial_ibfk_3` FOREIGN KEY (`usuari_id`)   REFERENCES `usuaris`   (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- albarans  (PDFs generats: préstec, devolució, incidència)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `albarans` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `alumne_id`  int(11) NOT NULL,
  `tipus`      enum('prestec','devolucio','incidencia') NOT NULL,
  `fitxer_pdf` varchar(255) NOT NULL,
  `data`       datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `alumne_id` (`alumne_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- enviaments_correu  (log d'enviaments de correu)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enviaments_correu` (
  `id`             int(11) NOT NULL AUTO_INCREMENT,
  `albara_id`      int(11) NOT NULL,
  `email_dest`     varchar(150) NOT NULL,
  `resultat`       enum('ok','error') NOT NULL DEFAULT 'error',
  `missatge_error` text DEFAULT NULL,
  `creat_at`       timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- documents  (taula auxiliar per a PDFs amb més metadades)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `documents` (
  `id`               int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipus`            enum('prestec','devolucio','incidencia') NOT NULL,
  `alumne_id`        int(10) unsigned NOT NULL,
  `lot_id`           int(10) unsigned DEFAULT NULL,
  `incidencia_id`    int(10) unsigned DEFAULT NULL,
  `fitxer_pdf`       varchar(255) DEFAULT NULL,
  `email_enviat`     tinyint(1)   NOT NULL DEFAULT 0,
  `email_data`       timestamp    NULL DEFAULT NULL,
  `email_destinatari` varchar(150) DEFAULT NULL,
  `creat_per`        int(10) unsigned DEFAULT NULL,
  `creat_at`         timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `alumne_id`     (`alumne_id`),
  KEY `lot_id`        (`lot_id`),
  KEY `incidencia_id` (`incidencia_id`),
  KEY `creat_per`     (`creat_per`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`alumne_id`)     REFERENCES `alumnes`    (`id`),
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`lot_id`)        REFERENCES `lots`       (`id`),
  CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`incidencia_id`) REFERENCES `incidencies` (`id`),
  CONSTRAINT `documents_ibfk_4` FOREIGN KEY (`creat_per`)     REFERENCES `usuaris`    (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

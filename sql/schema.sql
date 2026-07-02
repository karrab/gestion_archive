-- =====================================================================
-- Gestion Archive - Schema MySQL (InnoDB, UTF8MB4 pour le support arabe)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS gestion_archive
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_archive;

-- ---------------------------------------------------------------------
-- Tables de référence
-- ---------------------------------------------------------------------

CREATE TABLE service (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_service_nom (nom)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE employe (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matricule VARCHAR(50) NOT NULL,
  nom VARCHAR(100) NOT NULL,
  renom VARCHAR(100) NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  mail VARCHAR(150),
  tel1 VARCHAR(30),
  tel2 VARCHAR(30),
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_employe_matricule (matricule),
  KEY idx_employe_service (service_id),
  KEY idx_employe_nom (nom, renom),
  CONSTRAINT fk_employe_service FOREIGN KEY (service_id) REFERENCES service(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE depot (
  id_dept INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(50) NOT NULL,
  description VARCHAR(255),
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_depot_numero (numero)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE annee (
  id_annee INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annee SMALLINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_annee (annee)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE classification (
  id_cls INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_classification VARCHAR(50) NOT NULL,
  titre_classfication VARCHAR(255) NOT NULL,
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_classification_ref (ref_classification),
  KEY idx_classification_titre (titre_classfication)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE carac_ideologique (
  id_ideo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  notes TEXT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE carac_temporelle (
  id_tmp INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  notes TEXT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE carac_geographique (
  id_geo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  notes TEXT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE type_doc (
  id_typ INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  notes TEXT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE sort_fin_doc (
  id_sort INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  notes TEXT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE etat_archive (
  id_etat INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  etat VARCHAR(50) NOT NULL,
  notes TEXT,
  UNIQUE KEY uq_etat (etat)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO etat_archive (etat) VALUES ('Disponible'), ('Versé'), ('Transféré'), ('Eliminé');

CREATE TABLE institution (
  id_ins INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  notes TEXT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Sécurité : rôles, permissions, utilisateurs
-- ---------------------------------------------------------------------

CREATE TABLE role (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_role_nom (nom)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE permission (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(100) NOT NULL,
  libelle VARCHAR(150) NOT NULL,
  module VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_permission_code (code)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE role_permission (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permission(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE user (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  mail VARCHAR(150) NOT NULL,
  login VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_login (login),
  UNIQUE KEY uq_user_mail (mail),
  KEY idx_user_role (role_id),
  CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES role(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Archive
-- ---------------------------------------------------------------------

CREATE TABLE archive (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date_archive DATE NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  employe_id INT UNSIGNED NOT NULL,
  service_origin INT UNSIGNED,
  employe_origin INT UNSIGNED,
  titre_dossier VARCHAR(255) NOT NULL,
  num_boite VARCHAR(50),
  num_depot INT UNSIGNED NOT NULL,
  num_etagere VARCHAR(50),
  num_plaque VARCHAR(50),
  emplacement VARCHAR(150),
  annee_min SMALLINT UNSIGNED,
  annee_max SMALLINT UNSIGNED,
  ref_classification VARCHAR(50) NOT NULL,
  titre_classfication VARCHAR(255),
  carac_ideologique_id INT UNSIGNED,
  carac_temporelle_id INT UNSIGNED,
  carac_geographique_id INT UNSIGNED,
  type_doc_id INT UNSIGNED,
  duree_conser VARCHAR(50),
  sort_fin_doc_id INT UNSIGNED,
  etat_archive VARCHAR(50) NOT NULL DEFAULT 'Disponible',
  fichier VARCHAR(255),
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_archive_date_id (date_archive, id),
  KEY idx_archive_service (service_id),
  KEY idx_archive_employe (employe_id),
  KEY idx_archive_depot (num_depot),
  KEY idx_archive_classification (ref_classification),
  KEY idx_archive_etat (etat_archive),
  KEY idx_archive_titre (titre_dossier),
  FULLTEXT KEY ft_archive_recherche (titre_dossier, num_boite, emplacement, titre_classfication, notes),
  CONSTRAINT fk_archive_service FOREIGN KEY (service_id) REFERENCES service(id),
  CONSTRAINT fk_archive_employe FOREIGN KEY (employe_id) REFERENCES employe(id),
  CONSTRAINT fk_archive_service_origin FOREIGN KEY (service_origin) REFERENCES service(id),
  CONSTRAINT fk_archive_employe_origin FOREIGN KEY (employe_origin) REFERENCES employe(id),
  CONSTRAINT fk_archive_depot FOREIGN KEY (num_depot) REFERENCES depot(id_dept),
  CONSTRAINT fk_archive_ideo FOREIGN KEY (carac_ideologique_id) REFERENCES carac_ideologique(id_ideo),
  CONSTRAINT fk_archive_tmp FOREIGN KEY (carac_temporelle_id) REFERENCES carac_temporelle(id_tmp),
  CONSTRAINT fk_archive_geo FOREIGN KEY (carac_geographique_id) REFERENCES carac_geographique(id_geo),
  CONSTRAINT fk_archive_typ FOREIGN KEY (type_doc_id) REFERENCES type_doc(id_typ),
  CONSTRAINT fk_archive_sort FOREIGN KEY (sort_fin_doc_id) REFERENCES sort_fin_doc(id_sort)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Transfert (entête + lignes)
-- ---------------------------------------------------------------------

CREATE TABLE transfert (
  id_trans BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date_trans DATE NOT NULL,
  ref_trans VARCHAR(50) NOT NULL,
  num_bordereau VARCHAR(50) NOT NULL,
  nb_doc INT UNSIGNED DEFAULT 0,
  nb_boite INT UNSIGNED DEFAULT 0,
  metrage_lin DECIMAL(10,2) DEFAULT 0,
  service_dest INT UNSIGNED NOT NULL,
  employe_dest INT UNSIGNED NOT NULL,
  service_origin INT UNSIGNED NOT NULL,
  employe_origin INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_transfert_bordereau (num_bordereau),
  KEY idx_transfert_date_id (date_trans, id_trans),
  CONSTRAINT fk_trans_serv_dest FOREIGN KEY (service_dest) REFERENCES service(id),
  CONSTRAINT fk_trans_emp_dest FOREIGN KEY (employe_dest) REFERENCES employe(id),
  CONSTRAINT fk_trans_serv_orig FOREIGN KEY (service_origin) REFERENCES service(id),
  CONSTRAINT fk_trans_emp_orig FOREIGN KEY (employe_origin) REFERENCES employe(id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE ligne_transfert (
  id_ligtrans BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_trans BIGINT UNSIGNED NOT NULL,
  archive_id BIGINT UNSIGNED NULL,
  titre_dossier VARCHAR(255) NOT NULL,
  num_boite VARCHAR(50),
  num_depot INT UNSIGNED,
  num_etagere VARCHAR(50),
  num_plaque VARCHAR(50),
  emplacement VARCHAR(150),
  annee_min SMALLINT UNSIGNED,
  annee_max SMALLINT UNSIGNED,
  ref_classification VARCHAR(50),
  titre_classfication VARCHAR(255),
  carac_ideologique_id INT UNSIGNED,
  carac_temporelle_id INT UNSIGNED,
  carac_geographique_id INT UNSIGNED,
  type_doc_id INT UNSIGNED,
  duree_conser VARCHAR(50),
  sort_fin_doc_id INT UNSIGNED,
  etat_archive VARCHAR(50) NOT NULL DEFAULT 'Transféré',
  KEY idx_ligtrans_entete (id_trans),
  KEY idx_ligtrans_archive (archive_id),
  CONSTRAINT fk_ligtrans_entete FOREIGN KEY (id_trans) REFERENCES transfert(id_trans) ON DELETE CASCADE,
  CONSTRAINT fk_ligtrans_archive FOREIGN KEY (archive_id) REFERENCES archive(id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Versement (entête + lignes)
-- ---------------------------------------------------------------------

CREATE TABLE versement (
  id_vers BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date_vers DATE NOT NULL,
  ref_vers VARCHAR(50) NOT NULL,
  num_bordereau VARCHAR(50) NOT NULL,
  nb_doc INT UNSIGNED DEFAULT 0,
  nb_boite INT UNSIGNED DEFAULT 0,
  metrage_lin DECIMAL(10,2) DEFAULT 0,
  service INT UNSIGNED NOT NULL,
  employe INT UNSIGNED NOT NULL,
  institution INT UNSIGNED,
  responsable_reception VARCHAR(150),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_versement_bordereau (num_bordereau),
  KEY idx_versement_date_id (date_vers, id_vers),
  CONSTRAINT fk_vers_service FOREIGN KEY (service) REFERENCES service(id),
  CONSTRAINT fk_vers_employe FOREIGN KEY (employe) REFERENCES employe(id),
  CONSTRAINT fk_vers_institution FOREIGN KEY (institution) REFERENCES institution(id_ins)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE ligne_versement (
  id_ligvers BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_vers BIGINT UNSIGNED NOT NULL,
  archive_id BIGINT UNSIGNED NULL,
  titre_dossier VARCHAR(255) NOT NULL,
  num_boite VARCHAR(50),
  num_depot INT UNSIGNED,
  num_etagere VARCHAR(50),
  num_plaque VARCHAR(50),
  emplacement VARCHAR(150),
  annee_min SMALLINT UNSIGNED,
  annee_max SMALLINT UNSIGNED,
  ref_classification VARCHAR(50),
  titre_classfication VARCHAR(255),
  carac_ideologique_id INT UNSIGNED,
  carac_temporelle_id INT UNSIGNED,
  carac_geographique_id INT UNSIGNED,
  type_doc_id INT UNSIGNED,
  duree_conser VARCHAR(50),
  sort_fin_doc_id INT UNSIGNED,
  etat_archive VARCHAR(50) NOT NULL DEFAULT 'Versé',
  KEY idx_ligvers_entete (id_vers),
  KEY idx_ligvers_archive (archive_id),
  CONSTRAINT fk_ligvers_entete FOREIGN KEY (id_vers) REFERENCES versement(id_vers) ON DELETE CASCADE,
  CONSTRAINT fk_ligvers_archive FOREIGN KEY (archive_id) REFERENCES archive(id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Elimination (entête + lignes)
-- ---------------------------------------------------------------------

CREATE TABLE elimination (
  id_elm BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date_elm DATE NOT NULL,
  ref_elm VARCHAR(50) NOT NULL,
  numero_pv VARCHAR(50) NOT NULL,
  numero_visa VARCHAR(50),
  date_visa DATE,
  num_bordereau VARCHAR(50) NOT NULL,
  nb_doc INT UNSIGNED DEFAULT 0,
  nb_boite INT UNSIGNED DEFAULT 0,
  metrage_lin DECIMAL(10,2) DEFAULT 0,
  service INT UNSIGNED NOT NULL,
  employe INT UNSIGNED NOT NULL,
  institution INT UNSIGNED,
  responsable_reception VARCHAR(150),
  president_commission VARCHAR(100),
  membres_commission TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_elimination_pv (numero_pv),
  UNIQUE KEY uq_elimination_bordereau (num_bordereau),
  KEY idx_elimination_date_id (date_elm, id_elm),
  CONSTRAINT fk_elm_service FOREIGN KEY (service) REFERENCES service(id),
  CONSTRAINT fk_elm_employe FOREIGN KEY (employe) REFERENCES employe(id),
  CONSTRAINT fk_elm_institution FOREIGN KEY (institution) REFERENCES institution(id_ins)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE ligne_elimination (
  id_ligelm BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_elm BIGINT UNSIGNED NOT NULL,
  archive_id BIGINT UNSIGNED NULL,
  titre_dossier VARCHAR(255) NOT NULL,
  num_boite VARCHAR(50),
  num_depot INT UNSIGNED,
  num_etagere VARCHAR(50),
  num_plaque VARCHAR(50),
  emplacement VARCHAR(150),
  annee_min SMALLINT UNSIGNED,
  annee_max SMALLINT UNSIGNED,
  ref_classification VARCHAR(50),
  titre_classfication VARCHAR(255),
  carac_ideologique_id INT UNSIGNED,
  carac_temporelle_id INT UNSIGNED,
  carac_geographique_id INT UNSIGNED,
  type_doc_id INT UNSIGNED,
  duree_conser VARCHAR(50),
  sort_fin_doc_id INT UNSIGNED,
  etat_archive VARCHAR(50) NOT NULL DEFAULT 'Eliminé',
  KEY idx_ligelm_entete (id_elm),
  KEY idx_ligelm_archive (archive_id),
  CONSTRAINT fk_ligelm_entete FOREIGN KEY (id_elm) REFERENCES elimination(id_elm) ON DELETE CASCADE,
  CONSTRAINT fk_ligelm_archive FOREIGN KEY (archive_id) REFERENCES archive(id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Compteurs pour génération automatique PV/ et BR/
-- ---------------------------------------------------------------------

CREATE TABLE compteur_numero (
  cle VARCHAR(30) NOT NULL,
  annee SMALLINT UNSIGNED NOT NULL,
  dernier_numero INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (cle, annee)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Paramètres établissement / version
-- ---------------------------------------------------------------------

CREATE TABLE parametres (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom_etablissement VARCHAR(255) NOT NULL,
  logo VARCHAR(255),
  adresse VARCHAR(255),
  tel_fixe VARCHAR(30),
  tel_mobile VARCHAR(30),
  fax VARCHAR(30),
  email VARCHAR(150)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE version_app (
  id_ver INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  num_ver VARCHAR(30) NOT NULL,
  developper_par VARCHAR(150),
  direction VARCHAR(150),
  nouveaute TEXT,
  note TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Historique des opérations / traces
-- ---------------------------------------------------------------------

CREATE TABLE historique (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED,
  action VARCHAR(50) NOT NULL,
  module VARCHAR(100) NOT NULL,
  description TEXT,
  ip_address VARCHAR(45),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_historique_date (created_at),
  KEY idx_historique_user (user_id),
  KEY idx_historique_module (module),
  CONSTRAINT fk_historique_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Données de base : rôles, permissions, admin
-- ---------------------------------------------------------------------

INSERT INTO role (nom) VALUES ('Administrateur'), ('Gestionnaire'), ('Consultant');

INSERT INTO permission (code, libelle, module) VALUES
('archive.view','Consulter les archives','archive'),
('archive.add','Ajouter une archive','archive'),
('archive.edit','Modifier une archive','archive'),
('archive.delete','Supprimer une archive','archive'),
('archive.print','Imprimer/exporter les archives','archive'),
('transfert.view','Consulter les transferts','transfert'),
('transfert.add','Ajouter un transfert','transfert'),
('transfert.edit','Modifier un transfert','transfert'),
('transfert.delete','Supprimer un transfert','transfert'),
('transfert.print','Imprimer/exporter les transferts','transfert'),
('versement.view','Consulter les versements','versement'),
('versement.add','Ajouter un versement','versement'),
('versement.edit','Modifier un versement','versement'),
('versement.delete','Supprimer un versement','versement'),
('versement.print','Imprimer/exporter les versements','versement'),
('elimination.view','Consulter les éliminations','elimination'),
('elimination.add','Ajouter une élimination','elimination'),
('elimination.edit','Modifier une élimination','elimination'),
('elimination.delete','Supprimer une élimination','elimination'),
('elimination.print','Imprimer/exporter les éliminations','elimination'),
('reference.view','Consulter les tables de référence','reference'),
('reference.add','Ajouter dans les tables de référence','reference'),
('reference.edit','Modifier les tables de référence','reference'),
('reference.delete','Supprimer dans les tables de référence','reference'),
('reference.print','Imprimer/exporter les tables de référence','reference'),
('user.view','Consulter les utilisateurs','user'),
('user.add','Ajouter un utilisateur','user'),
('user.edit','Modifier un utilisateur','user'),
('user.delete','Supprimer un utilisateur','user'),
('user.print','Imprimer/exporter les utilisateurs','user'),
('role.manage','Gérer les rôles et permissions','role'),
('parametres.manage','Gérer les paramètres','parametres'),
('version.view','Consulter les versions','version'),
('version.add','Ajouter une version','version'),
('version.edit','Modifier une version','version'),
('version.delete','Supprimer une version','version'),
('version.print','Imprimer/exporter les versions','version'),
('historique.view','Consulter l\'historique','historique'),
('historique.print','Exporter l\'historique','historique');

INSERT INTO role_permission (role_id, permission_id)
SELECT 1, id FROM permission;

-- Mot de passe par défaut : Admin@123 (haché avec password_hash / bcrypt)
INSERT INTO user (nom, prenom, mail, login, password, role_id)
VALUES ('Admin', 'Système', 'admin@example.com', 'admin', '$2y$12$JOmgM6TtpTflJxdTwGhCy.TUg5Fa/V3tl4piU1d1C1R2..q/ZIk3i', 1);

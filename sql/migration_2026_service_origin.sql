-- ---------------------------------------------------------------------
-- Migration : ajout de service_origin / employe_origin à la table archive
-- À exécuter UNE SEULE FOIS sur une base de données existante (déjà créée
-- avant l'ajout de ces colonnes). Les installations neuves via schema.sql
-- possèdent déjà ces colonnes : ne pas rejouer cette migration sur elles.
--
-- Usage :
--   mysql -u root -p gestion_archive < sql/migration_2026_service_origin.sql
-- ---------------------------------------------------------------------

ALTER TABLE archive
  ADD COLUMN service_origin INT UNSIGNED NULL AFTER employe_id,
  ADD COLUMN employe_origin INT UNSIGNED NULL AFTER service_origin;

ALTER TABLE archive
  ADD CONSTRAINT fk_archive_service_origin FOREIGN KEY (service_origin) REFERENCES service(id),
  ADD CONSTRAINT fk_archive_employe_origin FOREIGN KEY (employe_origin) REFERENCES employe(id);

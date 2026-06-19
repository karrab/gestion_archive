# Gestion Archive

Application de gestion commerciale d'archives (PHP + Bootstrap 5 + MySQL/InnoDB).

## Installation

1. Importer `sql/schema.sql` dans MySQL (`mysql -u root -p < sql/schema.sql`).
2. Copier `config/opcache.ini` dans votre configuration PHP (php.ini ou php.d/) pour activer OPcache + APCu.
3. Définir les variables d'environnement `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (ou éditer `config/config.php`).
4. Installer les dépendances PDF : `composer install` (Dompdf).
5. Télécharger localement et placer dans `assets/` : Bootstrap 5 CSS/JS, Bootstrap Icons, jQuery, Select2 (aucun lien CDN n'est utilisé dans le code).
6. Placer le logo de l'établissement dans `images/logo.png` et renseigner les paramètres via le module Paramètres.
7. Compte par défaut : `admin` / `Admin@123` (à changer après la première connexion).

## Statut d'avancement

Fondations livrées dans cette itération :
- Schéma MySQL complet (InnoDB, UTF8MB4, index composites date+id pour pagination keyset, FULLTEXT sur archive).
- Connexion PDO, cache APCu, configuration OPcache.
- Authentification, permissions par rôle, CSRF, journal d'historique.
- Layout Bootstrap (navbar RTL, recherche globale, menu Export, logo+établissement).
- Génération de numéros séquentiels PV/BR par année.
- Export PDF (Dompdf) avec en-tête établissement, module `service` et `historique` en exemple.
- CRUD complet de référence (`service`) servant de modèle pour les autres tables de référence (depot, annee, classification, carac_*, type_doc, sort_fin_doc, etat_archive, institution).

Reste à construire selon ce même modèle : CRUD Archive (upload fichier, recherche globale AJAX, sélecteurs en cascade), Transfert/Versement/Elimination (entête+lignes, génération PV/BR, impression PDF arabe), modules Employe/User/Role/Permissions/Parametres/Version/Profil/Historique, pagination keyset sur les grids volumineux, et Select2 sur les sélecteurs d'archives.

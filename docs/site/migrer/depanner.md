# Dépanner une migration

Lire le premier échec avant de relancer. Les traitements dépendent les uns des autres ; une destination partiellement écrite ne constitue pas une nouvelle base vierge.

| Symptôme | Diagnostic et action |
|---|---|
| Commande `wordpress:importer` absente | Vérifier racine SPIP, exécutable CLI et activation de wp2spip et de ses dépendances |
| `wp-includes/version.php` introuvable | Fournir le dossier d’installation WordPress, pas uniquement uploads ou un export XML |
| Préfixe introuvable | Vérifier `wp-config.php`, puis fournir `--prefixe` si sa valeur est calculée ou ambiguë |
| Tables WordPress absentes | Contrôler connexion externe, base et préfixe ; ne pas essayer arbitrairement `wp_` |
| Préfixe différent d’un import commencé | Repartir d’une destination vierge ; le moteur refuse de mélanger les sources |
| Téléchargement/activation d’un plugin impossible | Lire les commandes affichées, vérifier accès réseau, correctif SVP et droits des dossiers |
| Identifiant déjà pris | Destination non vierge ou conflit ; sauvegarder puis refaire une destination de test propre |
| Traitement inconnu | Comparer à `--info` ; une spec n’ajoute pas son traitement à ce checkout |
| Média manquant/refusé | Contrôler fichier source, URL, autorisations et type de fichier ; utiliser `-v` |
| Lien conservé vers WordPress | Examiner média hors médiathèque, URL de fond ou slug ambigu ; préparer une correction ciblée |
| Conversion d’un bloc incomplète | Identifier le bloc et son stockage ; un bloc dynamique vide ne fournit pas de HTML à importer |

## Après interruption ou échec

Conserver les journaux sans divulguer les accès de connexion. Identifier les traitements exécutés et les objets créés. Remettre la **destination** à zéro ou préparer une nouvelle destination jetable, puis relancer l’import complet depuis la même source figée.

Ne pas utiliser un script de remise à zéro sur une installation réelle sans vérifier ses chemins et sa base. La source WordPress et ses sauvegardes restent indépendantes de la destination.

## Erreur d’environnement ou défaut du moteur ?

Une extension PHP absente, une connexion refusée ou un plugin non actif est un prérequis à corriger. Une relation erronée malgré les prérequis corrects peut être un défaut du moteur : consigner version exacte, commit, scénario minimal et sortie, sans modifier les références de tests pour cacher le problème.

[Tests](../installer/developpement.md) · [État du projet](../etat-projet.md) · [Commande et erreurs](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/spip-cli/WordpressImporter.php).

# Auteurs et accès

**État : implémenté**, avec une transformation des droits. L’identité éditoriale et l’autorisation de lire ou de modifier sont deux sujets distincts.

## Utilisateurs → auteurs

Les données de `users` et certaines valeurs de `usermeta` donnent login, email, nom et site web. Le nom privilégie prénom + nom, puis nom affiché, nicename ou login. Les auteurs gardent la numérotation SPIP ; `id_wordpress` permet de rattacher leurs articles.

| Rôle WordPress | Statut SPIP |
|---|---|
| `administrator` | Administrateur et webmestre |
| `editor` | Administrateur |
| `author`, `contributor` | Rédacteur |
| `subscriber` et autre | Visiteur |

Les capacités sont lues dans `<préfixe>capabilities`. Les rôles personnalisés et combinaisons de capacités ne sont pas reproduits comme un système d’autorisation WordPress complet.

## Mots de passe et conflits de login

Les mots de passe WordPress ne sont pas repris. Un mot de passe aléatoire est attribué à la création ; prévoir le parcours « mot de passe oublié » et vérifier les emails. Un login refusé par SPIP est remplacé par le premier libre parmi `login-wp`, `login-wp2`… Le bilan indique le login retenu.

## Privé et protégé → zones

| Source WordPress | Destination |
|---|---|
| Contenu privé | Zone « WordPress : contenus privés » |
| Contenu publié/programmé protégé par mot de passe | Zone « WordPress : contenus protégés par mot de passe » |

Le moteur crée ces zones avec Accès restreint et les réserve aux visiteurs identifiés. Il associe le contenu à la zone **avant** de le publier. Les mots de passe propres aux contenus ne sont pas repris.

!!! warning "La règle d’accès change"
    « Visiteur identifié » ne signifie pas « éditeur WordPress » ni « personne connaissant le mot de passe de cette page ». Restreindre les zones aux personnes réellement autorisées avant d’ouvrir le site. La migration de l’état privé ne garantit pas l’équivalence des droits.

## Contrôler

Tester avec un visiteur anonyme, un compte identifié non autorisé et un compte autorisé. Vérifier les rôles, la récupération des comptes et les fichiers associés aux contenus protégés. Garder la destination hors ligne jusqu’à validation de cette politique.

Sources : [auteurs](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_auteurs.php), [accès](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_acces.php).

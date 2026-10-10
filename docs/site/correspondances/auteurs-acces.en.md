# Authors and access

WordPress permissions are transformed into SPIP statuses. Editorial identity and authorization to read or modify are two distinct subjects.

## Users → authors

Data from `users` and certain `usermeta` values provide login, email, name, and website. The name prioritizes first name + last name, then display name, nicename, or login. Authors retain SPIP numbering; `id_wordpress` is used to link their articles.

| WordPress Role | SPIP Status |
|---|---|
| `administrator` | Administrator and webmaster |
| `editor` | Administrator |
| `author`, `contributor` | Author (Rédacteur) |
| `subscriber` and other | Visitor |

Capabilities are read from `<prefix>capabilities`. Custom roles and combinations of capabilities are not reproduced as a complete WordPress authorization system.

## Passwords and login conflicts

WordPress passwords are not imported. A random password is assigned upon creation; plan for the "forgotten password" process and verify emails. A login rejected by SPIP is replaced by the first available one among `login-wp`, `login-wp2`… The summary report indicates the chosen login.

## Private and protected → zones

| WordPress Source | Destination |
|---|---|
| Private content | Zone "WordPress: private content" |
| Password-protected published/scheduled content | Zone "WordPress: password-protected content" |

The engine creates these zones using Accès restreint and reserves them for identified visitors. It associates the content with the zone **before** publishing it. Passwords specific to individual content items are not imported.

!!! warning "Access rules change"
    "Identified visitor" does not mean "WordPress editor" or "person who knows the password for this page." Restrict zones to authorized individuals before opening the site. Migrating the private status does not guarantee equivalence of rights.

## Testing access before going live

Test with an anonymous visitor, an identified unauthorized account, and an authorized account. Verify roles, account recovery, and files associated with protected content. Keep the destination offline until this policy is validated.

Sources: [authors](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_auteurs.php), [access](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_acces.php).

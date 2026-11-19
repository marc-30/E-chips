# e-CHIPS — site web motion

Site statique, sans build :

- `index.html` — page d'accueil : sachet 3D (three.js), animations au scroll (GSAP ScrollTrigger + Lenis), dos du paquet détaillé, responsive mobile → TV.
- `commander.html` — page Commander : sachets éparpillés et déplaçables, fiche produit, panier, commande par WhatsApp ou téléphone.

Les librairies sont chargées depuis les CDN (cdnjs, jsDelivr) ; textures, logo et visuels des sachets sont intégrés dans les pages.

## Lancer en local
Une connexion internet est nécessaire (librairies chargées depuis les CDN).

- macOS / Linux : `sh lancer.sh` → http://localhost:5173
- Windows : double-clic sur `lancer.bat`
- Ou à la main, depuis ce dossier : `python3 -m http.server 5173` ou `npx serve .`

N'ouvre pas `index.html` en double-clic (file://) : certains navigateurs bloquent alors les modules JS.

## Déploiement
Site statique : sur Vercel, importer le dépôt sans framework (Framework Preset : « Other »), aucune commande de build, dossier de sortie : racine.

## Commande et paiement Paystack

Parcours : panier → « Passer la commande » → nom, numéro WhatsApp, lieu de livraison (pré-rempli par la géolocalisation, modifiable) → paiement Paystack → page `merci.html`.

- Le navigateur n'envoie **jamais de prix** : les prix sont dans `api/config.example.php` (`PRODUCTS`) et recalculés côté serveur.
- Les commandes sont enregistrées dans `data/orders/*.json` (protégé par `.htaccess`) et un email est envoyé à `ORDER_EMAIL` quand elles sont payées.
- Géolocalisation : `geo.js` demande la position à l'arrivée sur le site (le navigateur affiche son autorisation), la convertit en adresse (OpenStreetMap) et la propose dans le formulaire.

### Test en local
`sh lancer.sh` démarre `php -S` (si PHP est installé) ; crée `api/config.local.php` avec `<?php return ['DEMO_MODE'=>true];` pour simuler le paiement sans Paystack.

### Mise en ligne sur LWS (hébergement PHP, HTTPS obligatoire)
1. Envoie tout le dossier par FTP / gestionnaire de fichiers dans `public_html/`.
2. Copie `api/config.example.php` en `api/config.local.php` et renseigne :
   - `PAYSTACK_SECRET_KEY` (clé secrète `sk_test_…` puis `sk_live_…`),
   - `SITE_URL` (ex. `https://www.echipsafrica.com`),
   - `DEMO_MODE` => `false`.
3. Dans Paystack (Settings → API Keys & Webhooks) : URL de webhook = `https://TON-DOMAINE/api/webhook.php`.
4. Vérifie dans Paystack le multiplicateur du montant pour XOF (`AMOUNT_MULTIPLIER`, 100 par défaut) avec un paiement test.
5. Active le certificat SSL dans l'espace LWS (la géolocalisation et Paystack l'exigent). Le dossier `data/` doit être inscriptible (chmod 750/755).

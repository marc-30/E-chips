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

<?php
// Copie ce fichier en api/config.local.php et remplis les valeurs. config.local.php n'est JAMAIS commité (voir .gitignore).
// Tu peux aussi définir ces valeurs en variables d'environnement (PAYSTACK_SECRET_KEY, SITE_URL, ...).
return [
  // Clé SECRÈTE Paystack (sk_test_... pour tester, sk_live_... en production). Ne jamais la mettre côté navigateur.
  'PAYSTACK_SECRET_KEY' => '',

  // URL publique du site, sans slash final. Ex : https://www.echipsafrica.com
  'SITE_URL' => 'https://www.echipsafrica.com',

  'CURRENCY' => 'XOF',
  // Paystack attend le montant dans la plus petite unité (x100). À vérifier dans ton tableau de bord Paystack pour XOF.
  'AMOUNT_MULTIPLIER' => 100,

  // Frais de livraison en FCFA (0 = gratuit / à confirmer par téléphone)
  'DELIVERY_FEE' => 0,

  // Prix par lot en FCFA — la source de vérité est ICI (le navigateur n'envoie jamais de prix).
  'PRODUCTS' => [
    'banane-mure'  => ['name' => 'Chips de banane mûre',     'price' => 2500],
    'banane-verte' => ['name' => 'Chips de banane non mûre', 'price' => 2500],
    'manioc'       => ['name' => 'Chips de manioc',          'price' => 2500],
    'patate'       => ['name' => 'Chips de patate douce',    'price' => 2500],
    'mangues'      => ['name' => 'Mangues séchées',          'price' => 2500],
    'kilichi'      => ['name' => 'Kilichi',                  'price' => 2500],
  ],

  // Email qui reçoit chaque commande payée (laisser vide pour désactiver)
  'ORDER_EMAIL' => 'echipsafrica@gmail.com',
  // Domaine utilisé pour fabriquer un email technique (Paystack exige un email, le formulaire n'en demande pas)
  'FALLBACK_EMAIL_DOMAIN' => 'echipsafrica.com',

  // true = simule le paiement sans appeler Paystack (tests locaux uniquement !). Doit être false en production.
  'DEMO_MODE' => false,
];

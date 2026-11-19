<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['ok' => false, 'error' => 'Méthode non autorisée.'], 405);
$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) out(['ok' => false, 'error' => 'Requête invalide.'], 400);
if (!empty($in['website'])) out(['ok' => false, 'error' => 'Requête refusée.'], 400); // honeypot anti-bots

$name = trim((string)($in['name'] ?? ''));
$wa = preg_replace('/[^\d+]/', '', (string)($in['whatsapp'] ?? ''));
$addr = trim((string)($in['address'] ?? ''));
$lat = isset($in['lat']) && is_numeric($in['lat']) ? round((float)$in['lat'], 6) : null;
$lng = isset($in['lng']) && is_numeric($in['lng']) ? round((float)$in['lng'], 6) : null;

if (mb_strlen($name) < 2 || mb_strlen($name) > 80) out(['ok' => false, 'error' => 'Indique ton nom.', 'field' => 'name'], 422);
$digits = ltrim($wa, '+');
if (!preg_match('/^\d{8,15}$/', $digits)) out(['ok' => false, 'error' => 'Numéro WhatsApp invalide.', 'field' => 'whatsapp'], 422);
if (strlen($digits) === 10) $digits = '225' . $digits;        // numéro ivoirien à 10 chiffres sans indicatif
elseif (strlen($digits) === 8) $digits = '225' . $digits;
if (mb_strlen($addr) < 3 || mb_strlen($addr) > 300) out(['ok' => false, 'error' => 'Indique ton lieu de livraison.', 'field' => 'address'], 422);

$catalog = cfg('PRODUCTS', []);
$items = []; $sub = 0;
foreach ((array)($in['items'] ?? []) as $id => $qty) {
  $qty = (int)$qty;
  if (!isset($catalog[$id]) || $qty < 1 || $qty > 50) continue;
  $items[] = ['id' => $id, 'name' => $catalog[$id]['name'], 'price' => (int)$catalog[$id]['price'], 'qty' => $qty];
  $sub += $qty * (int)$catalog[$id]['price'];
}
if (!$items) out(['ok' => false, 'error' => 'Ton panier est vide.'], 422);
$total = $sub + (int)cfg('DELIVERY_FEE', 0);

$ref = 'ECH-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$order = [
  'reference' => $ref, 'status' => 'pending', 'created_at' => date('c'),
  'name' => $name, 'whatsapp' => '+' . $digits, 'address' => $addr, 'lat' => $lat, 'lng' => $lng,
  'items' => $items, 'subtotal' => $sub, 'delivery' => (int)cfg('DELIVERY_FEE', 0), 'total' => $total,
];
order_save($order);

$site = rtrim((string)cfg('SITE_URL', ''), '/');
if ($site === '') $site = (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$root = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$callback = $site . $root . '/merci.html';

if (cfg('DEMO_MODE', false)) {
  out(['ok' => true, 'reference' => $ref, 'url' => 'merci.html?reference=' . $ref . '&demo=1']);
}

$res = paystack('POST', '/transaction/initialize', [
  'email' => 'client' . $digits . '@' . cfg('FALLBACK_EMAIL_DOMAIN', 'echipsafrica.com'),
  'amount' => $total * (int)cfg('AMOUNT_MULTIPLIER', 100),
  'currency' => cfg('CURRENCY', 'XOF'),
  'reference' => $ref,
  'callback_url' => $callback,
  'metadata' => ['name' => $name, 'whatsapp' => '+' . $digits, 'address' => $addr, 'lat' => $lat, 'lng' => $lng,
                 'items' => array_map(fn($i) => $i['qty'] . ' x ' . $i['name'], $items)],
]);
if (empty($res['status']) || empty($res['data']['authorization_url'])) {
  out(['ok' => false, 'error' => 'Paystack : ' . ($res['message'] ?? 'initialisation impossible')], 502);
}
out(['ok' => true, 'reference' => $ref, 'url' => $res['data']['authorization_url']]);

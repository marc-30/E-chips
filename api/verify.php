<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

$ref = (string)($_GET['reference'] ?? '');
$order = $ref !== '' ? order_load($ref) : null;
if (!$order) out(['ok' => false, 'error' => 'Commande introuvable.'], 404);

if (($order['status'] ?? '') !== 'paid') {
  if (cfg('DEMO_MODE', false) && ($_GET['demo'] ?? '') === '1') {
    $order = mark_paid($order, ['channel' => 'demo']);
  } else {
    $res = paystack('GET', '/transaction/verify/' . rawurlencode($ref));
    $tx = $res['data'] ?? [];
    $expected = $order['total'] * (int)cfg('AMOUNT_MULTIPLIER', 100);
    if (($tx['status'] ?? '') === 'success' && (int)($tx['amount'] ?? 0) === $expected) {
      $order = mark_paid($order, $tx);
    }
  }
}
out([
  'ok' => true, 'status' => $order['status'], 'reference' => $order['reference'], 'name' => $order['name'],
  'address' => $order['address'], 'items' => $order['items'], 'total' => $order['total'],
]);

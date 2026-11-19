<?php
declare(strict_types=1);
// URL à renseigner dans Paystack > Settings > API Keys & Webhooks : https://TON-DOMAINE/api/webhook.php
require __DIR__ . '/lib.php';

$raw = (string)file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$key = (string)cfg('PAYSTACK_SECRET_KEY', '');
if ($key === '' || !hash_equals(hash_hmac('sha512', $raw, $key), $sig)) { http_response_code(401); exit; }

$ev = json_decode($raw, true);
if (($ev['event'] ?? '') === 'charge.success') {
  $tx = $ev['data'] ?? [];
  $order = order_load((string)($tx['reference'] ?? ''));
  if ($order && (int)($tx['amount'] ?? 0) === $order['total'] * (int)cfg('AMOUNT_MULTIPLIER', 100)) mark_paid($order, $tx);
}
http_response_code(200);
echo 'ok';

<?php
declare(strict_types=1);

function cfg(string $k, $default = null) {
  static $c = null;
  if ($c === null) {
    $c = [];
    $ex = __DIR__ . '/config.example.php';
    $loc = __DIR__ . '/config.local.php';
    if (is_file($ex)) $c = require $ex;
    if (is_file($loc)) $c = array_replace($c, require $loc);
    foreach (['PAYSTACK_SECRET_KEY','SITE_URL','DELIVERY_FEE','ORDER_EMAIL','DEMO_MODE','AMOUNT_MULTIPLIER','CURRENCY'] as $e) {
      $v = getenv($e);
      if ($v !== false && $v !== '') $c[$e] = in_array($e, ['DELIVERY_FEE','AMOUNT_MULTIPLIER'], true) ? (int)$v : ($e === 'DEMO_MODE' ? filter_var($v, FILTER_VALIDATE_BOOLEAN) : $v);
    }
  }
  return $c[$k] ?? $default;
}

function out(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function orders_dir(): string {
  $d = dirname(__DIR__) . '/data/orders';
  if (!is_dir($d)) @mkdir($d, 0750, true);
  return $d;
}
function order_path(string $ref): string { return orders_dir() . '/' . preg_replace('/[^A-Za-z0-9_-]/', '', $ref) . '.json'; }
function order_load(string $ref): ?array {
  $p = order_path($ref);
  return is_file($p) ? (json_decode((string)file_get_contents($p), true) ?: null) : null;
}
function order_save(array $o): void { file_put_contents(order_path($o['reference']), json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX); }

function paystack(string $method, string $path, ?array $body = null): array {
  $key = (string)cfg('PAYSTACK_SECRET_KEY', '');
  if ($key === '') out(['ok' => false, 'error' => 'Paiement non configuré (clé Paystack manquante).'], 500);
  $ch = curl_init('https://api.paystack.co' . $path);
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
  ]);
  if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
  $res = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);
  if ($res === false) out(['ok' => false, 'error' => 'Paystack injoignable : ' . $err], 502);
  return json_decode($res, true) ?: [];
}

function order_summary_text(array $o): string {
  $l = [];
  foreach ($o['items'] as $it) $l[] = sprintf('- %d x %s = %s FCFA', $it['qty'], $it['name'], number_format($it['qty'] * $it['price'], 0, ',', ' '));
  return implode("\n", $l);
}

function mark_paid(array $o, array $tx = []): array {
  if (($o['status'] ?? '') === 'paid') return $o;
  $o['status'] = 'paid';
  $o['paid_at'] = date('c');
  $o['channel'] = $tx['channel'] ?? null;
  order_save($o);
  $to = (string)cfg('ORDER_EMAIL', '');
  if ($to !== '') {
    $body = "Nouvelle commande payée {$o['reference']}\n\nClient : {$o['name']}\nWhatsApp : {$o['whatsapp']}\nLivraison : {$o['address']}\n"
      . (!empty($o['lat']) ? "Position : https://www.google.com/maps?q={$o['lat']},{$o['lng']}\n" : '')
      . "\n" . order_summary_text($o) . "\n\nTotal : " . number_format($o['total'], 0, ',', ' ') . " FCFA\n";
    @mail($to, "Commande e-CHIPS {$o['reference']} - " . number_format($o['total'], 0, ',', ' ') . ' FCFA', $body, "Content-Type: text/plain; charset=utf-8\r\nFrom: commandes@" . (cfg('FALLBACK_EMAIL_DOMAIN', 'localhost')));
  }
  return $o;
}

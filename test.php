<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(3);
if (!$user) { echo "User 3 not found\n"; exit; }
$orders = $user->orders()->with('items.product')->get();
echo "Orders logic executed. Count: " . count($orders) . "\n";

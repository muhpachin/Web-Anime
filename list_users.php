<?php

/**
 * List All Users Script
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   ALL USERS                                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$users = User::all();

if ($users->isEmpty()) {
    echo "❌ No users found!\n\n";
    exit(0);
}

foreach ($users as $user) {
    $adminBadge = $user->is_admin ? "✅ ADMIN" : "👤 User";
    echo "$adminBadge | {$user->name} | {$user->email}\n";
}

echo "\n";

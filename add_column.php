<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::table('leaves', function (Blueprint $table) {
    if (!Schema::hasColumn('leaves', 'is_special_approval')) {
        $table->boolean('is_special_approval')->default(false)->after('status');
    }
});

echo "Column added successfully.\n";

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('trader_promos', 'is_system_promo')) {
            return;
        }

        Schema::table('trader_promos', function (Blueprint $table) {
            $table->boolean('is_system_promo')->default(0);
        });
    }

    public function down(): void
    {

    }
};


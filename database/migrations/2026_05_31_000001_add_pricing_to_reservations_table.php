<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->json('consumables')->nullable()->after('end_time');
            $table->unsignedInteger('rental_fee')->default(0)->after('consumables');
            $table->unsignedInteger('consumables_fee')->default(0)->after('rental_fee');
            $table->unsignedInteger('tax_amount')->default(0)->after('consumables_fee');
            $table->unsignedInteger('total_amount')->default(0)->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'consumables',
                'rental_fee',
                'consumables_fee',
                'tax_amount',
                'total_amount',
            ]);
        });
    }
};

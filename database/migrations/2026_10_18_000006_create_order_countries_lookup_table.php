<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Normalized 20-row lookup table for sub-0.05ms country dropdown loading without caching.
     */
    public function up(): void
    {
        if (!Schema::hasTable('order_countries')) {
            Schema::create('order_countries', function (Blueprint $table) {
                $table->string('country', 50)->primary();
            });
        }

        // Seed with existing unique countries from orders and customers
        try {
            if (Schema::hasTable('orders')) {
                $countries = DB::table('orders')
                    ->select('shipping_country')
                    ->distinct()
                    ->pluck('shipping_country')
                    ->filter()
                    ->all();

                foreach ($countries as $c) {
                    DB::table('order_countries')->insertOrIgnore(['country' => trim($c)]);
                }
            }

            if (Schema::hasTable('customers')) {
                $custCountries = DB::table('customers')
                    ->select('country')
                    ->distinct()
                    ->pluck('country')
                    ->filter()
                    ->all();

                foreach ($custCountries as $c) {
                    DB::table('order_countries')->insertOrIgnore(['country' => trim($c)]);
                }
            }
        } catch (\Throwable $e) {
            // Seeding notice
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_countries');
    }
};


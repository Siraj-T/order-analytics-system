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
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->string('full_name', 120);
                $table->string('email', 180)->unique('uq_email');
                $table->string('country', 50);
                $table->dateTime('signup_date')->index('idx_signup_date');
                $table->boolean('is_active')->default(true);

                // Optimization index for customer search & country pagination
                $table->index(['country', 'id'], 'idx_customers_country_id');
                $table->index('full_name', 'idx_full_name');

                if (DB::getDriverName() === 'mysql') {
                    $table->fullText(['full_name', 'email'], 'ft_customers_name_email');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

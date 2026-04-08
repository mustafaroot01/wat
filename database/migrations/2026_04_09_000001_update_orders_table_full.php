<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('invoice_code', 20)->unique()->after('id');
            $table->string('invoice_token', 64)->unique()->after('invoice_code');
            $table->string('customer_name')->after('user_id');
            $table->string('customer_phone', 20)->after('customer_name');
            $table->string('province')->after('customer_phone');
            $table->string('district')->after('province');
            $table->string('nearest_landmark')->nullable()->after('district');
            $table->string('rejection_reason')->nullable()->after('notes');
        });

        // Update status default
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('sent')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_code', 'invoice_token',
                'customer_name', 'customer_phone',
                'province', 'district', 'nearest_landmark',
                'rejection_reason',
            ]);
        });
    }
};

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('gender')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('gender');
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete()->after('birth_date');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete()->after('district_id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            
            $table->dropForeign(['district_id']);
            $table->dropForeign(['area_id']);
            
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'gender',
                'birth_date',
                'district_id',
                'area_id',
                'is_active',
                'deleted_at'
            ]);
        });
    }
};

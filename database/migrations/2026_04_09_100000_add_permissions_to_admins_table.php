<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('is_active');
            $table->json('permissions')->nullable()->after('is_super_admin');
        });

        // المشرف الأول يكون سوبر أدمن تلقائياً
        DB::table('admins')->where('id', 1)->update(['is_super_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin', 'permissions']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // نقل بيانات المدراء من جدول users إلى admins
        DB::table('users')->where('is_admin', true)->orderBy('id')->each(function ($user) {
            DB::table('admins')->insert([
                'name'       => $user->name ?? explode('@', $user->email)[0],
                'email'      => $user->email,
                'password'   => $user->password,
                'is_active'  => $user->is_active ?? true,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        });

        // حذف سجلات المدراء من جدول users
        DB::table('users')->where('is_admin', true)->delete();

        // حذف عمود is_admin من users لأنه لم يعد ضرورياً
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('is_active');
        });

        DB::table('admins')->each(function ($admin) {
            DB::table('users')->insert([
                'name'       => $admin->name,
                'email'      => $admin->email,
                'password'   => $admin->password,
                'is_active'  => $admin->is_active,
                'is_admin'   => true,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]);
        });

        Schema::dropIfExists('admins');
    }
};

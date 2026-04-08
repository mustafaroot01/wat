<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert defaults
        DB::table('store_settings')->insert([
            ['key' => 'store_name',         'value' => 'متجري',              'created_at' => now(), 'updated_at' => now()],
            ['key' => 'store_phone',        'value' => '',                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'store_address',      'value' => '',                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'thank_you_message',  'value' => 'شكراً لثقتكم بنا',   'created_at' => now(), 'updated_at' => now()],
            ['key' => 'logo',               'value' => null,                  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};

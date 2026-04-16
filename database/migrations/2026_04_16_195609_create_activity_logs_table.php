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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->string('action'); // created, updated, deleted, toggled, etc.
            $table->string('model_type'); // Product, Order, Category, etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('description')->nullable(); // Human-readable Arabic description
            $table->json('old_values')->nullable(); // For updates - old data
            $table->json('new_values')->nullable(); // For updates - new data
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            $table->timestamps();
            
            $table->index(['admin_id', 'created_at']);
            $table->index('model_type');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

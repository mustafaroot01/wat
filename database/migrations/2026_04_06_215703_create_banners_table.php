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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'none', 'link', 'category', 'product'
            $table->string('url')->nullable();
            // We'll leave category/product as nullable unsigned big ints for now. 
            // In a real foreign key relationship we'd add constrained() but I'll skip cascading to be safe.
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('duration_type')->nullable(); // 'hours', 'days'
            $table->integer('duration_value')->nullable();
            $table->string('image');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};

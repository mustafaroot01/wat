<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->string('featured_title')->nullable()->after('is_featured');
            $table->string('featured_subtitle')->nullable()->after('featured_title');
            $table->string('featured_image')->nullable()->after('featured_subtitle'); // صورة مختلفة للعرض المميز
            $table->string('featured_bg_color', 20)->default('#1565c0')->after('featured_image');
            $table->enum('featured_display_style', ['full_banner', 'card', 'circle'])->default('full_banner')->after('featured_bg_color');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'is_featured', 'featured_title', 'featured_subtitle',
                'featured_image', 'featured_bg_color', 'featured_display_style',
            ]);
        });
    }
};

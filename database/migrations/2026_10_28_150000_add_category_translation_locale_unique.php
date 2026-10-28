<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_translations', function (Blueprint $table) {
            $table->unique(['category_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('category_translations', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'locale']);
        });
    }
};

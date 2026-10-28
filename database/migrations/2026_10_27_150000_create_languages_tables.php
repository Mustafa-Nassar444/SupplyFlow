<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->enum('direction', ['rtl', 'ltr']);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
            $table->index(['sort_order', 'code']);
        });
        // One non-null FK replaces competing boolean default flags.
        Schema::create('language_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedTinyInteger('singleton_key')->virtualAs('1')->unique();
            $table->foreignId('default_language_id')->constrained('languages')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_settings');
        Schema::dropIfExists('languages');
    }
};

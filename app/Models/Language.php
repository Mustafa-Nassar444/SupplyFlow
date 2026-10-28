<?php

namespace App\Models;

use App\Services\Languages\LanguageCatalog;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['name', 'code', 'direction', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(LanguageCatalog::class)->forget());
        static::deleted(fn () => app(LanguageCatalog::class)->forget());
    }
}

<?php

namespace App\Models;

use Database\Factories\HeroImageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroImage extends Model
{
    /** @use HasFactory<HeroImageFactory> */
    use HasFactory;

    protected $fillable = [
        'image_path',
        'headline',
        'subheading',
        'cta_label',
        'cta_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Only one row may be active at a time. Enforced here rather than left to
     * admin discipline, so it holds for the Filament form, the table toggle,
     * the seeder and tinker alike. The query-builder update fires no model
     * events, so there is no recursion back into this hook.
     */
    protected static function booted(): void
    {
        static::saved(function (HeroImage $hero): void {
            if ($hero->is_active) {
                static::query()
                    ->whereKeyNot($hero->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    /** @param  Builder<HeroImage>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

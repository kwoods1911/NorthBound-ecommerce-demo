<?php

namespace App\Services;

use App\Models\HeroImage;
use Illuminate\Support\Facades\Storage;

class HeroImageService
{
    /**
     * Sensible static hero used when no row is active, so the homepage never
     * renders an empty hero.
     */
    private const DEFAULT = [
        'image' => null,
        'headline' => 'Gear made for the way you travel.',
        'subheading' => 'Considered luggage, carry-ons and accessories — built to move.',
        'ctaLabel' => 'Shop new arrivals',
        'ctaUrl' => '/new-arrivals',
    ];

    /**
     * The active hero as an explicit prop array, or the static default.
     *
     * @return array{image: ?string, headline: string, subheading: ?string, ctaLabel: ?string, ctaUrl: ?string}
     */
    public function activeOrDefault(): array
    {
        $hero = HeroImage::query()->active()->orderBy('sort_order')->first();

        if ($hero === null) {
            return self::DEFAULT;
        }

        return [
            'image' => $hero->image_path ? Storage::url($hero->image_path) : null,
            'headline' => $hero->headline,
            'subheading' => $hero->subheading,
            'ctaLabel' => $hero->cta_label,
            'ctaUrl' => $hero->cta_url,
        ];
    }
}

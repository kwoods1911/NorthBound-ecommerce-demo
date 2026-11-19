<?php

use App\Models\HeroImage;
use App\Services\HeroImageService;

it('deactivates the previously active hero when another is activated', function () {
    $first = HeroImage::factory()->active()->create();
    $second = HeroImage::factory()->create();

    $second->update(['is_active' => true]);

    expect($first->fresh()->is_active)->toBeFalse()
        ->and($second->fresh()->is_active)->toBeTrue()
        ->and(HeroImage::query()->active()->count())->toBe(1);
});

it('allows no active hero when the only active row is switched off', function () {
    $hero = HeroImage::factory()->active()->create();

    $hero->update(['is_active' => false]);

    expect(HeroImage::query()->active()->count())->toBe(0);
});

it('returns the active hero from the service', function () {
    HeroImage::factory()->create(['headline' => 'Not this one']);
    HeroImage::factory()->active()->create([
        'image_path' => null,
        'headline' => 'Live hero',
        'subheading' => 'Sub',
        'cta_label' => 'Go',
        'cta_url' => '/new-arrivals',
    ]);

    expect(app(HeroImageService::class)->activeOrDefault())->toBe([
        'image' => null,
        'headline' => 'Live hero',
        'subheading' => 'Sub',
        'ctaLabel' => 'Go',
        'ctaUrl' => '/new-arrivals',
    ]);
});

it('falls back to a default hero when none is active', function () {
    HeroImage::factory()->count(2)->create(['is_active' => false]);

    $hero = app(HeroImageService::class)->activeOrDefault();

    expect($hero['image'])->toBeNull()
        ->and($hero['headline'])->not->toBe('')
        ->and($hero['ctaUrl'])->not->toBeNull();
});

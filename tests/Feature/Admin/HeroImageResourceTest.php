<?php

use App\Filament\Resources\HeroImageResource\Pages\CreateHeroImage;
use App\Filament\Resources\HeroImageResource\Pages\ListHeroImages;
use App\Models\HeroImage;
use App\Models\User;
use Livewire\Livewire;

it('denies non-admins the hero images resource', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get('/admin/hero-images')->assertForbidden();
});

it('lets an admin list and create hero images', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(ListHeroImages::class)->assertOk();

    Livewire::test(CreateHeroImage::class)
        ->fillForm([
            'headline' => 'Fresh hero',
            'subheading' => 'Sub copy',
            'cta_label' => 'Shop',
            'cta_url' => '/new-arrivals',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(HeroImage::query()->where('headline', 'Fresh hero')->exists())->toBeTrue();
});

it('deactivates other heroes when a new active one is created via the resource', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $existing = HeroImage::factory()->active()->create();

    Livewire::test(CreateHeroImage::class)
        ->fillForm([
            'headline' => 'New live hero',
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($existing->fresh()->is_active)->toBeFalse()
        ->and(HeroImage::query()->active()->count())->toBe(1);
});

<?php

namespace App\Providers;

use App\Services\Ai\AiChatProvider;
use App\Services\Ai\GeminiChatProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the AI chat abstraction to its concrete implementation. Swapping
 * providers is a one-line change here — nothing else in the app references a
 * vendor.
 */
class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiChatProvider::class, function ($app): AiChatProvider {
            $config = $app['config']->get('services.gemini', []);

            return new GeminiChatProvider(
                apiKey: (string) ($config['api_key'] ?? ''),
                model: (string) ($config['model'] ?? 'gemini-2.5-flash'),
                baseUrl: rtrim((string) ($config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/'),
            );
        });
    }
}

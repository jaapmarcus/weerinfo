<?php

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function fakeWeatherAndOpenAi(): void
{
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => ['temperature_2m' => 20, 'apparent_temperature' => 19, 'weather_code' => 3],
            'hourly' => [
                'time' => array_fill(0, 24, '2026-07-27T12:00'),
                'temperature_2m' => array_fill(0, 24, 20),
                'precipitation_probability' => array_fill(0, 24, 10),
                'weather_code' => array_fill(0, 24, 3),
                'uv_index' => array_fill(0, 24, 4),
            ],
            'daily' => [
                'time' => ['2026-07-27'],
                'weather_code' => [3],
                'temperature_2m_max' => [22],
                'temperature_2m_min' => [14],
                'precipitation_probability_max' => [20],
                'uv_index_max' => [5],
            ],
        ], 200),
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => "Eerste alinea van het weerbericht.\n\nTweede alinea met meer details."]],
            ],
        ], 200),
    ]);
}

test('generates a weerbericht blog post from forecasts and openai', function () {
    Storage::fake('public');
    User::factory()->create();
    fakeWeatherAndOpenAi();

    $this->artisan('weather:daily-report')->assertExitCode(0);

    $post = BlogPost::first();

    expect($post)->not->toBeNull();
    expect($post->title)->toContain('Weerbericht van');
    expect($post->body)->toContain('Eerste alinea van het weerbericht.');
    expect($post->body)->toContain('Tweede alinea met meer details.');
    expect($post->is_published)->toBeTrue();
    expect($post->featured_image)->not->toBeNull();
    expect($post->categories->pluck('name'))->toContain('Weerbericht');

    Storage::disk('public')->assertExists($post->featured_image);
});

test('does not duplicate the report when run twice on the same day', function () {
    Storage::fake('public');
    User::factory()->create();
    fakeWeatherAndOpenAi();

    $this->artisan('weather:daily-report')->assertExitCode(0);
    $this->artisan('weather:daily-report')->assertExitCode(0);

    expect(BlogPost::count())->toBe(1);
});

test('regenerates when --force is passed', function () {
    Storage::fake('public');
    User::factory()->create();
    fakeWeatherAndOpenAi();

    $this->artisan('weather:daily-report')->assertExitCode(0);
    $this->artisan('weather:daily-report --force')->assertExitCode(0);

    expect(BlogPost::count())->toBe(2);
});

test('fails gracefully when a forecast cannot be fetched', function () {
    User::factory()->create();

    Http::fake([
        'api.open-meteo.com/*' => Http::response([], 500),
    ]);

    $this->artisan('weather:daily-report')->assertExitCode(1);

    expect(BlogPost::count())->toBe(0);
});

test('fails gracefully when openai does not return content', function () {
    Storage::fake('public');
    User::factory()->create();

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => ['temperature_2m' => 20, 'apparent_temperature' => 19, 'weather_code' => 3],
            'hourly' => [
                'time' => array_fill(0, 24, '2026-07-27T12:00'),
                'temperature_2m' => array_fill(0, 24, 20),
                'precipitation_probability' => array_fill(0, 24, 10),
                'weather_code' => array_fill(0, 24, 3),
                'uv_index' => array_fill(0, 24, 4),
            ],
            'daily' => [
                'time' => ['2026-07-27'],
                'weather_code' => [3],
                'temperature_2m_max' => [22],
                'temperature_2m_min' => [14],
                'precipitation_probability_max' => [20],
                'uv_index_max' => [5],
            ],
        ], 200),
        'api.openai.com/*' => Http::response([], 500),
    ]);

    $this->artisan('weather:daily-report')->assertExitCode(1);

    expect(BlogPost::count())->toBe(0);
});

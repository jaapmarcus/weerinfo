<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Support\Weather;
use App\Support\WeatherMap;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateDailyWeatherReport extends Command
{
    protected $signature = 'weather:daily-report {--force : Regenerate even if today’s report already exists}';

    protected $description = 'Generate the daily national weather map and AI-written forecast blog post';

    /**
     * @var array<int, array{name: string, lat: float, lon: float}>
     */
    private const LOCATIONS = [
        ['name' => 'Vlissingen', 'lat' => 51.4426, 'lon' => 3.5734],
        ['name' => 'Weert', 'lat' => 51.2500, 'lon' => 5.7047],
        ['name' => 'Den Helder', 'lat' => 52.9563, 'lon' => 4.7601],
        ['name' => 'De Bilt', 'lat' => 52.1089, 'lon' => 5.1801],
        ['name' => 'Zutphen', 'lat' => 52.1397, 'lon' => 6.1970],
        ['name' => 'Groningen', 'lat' => 53.2194, 'lon' => 6.5665],
    ];

    public function handle(): int
    {
        $today = now();

        $category = BlogCategory::firstOrCreate(
            ['slug' => 'weerbericht'],
            ['name' => 'Weerbericht'],
        );

        $alreadyExists = BlogPost::whereHas(
            'categories',
            fn ($query) => $query->where('blog_categories.id', $category->id),
        )->whereDate('published_at', $today)->exists();

        if ($alreadyExists && ! $this->option('force')) {
            $this->info('Weerbericht voor vandaag bestaat al. Gebruik --force om opnieuw te genereren.');

            return self::SUCCESS;
        }

        $author = User::first();

        if (! $author) {
            $this->error('Geen gebruiker gevonden om als auteur te gebruiken.');

            return self::FAILURE;
        }

        $locations = $this->collectForecasts();

        if ($locations === null) {
            $this->error('Kon niet voor alle locaties een weersverwachting ophalen.');

            return self::FAILURE;
        }

        $body = $this->generateForecastText($locations, $today);

        if ($body === null) {
            $this->error('Het genereren van de weerberichttekst via OpenAI is mislukt.');

            return self::FAILURE;
        }

        $imagePath = 'blog/weerbericht-'.$today->format('Y-m-d').'.png';
        Storage::disk('public')->put($imagePath, WeatherMap::render($locations, $today));

        $post = BlogPost::create([
            'user_id' => $author->id,
            'title' => 'Weerbericht van '.$today->translatedFormat('l d F Y'),
            'excerpt' => Str::limit(strip_tags($body), 160),
            'body' => $body,
            'featured_image' => $imagePath,
            'is_published' => true,
            'published_at' => $today,
        ]);

        $post->categories()->sync([$category->id]);

        $this->info("Weerbericht aangemaakt: {$post->title}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{name: string, lat: float, lon: float, temperature_max: float, temperature_min: float, weather_code: int}>|null
     */
    private function collectForecasts(): ?array
    {
        $locations = [];

        foreach (self::LOCATIONS as $location) {
            $forecast = Weather::forecast($location['lat'], $location['lon']);
            $today = $forecast['days'][0] ?? null;

            if ($today === null) {
                return null;
            }

            $locations[] = [
                ...$location,
                'temperature_max' => $today['temperature_max'],
                'temperature_min' => $today['temperature_min'],
                'weather_code' => $today['weather_code'],
            ];
        }

        return $locations;
    }

    /**
     * @param  array<int, array{name: string, lat: float, lon: float, temperature_max: float, temperature_min: float, weather_code: int}>  $locations
     */
    private function generateForecastText(array $locations, CarbonInterface $today): ?string
    {
        $descriptions = Weather::descriptions();

        $summary = collect($locations)
            ->map(fn (array $location) => sprintf(
                '%s: %d tot %d graden, %s',
                $location['name'],
                round($location['temperature_min']),
                round($location['temperature_max']),
                $descriptions[$location['weather_code']] ?? 'onbekend',
            ))
            ->implode('; ');

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'temperature' => 0.7,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Je bent een Nederlandse weerredacteur die korte, prettig leesbare weerberichten schrijft voor de website Weer Gids. Je schrijft in vloeiende alinea\'s, geen opsommingen, in een neutrale journalistieke toon.',
                    ],
                    [
                        'role' => 'user',
                        'content' => sprintf(
                            'Schrijf een weerbericht van ongeveer 300 woorden in het Nederlands voor %s. Noem de opvallendste verschillen tussen de regio\'s. Begin niet met een titel of aanhef, begin direct met de eerste alinea.',
                            $today->translatedFormat('l d F Y'),
                            $summary,
                        ),
                    ],
                ],
            ]);

        if ($response->failed()) {
            return null;
        }

        $text = trim((string) $response->json('choices.0.message.content'));

        if ($text === '') {
            return null;
        }

        return collect(preg_split('/\n{2,}/', $text))
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph) => '<p>'.e($paragraph).'</p>')
            ->implode('');
    }
}

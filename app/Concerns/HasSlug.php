<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Boot the trait, deriving a unique slug from the source attribute
     * (title/name) whenever a slug isn't explicitly provided.
     */
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (filled($model->slug)) {
                return;
            }

            $model->slug = static::uniqueSlug(Str::slug($model->{$model->slugSourceColumn()}));
        });
    }

    /**
     * The column the slug is derived from.
     */
    protected function slugSourceColumn(): string
    {
        return 'title';
    }

    /**
     * Ensure the given base slug is unique for this model, suffixing with
     * -2, -3, etc. until it is.
     */
    protected static function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        while (static::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

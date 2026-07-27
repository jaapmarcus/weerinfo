<?php

namespace App\Models;

use App\Concerns\HasSlug;
use Database\Factories\BlogCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogCategory extends Model
{
    /** @use HasFactory<BlogCategoryFactory> */
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * @return BelongsToMany<BlogPost, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class);
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }
}

<?php

/*
 * NOTICE OF LICENSE
 *
 * Part of the Rinvex Category Package.
 *
 * This source file is subject to The MIT License (MIT)
 * that is bundled with this package in the LICENSE file.
 *
 * Package: Rinvex Category Package
 * License: The MIT License (MIT)
 * Link:    https://rinvex.com
 */

declare(strict_types=1);

namespace Rinvex\Category;

use Spatie\Sluggable\HasSlug;
use Kalnoy\Nestedset\NodeTrait;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Rinvex\Cacheable\CacheableEloquent;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Rinvex\Category\Category.
 *
 * @property int $id
 * @property array $name
 * @property string $slug
 * @property array $description
 * @property int $_lft
 * @property int $_rgt
 * @property int $parent_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property-read \Rinvex\Category\Category $parent
 * @property-read \Kalnoy\Nestedset\Collection|\Rinvex\Category\Category[] $children
 *
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereLft($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereRgt($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereParentId($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Rinvex\Category\Category whereDeletedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Category extends Model
{
    use HasSlug;
    use NodeTrait;
    use HasTranslations;
    use CacheableEloquent;

    /**
     * {@inheritdoc}
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = [
        'name',
        'description',
    ];

    /**
     * Get all attached models of the given class to the category.
     *
     * @param string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function entries(string $class): MorphToMany
    {
        return $this->morphedByMany($class, 'categorizable', 'categorizables', 'category_id', 'categorizable_id');
    }

    /**
     * Get the options for generating the slug.
     *
     * @return \Spatie\Sluggable\SlugOptions
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Get category tree.
     *
     * @return array
     */
    public static function tree(): array
    {
        return static::get()->toTree()->toArray();
    }

    /**
     * Find many categories by name or create if not exists.
     *
     * @param array       $categories
     * @param string|null $locale
     *
     * @return \Illuminate\Support\Collection
     */
    public static function findManyByNameOrCreate(array $categories, string $locale = null): Collection
    {
        // Expects array of category names
        return collect($categories)->map(function ($category) use ($locale) {
            return static::findByNameOrCreate($category, $locale);
        });
    }

    /**
     * Find category by name or create if not exists.
     *
     * @param string      $name
     * @param string|null $locale
     *
     * @return static
     */
    public static function findByNameOrCreate(string $name, string $locale = null): Category
    {
        $locale = $locale ?? app()->getLocale();

        return static::findByName($name, $locale) ?: static::createByName($name, $locale);
    }

    /**
     * Find category by name.
     *
     * @param string      $name
     * @param string|null $locale
     *
     * @return static|null
     */
    public static function findByName(string $name, string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return static::query()
                     ->where("name->{$locale}", $name)
                     ->first();
    }

    /**
     * Create category by name.
     *
     * @param string      $name
     * @param string|null $locale
     *
     * @return static
     */
    public static function createByName(string $name, string $locale = null): Category
    {
        $locale = $locale ?? app()->getLocale();

        return static::create([
            'name' => [$locale => $name],
        ]);
    }
}

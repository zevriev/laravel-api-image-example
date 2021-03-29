<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Schema(
 *     title="Image",
 *     description="Image resource",
 *     @OA\Xml(
 *         name="Image"
 *     )
 * )
 */
class Image extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'path',
    ];

    public function thumbs() {
        return $this->hasMany('App\Models\ImageThumb');
    }
}

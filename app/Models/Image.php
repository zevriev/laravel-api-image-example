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

    public static function getAll() {
        return Image::select('images.path', 'images.width', 'images.height',
            'image_thumbs.image_id', DB::raw('image_thumbs.path AS thumb_path'),
            DB::raw('image_thumbs.width AS thumb_width'), DB::raw('image_thumbs.height AS thumb_height'))
            ->leftJoin('image_thumbs', 'image_thumbs.image_id', '=', 'images.id')
            ->get();
    }
}

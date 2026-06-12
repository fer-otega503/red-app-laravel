<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'url',
        'imageable_id',
        'imageable_type',
    ];

    //Función que retorna la imagen relacionada con el modelo padre (polimórfico simple)
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}

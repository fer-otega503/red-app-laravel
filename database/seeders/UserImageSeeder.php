<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creamos 10 usuarios
        User::factory(10)->create()->each(function ($user) {
            // Generamos un número aleatorio entre 1 y 5
            $numberOfImages = rand(1, 5);

            // Creamos las imágenes asociándolas a la relación polimórfica 'images()' del usuario
            Image::factory($numberOfImages)->create([
                'imageable_id' => $user->id,
                'imageable_type' => User::class,
            ]);
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Cargamos únicamente la imagen más reciente para el listado general
        $users = User::with('latestImage')->get();
        
        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:255',
            'images' => 'nullable|array' // Validamos que sea un arreglo de cadenas
        ]);

        $validated['password'] = bcrypt('password123');

        // 1. Creamos al usuario primero para tener su ID (ignorando el arreglo 'images')
        $user = User::create(collect($validated)->except('images')->toArray());

        // 2. Procesamos las imágenes polimórficas
        if ($request->has('images') && is_array($request->images)) {
            foreach ($request->images as $base64Image) {
                if ($base64Image) {
                    $user->images()->create([
                        'url' => $base64Image
                    ]);
                }
            }
        }

        // 3. Lógica de poda (FIFO con límite de 4 imágenes) por si envían demasiadas en la creación
        $maxImages = 4;
        $totalImages = $user->images()->count();

        if ($totalImages > $maxImages) {
            $difference = $totalImages - $maxImages;
            
            // Obtenemos los IDs de las imágenes más antiguas (las primeras en procesarse en el foreach)
            $oldestImagesIds = $user->images()
                ->oldest() // equivalente a orderBy('created_at', 'asc')
                ->limit($difference)
                ->pluck('id');

            // Las eliminamos
            $user->images()->whereIn('id', $oldestImagesIds)->delete();
        }

        // 4. Retornamos el recurso cargando la galería resultante
        return UserResource::make($user->load('images'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Cargamos TODA la galería de imágenes del usuario, sin ningún límite
        $user = User::with('images')->findOrFail($id);
        
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8',
            'phone_number' => 'sometimes|required|string|max:255', 
            'images' => 'sometimes|nullable|array' // Validamos que sea un arreglo de imágenes
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        // Actualizamos los datos base del usuario (ignorando 'images' para la tabla users)
        $user->update(collect($validated)->except('images')->toArray());

        // 1. Carga Acumulativa
        if ($request->has('images') && is_array($request->images)) {
            foreach ($request->images as $base64Image) {
                if ($base64Image) {
                    $user->images()->create([
                        'url' => $base64Image
                    ]);
                }
            }
        }

        // 2. Lógica de poda (FIFO con límite máximo, por ejemplo: 4 imágenes)
        $maxImages = 4;
        $totalImages = $user->images()->count();

        if ($totalImages > $maxImages) {
            $difference = $totalImages - $maxImages;
            
            // Obtenemos los IDs de las imágenes más antiguas
            $oldestImagesIds = $user->images()
                ->oldest() // equivalente a orderBy('created_at', 'asc')
                ->limit($difference)
                ->pluck('id');

            // Las eliminamos
            $user->images()->whereIn('id', $oldestImagesIds)->delete();
        }

        // 3. Retornamos el recurso cargando la galería resultante
        return UserResource::make($user->load('images'));
    }

    
    /**
     * Remove the specified resource from storage.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            // Sacamos la URL desde la relación polimórfica, devolviendo null si no tiene
            'image' => $this->image ? $this->image->url : null, 
        ];
    }   

    public function destroy($id): JsonResponse
    {
        // Buscamos al usuario o lanzamos 404
        $user = User::findOrFail($id);

        // Eliminamos todos los registros huérfanos de la relación polimórfica (Base64)
        $user->images()->delete();

        // Eliminamos al usuario
        $user->delete();

        // Retornamos respuesta exitosa con código 200
        return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
    }
}



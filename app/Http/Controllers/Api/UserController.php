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
        // El 'with' carga la relación polimórfica automáticamente
        $users = User::with('image')->get();
        
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
            // Agregamos la validación de la imagen (ajusta según lo que mandes)
            'image' => 'nullable' 
        ]);

        $validated['password'] = bcrypt('password123');

        // 1. Creamos al usuario primero para tener su ID
        $user = User::create($validated);

        // 2. Procesamos la imagen polimórfica
        if ($request->has('image') && $request->image != null) {
            // Lógica para guardar el archivo físico en storage (depende de cómo lo mandes)
            // $path = ... 
            
            // 3. Creamos la relación polimórfica
            $user->image()->create([
                'url' => 'aqui_va_el_path_de_la_imagen.jpg' // Ajusta al nombre de tu columna
            ]);
        }

        return UserResource::make($user);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return UserResource::make($user);
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
            'image' => 'nullable' // Permitimos que la imagen venga en el request
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        // Actualizamos los datos base del usuario
        $user->update($validated);

        // Magia polimórfica para la actualización
        if ($request->has('image') && $request->image != null) {
            
            // OJO: Si mandas un archivo físico o Base64, aquí procesas el nombre/ruta
            $valorImagen = $request->image; 

            if ($user->image) {
                // Si ya existe su registro polimórfico, lo actualizamos
                $user->image()->update([
                    'url' => $valorImagen // Cambia 'url' por el nombre de tu columna (path, src, etc.)
                ]);
            } else {
                // Si no tenía imagen previa, creamos la relación
                $user->image()->create([
                    'url' => $valorImagen
                ]);
            }
        }

        // Refrescamos el usuario para que el recurso devuelva la nueva imagen
        return UserResource::make($user->load('image'));
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

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
}



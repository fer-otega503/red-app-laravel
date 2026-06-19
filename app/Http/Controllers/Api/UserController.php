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
    public function index()
    {
        return UserCollection::make(User::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validamos solo lo que la app de Android nos puede mandar
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:255',
        ]);

        // 2. Le inyectamos una contraseña genérica por defecto 
        // para que la base de datos (MySQL) no nos tire error de "campo vacío"
        $validated['password'] = bcrypt('password123');

        // 3. Creamos el usuario
        $user = User::create($validated);

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
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // 2. Validamos los datos recibidos
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
            // Ojo: En tu imagen vi que es varchar(255), actualicé el max a 255 por si acaso
            'phone_number' => 'sometimes|required|string|max:255', 
        ]);

        // 3. Actualizamos y devolvemos
        $user->update($validated);

        return UserResource::make($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $userName = $user->name;
        $user->delete();

        return response()->json([
            'message' => "User $userName deleted successfully.",
        ], 200);
    }
}

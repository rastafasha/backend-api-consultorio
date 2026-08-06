<?php

namespace App\Http\Controllers\Admin\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor\DoctorAddress;
use Illuminate\Http\Request;


class DoctorAddressController extends Controller
{
    // Listar direcciones de un doctor específico
    public function getByDoctor($user_id)
    {
        $addresses = DoctorAddress::with('schedule_days.schedule_hours.doctor_schedule_hour')
        ->where('user_id', $user_id)
        ->get();

    return response()->json([
        'status' => 'success',
        'addresses' => $addresses
    ]);
    }

    // Almacenar una dirección de forma independiente
    public function store(Request $request) 
{ 
    $request->validate([ 
        'user_id' => 'required|exists:users,id', 
        'address' => 'required|string', 
        'is_active' => 'boolean', // Es buena práctica validar que sea booleano
    ]); 

    // Forzamos a que el input se transforme en un true o false real de PHP
    $isActive = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);

    $address = DoctorAddress::create([ 
        'user_id' => $request->user_id, 
        'name_consultorio' => $request->name_consultorio, 
        'address' => $request->address, 
        'is_active' => $isActive // Aquí pasamos la variable ya convertida
    ]); 

    return response()->json([ 
        'status' => 'success', 
        'message' => 'Consultorio registrado correctamente', 
        'address' => $address 
    ], 201); 
}


    // Actualizar una dirección
    public function update(Request $request, $id)
    {
        $address = DoctorAddress::findOrFail($id);
        
        $address->update($request->only(['name_consultorio', 'address', 'is_active']));

        return response()->json([
            'status' => 'success',
            'message' => 'Consultorio actualizado con éxito',
            'address' => $address
        ]);
    }

    // Borrado lógico (SoftDelete) del consultorio
    public function destroy($id)
    {
        $address = DoctorAddress::findOrFail($id);
        $address->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Consultorio eliminado correctamente'
        ]);
    }
}
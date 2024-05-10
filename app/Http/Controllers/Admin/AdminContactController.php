<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AdminContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   

        $this->authorize('index', Contact::class);

        $contactsAll = Contact::get();

        return response()->json([
            'code' => 200,
            'status' => 'List Contacts',
            'contactsAll' => $contactsAll,
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function contactShow(Contact $contact)
    {   
        $this->authorize('contactShow', Contact::class);

        if (!$contact) {
            return response()->json([
                'message' => 'Contact not found.'
            ], 404);
        }

        $contact = Contact::select([
                "id", "phone", "email", "subject", "comment", "status"
            ])->find($contact);

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'contact' => $contact,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function contactUpdate(Request $request,  $id)
    {   
        try {
            DB::beginTransaction();
            
            $input = $request->all();
            $contact = Contact::find($id);
            $contact->update($input);
            

            DB::commit();

            Log::info("Actualización de contacto exitoso- 
            Usuario: {$request->user()->username}, Agent: {$request->header('user-agent')}");


            return response()->json([
                'code' => 200,
                'status' => 'Update contact success',
                'contact' => $contact,
            ], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();


            Log::error("Error en actualizar un contacto - Usuario: 
            {$request->user()->username} Mensaje de error: {$exception->getMessage()}");
            
            return response()->json([
                'message' => 'Error no update'  . $exception,
                'exception' => $exception->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function contactDestroy(Request $request, Contact $contact)
    {
        $this->authorize('contactDestroy', Contact::class);

        try {
            DB::beginTransaction();

            $contact->delete();

            DB::commit();

            Log::info("Eliminación de contacto exitoso- 
            Usuario: {$request->user()->username}, Agent: {$request->header('user-agent')}");

            return response()->json([
                'code' => 200,
                'status' => 'Contact delete',
            ], 200);

        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error("Error en eliminar un contacto - Usuario: 
            {$request->user()->username} Mensaje de error: {$exception->getMessage()}");

            return response()->json([
                'status' => 'error',
                'message' => 'Borrado fallido. Conflicto',
                'exception' => $exception->getMessage(),
            ], 409);
        }
    }

}

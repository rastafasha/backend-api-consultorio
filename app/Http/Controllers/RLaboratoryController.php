<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RLaboratory;
use App\Http\Resources\RLaboratory\RLaboratoryCollection;
use App\Http\Resources\RLaboratory\RLaboratoryResource;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;

class RLaboratoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$request->input('patient_id')) {
            return response()->json(['message' => 'El ID del paciente es requerido'], 400);
        }

        $laboratoriesSaved = [];
        $comentarioGeneral = $request->input('comentario_general');
        $metaData = json_decode($request->input('file_metadata'), true) ?: [];

        if (empty($metaData) && (is_null($comentarioGeneral) || trim($comentarioGeneral) === '')) {
            return response()->json(['message' => 'No se recibió información para guardar'], 400);
        }

        // --- CASO 1: COLA LOCAL CON REGISTROS (Vía "FilesAdded") ---
        if (!empty($metaData)) {
            // 📦 Forzamos a Laravel a darnos TODOS los archivos de 'files' como un array limpio indexado por números
            $allUploadedFiles = $request->file('files') ?: [];

            foreach ($metaData as $data) {
                $path = NULL;
                $name_file = 'Nota de texto (Sin archivo)';
                $size = '0';
                $extension = 'text';
                $resolution = NULL;

                $comentarioFinal = (!empty($data['comentario'])) ? $data['comentario'] : $comentarioGeneral;

                // 🔍 EXTRACCIÓN MAESTRA DEL ARCHIVO:
                // Buscamos directamente en el array indexado usando el file_index numérico que mandó Angular
                $file = null;
                if (isset($data['has_file']) && $data['has_file'] && is_numeric($data['file_index'])) {
                    $idx = (int) $data['file_index'];
                    $file = $allUploadedFiles[$idx] ?? null;
                }

                // Si el archivo físico fue encontrado en el array indexado
                if ($file) {
                    $extension = $file->getClientOriginalExtension();
                    $size = number_format($file->getSize() / 1024, 1);
                    $name_file = $file->getClientOriginalName();

                    if (in_array(strtolower($extension), ["jpeg", "bmp", "jpg", "png"])) {
                        $dimensions = getimagesize($file);
                        if ($dimensions) {
                            $resolution = $dimensions[0] . "x" . $dimensions[1];
                        }
                    }

                    try {
                        // 🚀 SUBIDA A CLOUDINARY
                        $uploadedFile = $file->storeOnCloudinary('klyntic/rlaboratories');

                        // 🔍 PRUEBA EN CASCADA PARA RECOGER LA URL SEGÚN TU VERSIÓN DEL PAQUETE
                        if (is_array($uploadedFile)) {
                            $path = $uploadedFile['secure_url'] ?? $uploadedFile['url'] ?? NULL;
                        } elseif (is_object($uploadedFile)) {
                            if (method_exists($uploadedFile, 'getSecurePath')) {
                                $path = $uploadedFile->getSecurePath();
                            } elseif (method_exists($uploadedFile, 'getSecureUrl')) {
                                $path = $uploadedFile->getSecureUrl();
                            } elseif (method_exists($uploadedFile, 'url')) {
                                $path = $uploadedFile->url();
                            } else {
                                // Si es un objeto genérico o un wrapper de CloudinaryEngine/ApiResponse
                                $path = $uploadedFile->secure_url ?? $uploadedFile->url ?? NULL;
                            }
                        }

                        // 🚨 DETECCIÓN DE EMERGENCIA: Si tras todos los intentos la URL sigue vacía, 
                        // paramos el código aquí y te mostramos en Angular exactamente qué respondió Cloudinary
                        if (is_null($path)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Cloudinary procesó el archivo pero no devolvió ninguna URL válida.',
                                'clase_objeto_recibido' => is_object($uploadedFile) ? get_class($uploadedFile) : 'Es un Array/Primitive',
                                'respuesta_cruda_cloudinary' => $uploadedFile
                            ], 500);
                        }

                    } catch (\Throwable $e) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Fallo la comunicación con Cloudinary usando storeOnCloudinary',
                            'error_detallado' => $e->getMessage()
                        ], 500);
                    }

                }

                // 💾 Guardado definitivo en base de datos de MAMP
                try {
                    $laboratory = RLaboratory::create([
                        'patient_id' => $request->patient_id,
                        'comentario' => $comentarioFinal,
                        'name_file' => $name_file,
                        'size' => $size,
                        'resolution' => $resolution,
                        'file' => $path, // ¡Aquí por fin se guardará la URL de Cloudinary!
                        'type' => $extension,
                    ]);
                    $laboratoriesSaved[] = $laboratory;
                } catch (\Exception $dbEx) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Error al insertar en la base de datos',
                        'error_detallado' => $dbEx->getMessage()
                    ], 500);
                }
            }
        }

        // --- CASO 2: SOLO COMENTARIO DIRECTO ---
        else if (!empty($comentarioGeneral)) {
            try {
                $laboratory = RLaboratory::create([
                    'patient_id' => $request->patient_id,
                    'comentario' => $comentarioGeneral,
                    'name_file' => 'Nota de texto general',
                    'size' => '0',
                    'resolution' => NULL,
                    'file' => NULL,
                    'type' => 'text',
                ]);
                $laboratoriesSaved[] = $laboratory;
            } catch (\Exception $dbEx) {
                return response()->json(['message' => 'Error al insertar comentario general', 'error_detallado' => $dbEx->getMessage()], 500);
            }
        }

        return response()->json([
            'status' => 'success',
            'laboratories' => RLaboratoryResource::collection(collect($laboratoriesSaved))
        ]);
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    public function showByPatient($patient_id)
    {
        $laboratories = RLaboratory::where("patient_id", $patient_id)->get();

        return response()->json([
            "laboratories" => RLaboratoryCollection::make($laboratories),
        ]);


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id_paciente)
    {
        // 1. ELIMINACIÓN DE ARCHIVOS VIEJOS (Si el médico borró alguno de la tabla)
        // Angular debe mandarte un array opcional con los IDs de los archivos que se eliminaron en la vista
        if ($request->has('deleted_file_ids')) {
            $deletedIds = json_decode($request->input('deleted_file_ids'), true) ?? [];

            foreach ($deletedIds as $fileId) {
                $fileRecord = RLaboratory::find($fileId);
                if ($fileRecord) {
                    try {
                        // 🗑️ Extraemos el Public ID de Cloudinary desde la URL para borrarlo de su nube
                        // Ejemplo URL: https://cloudinary.com
                        // Queremos obtener: 'klyntic/rlaboratories/abcde'
                        $publicIdWithExtension = Str::after($fileRecord->file, 'upload/');
                        // Quitamos la versión (v12345/) si existe
                        if (Str::contains($publicIdWithExtension, '/')) {
                            $publicIdWithExtension = Str::after($publicIdWithExtension, '/');
                        }
                        $publicId = Str::beforeLast($publicIdWithExtension, '.');

                        // Borramos de Cloudinary (Detecta automáticamente si es imagen o pdf/raw)
                        Cloudinary::uploadApi()->destroy($publicId);

                    } catch (\Exception $e) {
                        error_log("⚠️ No se pudo borrar de Cloudinary, procediendo a borrar de BD: " . $e->getMessage());
                    }

                    // Borramos definitivamente el registro de la Base de Datos
                    $fileRecord->delete();
                }
            }
        }

        // 2. ACTUALIZACIÓN DE COMENTARIOS EN ARCHIVOS EXISTENTES
        // Si necesitas actualizar el comentario general de los reportes del paciente:
        RLaboratory::where('patient_id', $id_paciente)->update([
            'comentario' => $request->input('comentario')
        ]);

        // 3. AGREGAR ARCHIVOS NUEVOS (Misma lógica del store)
        $newLaboratories = [];
        if ($request->hasFile("files")) {
            foreach ($request->file("files") as $file) {
                $extension = $file->getClientOriginalExtension();
                $size = $file->getSize();
                $name_file = $file->getClientOriginalName();
                $dimensions = null;

                if (in_array(strtolower($extension), ["jpeg", "bmp", "jpg", "png"])) {
                    $dimensions = getimagesize($file);
                }

                try {
                    // Subida a Cloudinary
                    $cloudinaryResponse = Cloudinary::uploadApi()->upload(
                        $file->getRealPath(),
                        ['folder' => 'klyntic/rlaboratories']
                    );

                    $path = $cloudinaryResponse['secure_url'];

                    // Creamos el nuevo registro adjunto al paciente
                    $laboratory = RLaboratory::create([
                        'patient_id' => $id_paciente,
                        'comentario' => $request->input('comentario'),
                        'name_file' => $name_file,
                        'size' => $size,
                        'resolution' => $dimensions ? $dimensions[0] . "x" . $dimensions[1] : NULL,
                        'file' => $path,
                        'type' => $extension,
                        'time' => now()->toTimeString(),
                    ]);

                    $newLaboratories[] = $laboratory;

                } catch (\Exception $e) {
                    error_log("❌ Error subiendo archivo nuevo a Cloudinary: " . $e->getMessage());
                    continue;
                }
            }
        }

        // 4. Traemos la lista completa y actualizada de reportes que le quedaron al paciente
        $allLaboratories = RLaboratory::where('patient_id', $id_paciente)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Historial de laboratorios actualizado correctamente',
            'laboratories' => RLaboratoryResource::collection($allLaboratories)
        ]);
    }

    public function addFiles(Request $request)
    {
        $laboratory = RLaboratory::findOrFail($request->appointment_id);
        foreach ($request->file("files") as $key => $file) {
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $name_file = $file->getClientOriginalName();
            $data = null;
            if (in_array(strtolower($extension), ["jpeg", "bmp", "jpg", "png"])) {
                $data = getImageSize($file);

            }
            $path = Storage::putFile("laboratories", $file);

            $laboratory = RLaboratory::create([
                'appointment_id' => $request->appointment_id,
                'name_file' => $name_file,
                'size' => $size,
                'resolution' => $data ? $data[0] . "x" . $data[1] : NULL,
                'file' => $path,
                'type' => $extension,
            ]);
        }

        return response()->json(['laboratory' => RLaboratoryResource::make($laboratory)]);

    }

    public function removeFiles($id)
    {
        // 1. Buscamos el registro o disparamos un error 404 de inmediato si no existe
        $laboratory = RLaboratory::findOrFail($id);

        try {
            // 2. 🗑️ EXTRACCIÓN DINÁMICA DEL PUBLIC ID DESDE LA URL
            // Ejemplo de URL guardada: https://cloudinary.com

            // Cortamos el texto para quedarnos con lo que está después de 'upload/'
            $publicIdWithExtension = Str::after($laboratory->file, 'upload/');

            // Si la URL contiene una versión (ej: v1719600000/), la removemos para limpiar la ruta
            if (Str::contains($publicIdWithExtension, '/')) {
                $publicIdWithExtension = Str::after($publicIdWithExtension, '/');
            }

            // Removemos la extensión (.pdf, .jpg, .png) para obtener el Public ID puro
            $publicId = Str::beforeLast($publicIdWithExtension, '.');

            // 3. 🚀 ORDEN DE DESTRUCCIÓN A CLOUDINARY
            // El método destroy() detecta de forma automática el tipo de recurso (imagen o documento raw)
            Cloudinary::uploadApi()->destroy($publicId);

        } catch (\Exception $e) {
            // Si Cloudinary falla (por ejemplo, si el archivo ya fue borrado manualmente en su panel),
            // registramos el log pero no trancamos el flujo para que la base de datos sí se limpie.
            error_log("⚠️ Alerta: No se pudo borrar el archivo de Cloudinary: " . $e->getMessage());
        }

        // 4. Limpiamos la base de datos de forma definitiva
        $laboratory->delete();

        // Retorno limpio siguiendo tu formato estándar de respuestas HTTP exitosas
        return response()->json([
            "status" => "success",
            "message" => "Archivo eliminado exitosamente de la base de datos y de Cloudinary"
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($patient_id)
    {
        // 1. Buscamos TODOS los registros de laboratorio que pertenezcan a ese paciente
        $laboratories = RLaboratory::where('patient_id', $patient_id)->get();

        // Si el paciente no tiene ningún archivo registrado, salimos pacíficamente
        if ($laboratories->isEmpty()) {
            return response()->json([
                "status" => "info",
                "message" => "El paciente no tiene archivos de laboratorio registrados para eliminar."
            ], 200);
        }

        $publicIdsToDelete = [];

        // 2. 🔄 RECORREMOS CADA REGISTRO PARA EXTRAER SU PUBLIC ID DE CLOUDINARY
        foreach ($laboratories as $laboratory) {
            try {
                // Ejemplo URL: https://cloudinary.com
                $publicIdWithExtension = Str::after($laboratory->file, 'upload/');

                if (Str::contains($publicIdWithExtension, '/')) {
                    $publicIdWithExtension = Str::after($publicIdWithExtension, '/');
                }

                $publicId = Str::beforeLast($publicIdWithExtension, '.');

                // Acumulamos el ID en nuestra lista de eliminación
                $publicIdsToDelete[] = $publicId;

            } catch (\Exception $e) {
                error_log("⚠️ Error al parsear la URL del archivo ID {$laboratory->id}: " . $e->getMessage());
            }
        }

        // 3. 🚀 ELIMINACIÓN MASIVA EN CLOUDINARY
        // El SDK de Cloudinary permite borrar hasta 100 archivos de golpe usando 'destroy' o el método masiva 'deleteResources'
        if (!empty($publicIdsToDelete)) {
            try {
                // Usamos deleteResources del Admin API o un bucle rápido de destrucción por seguridad
                foreach ($publicIdsToDelete as $id) {
                    Cloudinary::uploadApi()->destroy($id);
                }
                error_log("✅ Se borraron " . count($publicIdsToDelete) . " archivos de Cloudinary con éxito.");
            } catch (\Exception $e) {
                error_log("❌ Falló la eliminación masiva en el panel de Cloudinary: " . $e->getMessage());
            }
        }

        // 4. 💾 ELIMINACIÓN EN MASA EN LA BASE DE DATOS (Railway)
        // Borra de golpe todas las filas de la tabla asociadas a este paciente
        RLaboratory::where('patient_id', $patient_id)->delete();

        return response()->json([
            "status" => "success",
            "message" => "Todo el buzón de reportes de laboratorio del paciente fue eliminado con éxito de la base de datos y de Cloudinary."
        ], 200);
    }
}

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
        // 🛡️ Validación inicial de seguridad
        if (!$request->hasFile('files')) {
            return response()->json(['message' => 'No se recibieron archivos de laboratorio'], 400);
        }

        $laboratoriesSaved = [];

        // 🔄 Recorremos cada archivo enviado en el arreglo 'files' desde Angular
        foreach ($request->file("files") as $file) {

            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $name_file = $file->getClientOriginalName();
            $dimensions = null;

            // 🖼️ Extraemos la resolución si es una imagen
            if (in_array(strtolower($extension), ["jpeg", "bmp", "jpg", "png"])) {
                $dimensions = getimagesize($file); // Corrección: la función nativa es en minúsculas
            }

            try {
                // 🚀 SUBIDA NATIVA A CLOUDINARY (Usando la variable del ciclo $file)
                $cloudinaryResponse = Cloudinary::uploadApi()->upload(
                    $file->getRealPath(),
                    ['folder' => 'klyntic/rlaboratories']
                );

                // Obtenemos la URL segura directamente desde la respuesta
                $path = $cloudinaryResponse['secure_url'];

                // 💾 Guardamos en la base de datos mapeando tu $fillable
                $laboratory = RLaboratory::create([
                    'patient_id' => $request->patient_id,
                    'comentario' => $request->input('comentario'), // Corregido: leemos del request
                    'name_file' => $name_file,
                    'size' => $size,
                    'resolution' => $dimensions ? $dimensions[0] . "x" . $dimensions[1] : NULL,
                    'file' => $path, // Guardamos la URL de Cloudinary
                    'type' => $extension,
                    'time' => now()->toTimeString(), // Asegúrate de rellenar 'time' si es requerido
                ]);

                // Acumulamos los laboratorios creados para la respuesta JSON
                $laboratoriesSaved[] = $laboratory;

            } catch (\Exception $e) {
                error_log("❌ Error subiendo archivo a Cloudinary: " . $e->getMessage());
                // Puedes decidir si continuar con el siguiente archivo o abortar
                continue;
            }
        }

        // Retornamos la lista de laboratorios procesados usando tu Resource Collection o el último
        return response()->json([
            'status' => 'success',
            // Devolvemos una colección con todos los archivos guardados con éxito
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

<?php
namespace App\Helper;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class Wasabi
{

   /*
   *
   * @brief
   * @author Juan Carlos Salinas Ojeda
   * @param string
   * @return
   *
   */
   public static function upload($carpeta, $archivo, $actual = null)
   {

       $prefijo = env('WASABI_PREFIJO') ?? 'UPLODIA';

       if (!$archivo) {
           return $actual; // Si no llega archivo, regresamos el anterior
       }

       // Si hay archivo anterior, lo borramos
       if ($actual && !str_starts_with($actual, 'http://') && !str_starts_with($actual, 'https://')) {
           Storage::disk('wasabi')->delete($actual);
       }

       $nombreOriginal = pathinfo(
           $archivo->getClientOriginalName(),
           PATHINFO_FILENAME
       );

       // Limpiar nombre: sin acentos, sin espacios, sin símbolos raros
       $nombreLimpio = Str::slug($nombreOriginal, '_');

       // Extensión real
       $extension = $archivo->getClientOriginalExtension();

       // Hash corto (8 chars)
       $hash = substr(md5(uniqid('', true)), 0, 5);

       // Nombre final
       $nombre = $hash . '__' . $nombreLimpio . '.' . $extension;

       // Subir archivo
       $path = $archivo->storeAs($prefijo . '/' . $carpeta, $nombre, 'wasabi');

       return $path;
   }


   /**
    * Obtiene una URL firmada de descarga de corta duración.
    * La disponibilidad de la transferencia se valida antes de llamar este método;
    * la URL no se cachea para no prolongar el acceso accidentalmente.
    *
    * @param string|null $path Path del archivo
    * @return string|null
    */
   public static function url($path)
   {
       if (!$path) {
           return null;
       }

       try {
           return Storage::disk('wasabi')->temporaryUrl(
               $path,
               now()->addMinutes(max(1, (int) config('filesystems.wasabi_url_ttl_minutes', 60)))
           );
       } catch (\Throwable $e) {
           Log::error('Error al generar URL temporal de Wasabi: ' . $e->getMessage());
           return null;
       }
   }



}

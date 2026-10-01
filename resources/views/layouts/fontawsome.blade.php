@if (strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)
   {{-- ESTE KIT ES FIJO, SIEMPRE SERÁ LOCAL --}}
   <script src="https://kit.fontawesome.com/8faff8ecab.js" crossorigin="anonymous"></script>
@else
   {{-- AQUI SIEMPRE HAY QUE PONER EL KIT PÚBLICO GLOBAL7 --}}
   <script src="https://kit.fontawesome.com/5e7b107147.js" crossorigin="anonymous"></script>
@endif

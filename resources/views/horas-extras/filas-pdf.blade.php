<h2>{{ $titulo }}</h2>
<table class="campos">
    @foreach(array_chunk($campos, 2, true) as $fila)
        <tr>
            @foreach($fila as $campo => $etiqueta)
                <td class="etiqueta">{{ $etiqueta }}</td>
                <td class="valor {{ $campo === 'Motivo' ? 'texto-registro' : '' }}">{{ ($datos[$campo] === null || $datos[$campo] === '') ? '—' : $datos[$campo] }}</td>
            @endforeach
        </tr>
    @endforeach
</table>

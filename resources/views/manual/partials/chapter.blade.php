<article class="manual-chapter" id="{{ $chapter['id'] }}">
    <header class="manual-chapter-header">
        <span class="manual-chapter-number">{{ $chapter['number'] }}</span>
        <h2>{{ $chapter['title'] }}</h2>
    </header>
    <div class="manual-chapter-body">
        @php($sectionOpen = false)
        @foreach($chapter['blocks'] as $block)
            @if($block['type'] === 'heading')
                @if($sectionOpen)</section>@endif
                <section class="manual-section"><h3>{{ $block['text'] }}</h3>
                @php($sectionOpen = true)
            @elseif($block['type'] === 'paragraph')
                <p>{{ $block['text'] }}</p>
            @elseif($block['type'] === 'step')
                <div class="manual-step-row"><span class="manual-step-number">{{ $block['number'] }}</span><span>{{ $block['text'] }}</span></div>
            @elseif($block['type'] === 'bullet')
                <ul class="manual-bullets"><li>{{ $block['text'] }}</li></ul>
            @elseif($block['type'] === 'callout')
                <aside class="alert alert-warning manual-callout"><strong>{{ $block['kind'] === 'important' ? 'IMPORTANTE' : 'RECOMENDACIÓN' }}</strong><span>{{ $block['text'] }}</span></aside>
            @elseif($block['type'] === 'table')
                <div class="manual-table-wrap"><table class="manual-table"><tbody>
                    @foreach($block['rows'] as $rowIndex => $row)
                        <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                    @endforeach
                </tbody></table></div>
            @elseif($block['type'] === 'image')
                <figure><a class="manual-image-link" href="{{ asset('assets/manual/' . $block['src']) }}" target="_blank" rel="noopener noreferrer" aria-label="Abrir imagen en una pestaña nueva: {{ $block['alt'] ?: 'Captura de pantalla del manual de Horalia' }}"><img class="manual-image" src="{{ asset('assets/manual/' . $block['src']) }}" alt="{{ $block['alt'] ?: 'Captura de pantalla del manual de Horalia' }}" loading="lazy"></a><figcaption class="manual-caption">{{ $block['alt'] }}</figcaption></figure>
            @elseif($block['type'] === 'link')
                <p><a class="manual-link" href="{{ $block['url'] }}" target="_blank" rel="noopener noreferrer">{{ $block['text'] }}</a></p>
            @elseif($block['type'] === 'caption')
                <p class="manual-caption">{{ $block['text'] }}</p>
            @endif
        @endforeach
        @if($sectionOpen)</section>@endif
    </div>
</article>

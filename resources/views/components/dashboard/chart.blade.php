@props([
    'panel',
])

@php
    /**
     * @var array{title: string, description: string, type: string, labels: list<string>, series: list<array{label: string, values: list<int>}>, palette: list<string>} $panel
     */
    $chartId = 'chart-'.substr(md5($panel['title'].json_encode($panel['series'])), 0, 10);

    $payload = json_encode([
        'type' => $panel['type'],
        'labels' => $panel['labels'],
        'series' => $panel['series'],
        'palette' => $panel['palette'],
    ], JSON_THROW_ON_ERROR);
@endphp

<figure class="chart-panel">
    <figcaption>
        <h3>{{ $panel['title'] }}</h3>
        <p>{{ $panel['description'] }}</p>
    </figcaption>

    {{-- Progressive enhancement: the canvas carries the chart, the table below
         carries the same figures for assistive technology and no-JS clients. --}}
    <div class="chart-canvas-wrap">
        <canvas
            id="{{ $chartId }}"
            data-chart-payload="{{ $payload }}"
            role="img"
            aria-label="{{ $panel['title'] }}: {{ $panel['description'] }}"
        ></canvas>
    </div>

    <table class="chart-data-table visually-hidden">
        <caption>{{ $panel['title'] }}</caption>
        <thead>
            <tr>
                <th scope="col">Category</th>
                @foreach ($panel['series'] as $series)
                    <th scope="col">{{ $series['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($panel['labels'] as $index => $label)
                <tr>
                    <th scope="row">{{ $label }}</th>
                    @foreach ($panel['series'] as $series)
                        <td>{{ $series['values'][$index] ?? 0 }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</figure>

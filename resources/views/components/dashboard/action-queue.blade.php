@props([
    'items',
])

@if (! empty($items))
    <section class="action-queue" aria-labelledby="action-queue-heading">
        <div class="section-heading">
            <div>
                <p class="eyebrow eyebrow-dark">WORKLIST</p>
                <h2 id="action-queue-heading">Needs your attention</h2>
            </div>
            <span>{{ count($items) }} OPEN</span>
        </div>

        <ul class="action-queue-list">
            @foreach ($items as $item)
                <li>
                    <a class="action-queue-item action-queue-{{ $item['tone'] }}" href="{{ $item['route'] }}">
                        <span class="action-queue-count">{{ $item['count'] }}</span>
                        <span class="action-queue-body">
                            <strong>{{ $item['label'] }}</strong>
                            <small>{{ $item['detail'] }}</small>
                        </span>
                        <span class="action-queue-arrow" aria-hidden="true">&#8594;</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<div class="nfv-news-board">
    <div class="nfv-news-leads">
        @foreach (array_slice($newsStories, 0, 2) as $story)
            <article class="nfv-news-lead-story">
                @if ($story['image'])
                    <a href="{{ $story['url'] }}" class="nfv-news-lead-photo" tabindex="-1" aria-hidden="true"><img src="{{ $story['image'] }}" alt="" loading="lazy" decoding="async"></a>
                @endif
                <div class="nfv-news-lead-copy">
                    <time datetime="{{ $story['date'] }}">{{ $story['display_date'] }}</time>
                    <h3><a href="{{ $story['url'] }}">{{ $story['title'] }}</a></h3>
                </div>
            </article>
        @endforeach
    </div>
    <div class="nfv-news-briefs">
        @foreach (array_slice($newsStories, 2) as $story)
            <article class="nfv-news-brief-story">
                @if ($story['image'])
                    <a href="{{ $story['url'] }}" class="nfv-news-brief-photo" tabindex="-1" aria-hidden="true"><img src="{{ $story['image'] }}" alt="" loading="lazy" decoding="async"></a>
                @endif
                <div>
                    <time datetime="{{ $story['date'] }}">{{ $story['display_date'] }}</time>
                    <h3><a href="{{ $story['url'] }}">{{ $story['title'] }}</a></h3>
                </div>
            </article>
        @endforeach
        <aside class="nfv-news-calendar" aria-labelledby="events-heading">
            <h3 id="events-heading">Events</h3>
            <a href="https://www.frenchvillage.edu.ng/event">View the NFLV events calendar</a>
        </aside>
    </div>
</div>

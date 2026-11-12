<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicWebsiteControllerTest extends TestCase
{
    private const SOURCE = 'https://www.frenchvillage.edu.ng';

    public function test_homepage_shows_official_headlines_and_reuses_them_when_the_source_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::SOURCE.'/' => Http::sequence()->push($this->listing())->pushStatus(503),
        ]);

        $this->get('/website-preview')
            ->assertOk()
            ->assertSee('French teaching workshop')
            ->assertSee('/website-preview/news/story/105')
            ->assertSee(self::SOURCE.'/picture_servic/105.jpg')
            ->assertDontSee('Unrelated promotion');

        $this->get('/website-preview/news')->assertOk()->assertSee('French teaching workshop');
        Http::assertSentCount(1);

        $this->travel(16)->minutes();
        $this->get('/website-preview')->assertOk()->assertSee('French teaching workshop');
        Http::assertSentCount(2);
    }

    public function test_homepage_uses_saved_official_stories_if_the_source_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::SOURCE.'/' => Http::response('Unavailable', 503)]);

        $this->get('/website-preview')
            ->assertOk()
            ->assertSee('Diploma in French: Batch A 2026')
            ->assertSee('/website-preview/news/diploma-french-2026')
            ->assertSee('/media/news-78-announcement.jpg');
    }

    public function test_a_live_story_has_an_internal_page_with_escaped_article_text(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::SOURCE.'/' => Http::response($this->listing()),
            self::SOURCE.'/newsletter/105/news' => Http::response('<div class="entry-content"><p>Registration is open for teachers.</p><p>&lt;img src=x onerror=alert(1)&gt;</p></div>'),
        ]);

        $this->get('/website-preview/news/story/105')
            ->assertOk()
            ->assertSee('French teaching workshop')
            ->assertSee('Registration is open for teachers.')
            ->assertSee('<img src=x onerror=alert(1)>')
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_an_unlisted_story_does_not_trigger_an_article_fetch(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::SOURCE.'/' => Http::response($this->listing())]);

        $this->get('/website-preview/news/story/106')->assertNotFound();
        Http::assertSentCount(1);
    }

    private function listing(): string
    {
        return <<<'HTML'
            <div class="swiper latest-news-swiper">
                <div class="news-card">
                    <a class="news-image" style="background-image:url('/picture_servic/105.jpg')"></a>
                    <span class="news-date">2026-10-20 08:10:00</span>
                    <h3 class="news-title"><a href="/newsletter/105/news">French teaching workshop</a></h3>
                </div>
                <div class="news-card">
                    <span class="news-date">2026-10-21 08:10:00</span>
                    <h3 class="news-title"><a href="https://example.com/newsletter/106/news">Unrelated promotion</a></h3>
                </div>
            </div>
            HTML;
    }
}

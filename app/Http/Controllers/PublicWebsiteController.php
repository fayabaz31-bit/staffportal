<?php

namespace App\Http\Controllers;

use App\Support\PublicNewsFeed;
use Illuminate\View\View;

class PublicWebsiteController extends Controller
{
    public function home(PublicNewsFeed $news): View
    {
        return view('public.home', ['newsStories' => $this->storiesForListing($news)]);
    }

    public function news(PublicNewsFeed $news): View
    {
        return view('public.news', ['newsStories' => $this->storiesForListing($news)]);
    }

    public function article(PublicNewsFeed $news, int $id): View
    {
        $article = $news->article($id);

        abort_if($article === null, 404);

        return view('public.news-from-source', $article);
    }

    /** @return list<array{id: int, title: string, date: string, image: string|null, display_date: string, url: string}> */
    private function storiesForListing(PublicNewsFeed $news): array
    {
        return array_map(static function (array $story): array {
            $story['display_date'] = date('j F Y', strtotime($story['date']));
            $story['url'] = match ($story['id']) {
                78 => route('website.news.diploma-french-2026'),
                77 => route('website.news.certificate-french-2026'),
                73 => route('website.news.french-embassy-technical-meeting'),
                default => route('website.news.story', $story['id']),
            };

            return $story;
        }, $news->latest());
    }
}

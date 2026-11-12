<?php

namespace App\Support;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * @phpstan-type NewsStory array{id: int, title: string, date: string, image: string|null}
 */
class PublicNewsFeed
{
    private const SOURCE = 'https://www.frenchvillage.edu.ng';

    private const SNAPSHOT = [
        78 => ['Diploma in French: Batch A 2026', '2026-06-24', 'news-78-announcement.jpg'],
        77 => ['Certificate in French: Third Batch 2026', '2026-06-24', 'news-77-announcement.jpg'],
        76 => ['Online French programme', '2026-04-23', 'news-76-announcement.jpg'],
        73 => ['French Village hosts French Embassy officials for a 3-day technical meeting', '2026-04-15', 'news-73-meeting.jpg'],
        67 => ['Learn French the easy way', '2026-01-29', 'news-67-announcement.jpg'],
        21 => ['Customised French programmes', '2021-03-26', 'news-21-announcement.jpg'],
    ];

    /** @return list<NewsStory> */
    public function latest(): array
    {
        return Cache::remember('public-news.latest', 900, function (): array {
            $stories = $this->fetchLatest();

            if ($stories !== []) {
                Cache::put('public-news.last-successful', $stories, now()->addDays(7));

                return $stories;
            }

            return Cache::get('public-news.last-successful', $this->snapshot());
        });
    }

    /** @return array{story: NewsStory, paragraphs: list<string>, source: string}|null */
    public function article(int $id): ?array
    {
        return Cache::remember('public-news.article.'.$id, 3600, function () use ($id): ?array {
            foreach ([...$this->latest(), ...$this->snapshot()] as $story) {
                if ($story['id'] !== $id) {
                    continue;
                }

                $path = '/newsletter/'.$id.'/news';
                $page = $this->fetchPage($path);
                $paragraphs = [];
                $content = $page?->query('//*[contains(concat(" ", normalize-space(@class), " "), " entry-content ")]')?->item(0);

                if ($content instanceof DOMElement) {
                    foreach ($page->query('.//p | .//h2 | .//h3 | .//h4 | .//li[not(.//p)]', $content) as $paragraph) {
                        $text = $this->plainText($paragraph->textContent);

                        if ($text !== '') {
                            $paragraphs[] = $text;
                        }
                    }

                    if ($paragraphs === []) {
                        $text = $this->plainText($content->textContent);

                        if ($text !== '') {
                            $paragraphs[] = $text;
                        }
                    }
                }

                return ['story' => $story, 'paragraphs' => $paragraphs, 'source' => self::SOURCE.$path];
            }

            return null;
        });
    }

    /** @return list<NewsStory> */
    private function fetchLatest(): array
    {
        $page = $this->fetchPage('/');

        if ($page === null) {
            return [];
        }

        $stories = [];

        foreach ($page->query('//*[contains(concat(" ", normalize-space(@class), " "), " latest-news-swiper ")]//*[contains(concat(" ", normalize-space(@class), " "), " news-card ")]') as $card) {
            $titleLink = $page->query('.//*[contains(concat(" ", normalize-space(@class), " "), " news-title ")]/a', $card)->item(0);

            if (! $titleLink instanceof DOMElement || ! preg_match('~^(?:https://www\.frenchvillage\.edu\.ng)?/newsletter/([1-9][0-9]{0,8})/news/?$~D', $titleLink->getAttribute('href'), $match)) {
                continue;
            }

            $id = (int) $match[1];
            $title = $this->plainText($titleLink->textContent);
            $dateText = $page->evaluate('string(.//*[contains(concat(" ", normalize-space(@class), " "), " news-date ")])', $card);
            $date = substr(trim($dateText), 0, 10);
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

            if ($title === '' || $parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
                continue;
            }

            $image = isset(self::SNAPSHOT[$id]) ? '/media/'.self::SNAPSHOT[$id][2] : null;

            if ($image === null) {
                $style = $page->evaluate('string(.//a[contains(concat(" ", normalize-space(@class), " "), " news-image ")]/@style)', $card);

                if (preg_match('~url\(\s*[\'"]?(/picture_servic/[a-z0-9_-]+\.(?:jpe?g|png|webp))[\'"]?\s*\)~i', $style, $imageMatch)) {
                    $image = self::SOURCE.$imageMatch[1];
                }
            }

            $stories[$id] = ['id' => $id, 'title' => $title, 'date' => $date, 'image' => $image];

            if (count($stories) === 6) {
                break;
            }
        }

        return array_values($stories);
    }

    /** @return list<NewsStory> */
    private function snapshot(): array
    {
        $stories = [];

        foreach (self::SNAPSHOT as $id => [$title, $date, $image]) {
            $stories[] = ['id' => $id, 'title' => $title, 'date' => $date, 'image' => '/media/'.$image];
        }

        return $stories;
    }

    private function fetchPage(string $path): ?DOMXPath
    {
        try {
            $response = Http::accept('text/html')
                ->connectTimeout(2)
                ->timeout(4)
                ->withoutRedirecting()
                ->get(self::SOURCE.$path);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || $response->body() === '' || strlen($response->body()) > 4_000_000) {
            return null;
        }

        $document = new DOMDocument;

        if (! $document->loadHTML('<?xml encoding="UTF-8">'.$response->body(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return null;
        }

        return new DOMXPath($document);
    }

    private function plainText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}

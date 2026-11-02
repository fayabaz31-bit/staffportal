<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class BladeTemplateSyntaxTest extends TestCase
{
    public function test_blade_templates_do_not_use_inline_php_directives(): void
    {
        $viewPath = dirname(__DIR__, 2).'/resources/views';
        $templates = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewPath, FilesystemIterator::SKIP_DOTS),
        );
        $invalidTemplates = [];

        /** @var SplFileInfo $template */
        foreach ($templates as $template) {
            if ($template->getExtension() !== 'php') {
                continue;
            }

            if (preg_match('/@php\s*\(/', (string) file_get_contents($template->getPathname())) === 1) {
                $invalidTemplates[] = str_replace('\\', '/', $template->getPathname());
            }
        }

        $this->assertSame(
            [],
            $invalidTemplates,
            'Use @php ... @endphp blocks because inline @php(...) directives compile to invalid PHP.',
        );
    }
}

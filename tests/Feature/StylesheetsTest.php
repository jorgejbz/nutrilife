<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Hojas de estilo (config/stylesheets.php): todas existen, las @media solo
 * viven en public/css/responsive y cada sección las carga en orden.
 */
class StylesheetsTest extends TestCase
{
    private function allSheets(): array
    {
        $files = config('stylesheets.modules.diet-plans');

        foreach (config('stylesheets.sections') as $section) {
            $files = array_merge($files, $section['base'], array_values($section['themes']), $section['responsive']);
        }

        return array_unique($files);
    }

    public function test_every_configured_sheet_exists_and_every_sheet_is_configured(): void
    {
        $configured = $this->allSheets();

        foreach ($configured as $file) {
            $this->assertFileExists(public_path('css/'.$file));
        }

        $onDisk = collect(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(public_path('css'), \FilesystemIterator::SKIP_DOTS)))
            ->map(fn ($f) => str_replace(DIRECTORY_SEPARATOR, '/', substr($f->getPathname(), strlen(public_path('css')) + 1)))
            ->filter(fn ($f) => str_ends_with($f, '.css'))
            ->values()->all();

        $this->assertSame([], array_values(array_diff($onDisk, $configured)), 'Archivos de public/css que no están en config/stylesheets.php');
        $this->assertSame([], array_values(array_diff($configured, $onDisk)), 'Hojas configuradas que no existen');
    }

    public function test_media_queries_only_live_in_responsive_files(): void
    {
        foreach ($this->allSheets() as $file) {
            $css = file_get_contents(public_path('css/'.$file));

            if (str_starts_with($file, 'responsive/')) {
                continue;
            }

            $this->assertStringNotContainsString('@media', $css, $file);
            $this->assertStringNotContainsString('@container', $css, $file);
        }
    }

    public function test_sections_load_base_then_themes_then_responsive(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $pos = fn (string $file) => strpos($html, 'css/'.$file);
        $login = config('stylesheets.sections.login');

        $this->assertLessThan($pos($login['themes']['light']), $pos(end($login['base'])));
        $this->assertLessThan($pos($login['responsive'][0]), $pos($login['themes']['dark']));
        $this->assertStringContainsString('data-theme-stylesheet="dark"', $html);
        $this->assertStringNotContainsString('css/login.css', $html);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for all public pages and languages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');

        $baseUrl = config('app.url');
        
        // Supported languages
        $languages = ['en', 'es', 'fr', 'ar', 'hi', 'pt-br', 'sw'];

        // Public pages (path => [priority, changefreq])
        $pages = [
            '' => [1.0, 'weekly'],              // Home/Landing page
            'pricing' => [0.8, 'weekly'],        // Pricing page
            'features' => [0.8, 'weekly'],       // Features page
            'contact' => [0.6, 'monthly'],       // Contact page
            'privacy' => [0.5, 'monthly'],       // Privacy policy
            'terms' => [0.5, 'monthly'],         // Terms of service
        ];

        // Start building XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Add default language (English) pages
        foreach ($pages as $page => $settings) {
            list($priority, $changefreq) = $settings;
            $url = $page ? $baseUrl . '/' . $page : $baseUrl;
            $lastmod = now()->toAtomString();
            
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $priority . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        // Add all language variations
        foreach ($languages as $lang) {
            foreach ($pages as $page => $settings) {
                list($priority, $changefreq) = $settings;
                $url = $page ? "{$baseUrl}/{$lang}/{$page}" : "{$baseUrl}/{$lang}";
                $lastmod = now()->toAtomString();
                
                // Slightly lower priority for non-English pages
                $langPriority = max(0.1, $priority - 0.1);
                
                $xml .= '  <url>' . PHP_EOL;
                $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
                $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
                $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . PHP_EOL;
                $xml .= '    <priority>' . $langPriority . '</priority>' . PHP_EOL;
                $xml .= '  </url>' . PHP_EOL;
            }
        }

        $xml .= '</urlset>';

        // Write sitemap to public directory
        File::put(public_path('sitemap.xml'), $xml);

        $urlCount = count($pages) * (1 + count($languages));
        
        $this->info('✓ Sitemap generated successfully at public/sitemap.xml');
        $this->info('✓ Total URLs: ' . $urlCount);

        return Command::SUCCESS;
    }
}

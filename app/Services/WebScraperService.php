<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class WebScraperService
{
    /**
     * Scrape the content from a given URL.
     *
     * @param string $url
     * @return string
     */
    public function scrape(string $url): string
    {
        try {
            $response = Http::get($url);

            if ($response->failed()) {
                return "Error: Could not retrieve content from the URL.";
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            // Extract the body content, or fall back to all text
            $bodyNode = $crawler->filter('body');
            if ($bodyNode->count() > 0) {
                // Remove script and style tags to clean up the content
                $bodyNode->filter('script, style')->each(function (Crawler $crawler) {
                    foreach ($crawler as $node) {
                        $node->parentNode->removeChild($node);
                    }
                });
                $content = $bodyNode->text();
            } else {
                $content = $crawler->text();
            }
            
            // Normalize whitespace and limit the length to avoid excessive token usage
            $cleanedContent = preg_replace('/\s+/', ' ', $content);
            return substr($cleanedContent, 0, 15000); 

        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}
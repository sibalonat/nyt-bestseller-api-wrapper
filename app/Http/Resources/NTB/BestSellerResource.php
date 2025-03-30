<?php

namespace App\Http\Resources\NTB;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BestSellerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            'title' => $this->getSafeTitle(),
            'author' => $this->resource['author'] ?? null,
            'isbn' => $this->getIsbn(),
            'description' => $this->resource['description'] ?? null,
            'publisher' => $this->resource['publisher'] ?? null,
            'rank' => $this->getRank(),
            'weeks_on_list' => $this->getWeeksOnList(),
            'created_at' => now(),
        ];
    }

    /**
     * Clean and sanitize title to prevent XSS and SQL injection
     *
     * @return string|null
     */
    private function getSafeTitle(): ?string
    {
        if (!isset($this->resource['title']) || !is_string($this->resource['title'])) {
            return null;
        }

        $title = $this->resource['title'];

        // Remove any script tags or dangerous HTML
        $title = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $title);

        // Remove special characters from beginning and end of title
        $title = preg_replace('/^[\'",.\-_#@!%^&*()\\\\]+/', '', $title);
        $title = preg_replace('/[\'",.\-_#@!%^&*()\\\\]+$/', '', $title);

        // Balance parentheses - if we have unbalanced ones, remove them
        if (substr_count($title, '(') != substr_count($title, ')')) {
            $title = str_replace(['(', ')'], '', $title);
        }

        // Replace sequences like '' with a proper double quote
        $title = preg_replace('/\'\'/', '"', $title);

        // Remove sequences of dashes (3 or more)
        $title = preg_replace('/---+/', '', $title);

        // Normalize quotes - use proper typographic quotes when appropriate
        $title = str_replace(['""', '\'\''], ['"', '"'], $title);

        // Prevent SQL injection by escaping special characters
        $title = str_replace(['\\', "\0", "\n", "\r", "\x1a"],
                            ['\\\\', '\\0', '\\n', '\\r', '\\Z'], $title);

        // Final trim of whitespace
        return trim($title);
    }

    /**
     * Get ISBN from nested structure
     */
    private function getIsbn()
    {
        if (!isset($this->resource['isbns']) || empty($this->resource['isbns'])) {
            return null;
        }

        return $this->resource['isbns'][0]['isbn13'] ??
               $this->resource['isbns'][0]['isbn10'] ??
               null;
    }

    /**
     * Get rank from ranks_history
     */
    private function getRank()
    {
        if (!isset($this->resource['ranks_history']) || empty($this->resource['ranks_history'])) {
            return null;
        }

        return $this->resource['ranks_history'][0]['rank'] ?? null;
    }

    /**
     * Get weeks_on_list from ranks_history
     */
    private function getWeeksOnList()
    {
        if (!isset($this->resource['ranks_history']) || empty($this->resource['ranks_history'])) {
            return null;
        }

        return $this->resource['ranks_history'][0]['weeks_on_list'] ?? null;
    }

}

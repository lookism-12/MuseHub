<?php

namespace App\Service;

class ContentFilter
{
    private array $bannedWords = [
        'spam', 'scam', 'hack', // Add more as needed
    ];

    private array $suspiciousPatterns = [
        '/http[s]?:\/\/[^\s]+/', // URLs
        '/[A-Z]{10,}/', // Excessive caps
    ];

    public function filterContent(string $content): array
    {
        $issues = [];
        $filteredContent = $content;

        // Check for banned words
        foreach ($this->bannedWords as $word) {
            if (stripos($content, $word) !== false) {
                $issues[] = "Contenu suspect détecté: mot interdit";
                $filteredContent = str_ireplace($word, str_repeat('*', strlen($word)), $filteredContent);
            }
        }

        // Check for suspicious patterns
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $issues[] = "Pattern suspect détecté";
            }
        }

        // Check length (too short might be spam)
        if (strlen(trim($content)) < 3) {
            $issues[] = "Contenu trop court";
        }

        return [
            'isValid' => empty($issues),
            'issues' => $issues,
            'filteredContent' => $filteredContent,
        ];
    }

    public function isContentValid(string $content): bool
    {
        $result = $this->filterContent($content);
        return $result['isValid'];
    }

    public function getFilteredContent(string $content): string
    {
        $result = $this->filterContent($content);
        return $result['filteredContent'];
    }
}


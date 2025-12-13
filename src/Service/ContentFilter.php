<?php

namespace App\Service;

class ContentFilter
{
    // Comprehensive list of bad words in French and English
    private array $bannedWords = [
        // English bad words
        'stupid', 'idiot', 'moron', 'dumb', 'asshole', 'bastard', 'bitch', 'shit', 'fuck', 'damn',
        'crap', 'jerk', 'loser', 'fool', 'retard', 'dick', 'pussy', 'cunt', 'whore', 'slut',
        'nigger', 'faggot', 'chink', 'spic', 'kike', 'wop', 'gook',

        // French bad words
        'merdique', 'con', 'connard', 'salope', 'pute', 'enculé', 'bordel', 'merde', 'putain',
        'salaud', 'ordure', 'crétin', 'imbécile', 'abruti', 'débil', 'taré', 'fou', 'malade',
        'sanglant', 'maudit', 'tabarnak', 'osti', 'calisse', 'câlisse', 'tabarnouche', 'sacrament',
        'viarge', 'bâtard', 'chien', 'chienne', 'grosse', 'gros', 'laid', 'laide', 'nul', 'nulle',

        // Additional offensive terms
        'spam', 'scam', 'hack', 'porn', 'sex', 'nude', 'naked', 'tits', 'boobs', 'cock', 'dick',
        'pénis', 'vagin', 'cul', 'fesse', 'sexe', 'baise', 'branler', 'sucer', 'enculer',
        'sodomie', 'pédophile', 'violeur', 'meurtrier', 'terroriste', 'raciste', 'homophobe',
        'transphobe', 'sexiste', 'misogyne', 'patriarcat', 'féminazi', 'incel', 'chad'
    ];

    private array $spamPatterns = [
        // Excessive repetition
        '/(.)\1{10,}/', // Same character repeated 10+ times
        '/(\b\w+\b)(\s+\1){3,}/', // Same word repeated 3+ times

        // Common spam phrases
        '/\b(?:free|cheap|buy now|limited time|urgent|act now)\b/i',
        '/\b(?:viagra|casino|lottery|winner|prize)\b/i',

        // Excessive URLs or email patterns
        '/(?:https?:\/\/[^\s]+){3,}/', // 3+ URLs
        '/\b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}\b/i', // Email addresses (could be spam)

        // All caps with excessive exclamation
        '/[A-Z\s]{20,}[!]{2,}/',

        // Gibberish patterns (random characters)
        '/[^\w\s]{5,}/', // 5+ non-word characters in a row
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

    /**
     * Check if content contains bad words (for moderation, not blocking)
     * Returns detailed information about bad words found
     */
    public function checkForBadWords(string $content): array
    {
        $foundBadWords = [];
        $normalizedContent = $this->normalizeText($content);

        // Debug: Check if content contains "stupid"
        $containsStupid = stripos($normalizedContent, 'stupid') !== false;

        foreach ($this->bannedWords as $badWord) {
            // Simple case-insensitive check first
            if (stripos($normalizedContent, $badWord) !== false) {
                $foundBadWords[] = $badWord;
            }
        }

        return [
            'containsBadWords' => !empty($foundBadWords),
            'foundBadWords' => array_unique($foundBadWords),
            'badWordsCount' => count($foundBadWords),
            'debug_contains_stupid' => $containsStupid,
            'debug_normalized' => $normalizedContent,
        ];
    }

    /**
     * Normalize text for better bad word detection
     */
    private function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Replace common leet speak and variations
        $replacements = [
            '4' => 'a', '@' => 'a', '3' => 'e', '1' => 'i', '!' => 'i',
            '0' => 'o', '$' => 's', '5' => 's', '7' => 't', '2' => 'z',
            '9' => 'g', '6' => 'g', '8' => 'b',
        ];

        $text = strtr($text, $replacements);

        // Remove extra spaces and normalize
        $text = preg_replace('/\s+/', ' ', trim($text));

        return $text;
    }

    /**
     * Generate regex patterns for bad word variations
     */
    private function generateBadWordVariations(string $word): string
    {
        // Create pattern that allows for:
        // - Repeated letters (e.g., "stuuupid")
        // - Common substitutions
        // - Word boundaries

        $escaped = preg_quote($word, '/');
        $pattern = '';

        // Build pattern character by character
        for ($i = 0; $i < strlen($escaped); $i++) {
            $char = $escaped[$i];
            // Allow the character to be repeated 1 or more times
            $pattern .= $char . '+';
        }

        return '/\b' . $pattern . '\b/ui';
    }

    /**
     * Get the current list of banned words (for admin purposes)
     */
    public function getBannedWords(): array
    {
        return $this->bannedWords;
    }

    /**
     * Add a word to the banned list (for dynamic updates)
     */
    public function addBannedWord(string $word): void
    {
        $word = strtolower(trim($word));
        if (!in_array($word, $this->bannedWords)) {
            $this->bannedWords[] = $word;
        }
    }

    /**
     * Remove a word from the banned list
     */
    public function removeBannedWord(string $word): void
    {
        $word = strtolower(trim($word));
        $key = array_search($word, $this->bannedWords);
        if ($key !== false) {
            unset($this->bannedWords[$key]);
            $this->bannedWords = array_values($this->bannedWords); // Reindex
        }
    }
}

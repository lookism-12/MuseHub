<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommentModerationService
{
    private const TOXICITY_THRESHOLD = 0.75;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openaiApiKey,
        private string $openaiApiUrl,
        private LoggerInterface $logger,
        private float $toxicityThreshold = self::TOXICITY_THRESHOLD
    ) {
    }

    /**
     * Check if content is toxic using OpenAI Moderation API
     * 
     * @param string $content The content to analyze
     * @return array ['isToxic' => bool, 'score' => float, 'error' => ?string]
     */
    public function checkToxicity(string $content): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->openaiApiUrl . '/moderations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'input' => $content,
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            if (!isset($data['results'][0])) {
                throw new \Exception('Invalid API response');
            }

            $result = $data['results'][0];
            $flagged = $result['flagged'] ?? false;

            // Get the highest category score for reporting
            $categories = $result['category_scores'] ?? [];
            $maxScore = 0.0;

            foreach ($categories as $category => $score) {
                if ($score > $maxScore) {
                    $maxScore = $score;
                }
            }

            $this->logger->info('OpenAI Moderation check completed', [
                'flagged' => $flagged,
                'max_score' => $maxScore,
                'categories' => $result['categories'] ?? [],
                'content_length' => strlen($content),
            ]);

            return [
                'isToxic' => $flagged,
                'score' => $maxScore,
                'error' => null,
                'categories' => $result['categories'] ?? [],
            ];
        } catch (\Exception $e) {
            // Log error but don't block comment creation
            $this->logger->error('OpenAI Moderation API check failed', [
                'error' => $e->getMessage(),
                'content_length' => strlen($content),
            ]);

            // Fail open: allow comment if API is unavailable
            return [
                'isToxic' => false,
                'score' => 0.0,
                'error' => 'Moderation service temporarily unavailable',
                'categories' => [],
            ];
        }
    }

    /**
     * Get a user-friendly error message for toxic content
     */
    public function getToxicityErrorMessage(float $score, array $categories = []): string
    {
        $flaggedCategories = array_keys(array_filter($categories));

        if (!empty($flaggedCategories)) {
            $categoryList = implode(', ', $flaggedCategories);
            return sprintf(
                'Your content was flagged for: %s. Please revise your message to be more respectful.',
                $categoryList
            );
        }

        return sprintf(
            'Your content was flagged as potentially inappropriate (score: %.2f). Please revise your message to be more respectful.',
            $score
        );
    }

    /**
     * Set custom toxicity threshold
     */
    public function setToxicityThreshold(float $threshold): void
    {
        $this->toxicityThreshold = max(0.0, min(1.0, $threshold));
    }

    /**
     * Get current toxicity threshold
     */
    public function getToxicityThreshold(): float
    {
        return $this->toxicityThreshold;
    }
}

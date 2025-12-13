<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour le chatbot de MuseHub
 * Gère les interactions avec l'assistant virtuel de la communauté
 */
class ChatbotController extends AbstractController
{
    public function __construct(
        private ChatbotService $chatbotService
    ) {}

    /**
     * Route POST /chatbot
     * Reçoit un message utilisateur et retourne une réponse JSON
     */
    #[Route('/chatbot', name: 'chatbot_message', methods: ['POST'])]
    public function handleMessage(Request $request): JsonResponse
    {
        // Récupérer le message depuis la requête JSON
        $data = json_decode($request->getContent(), true);

        if (!isset($data['message']) || empty(trim($data['message']))) {
            return new JsonResponse([
                'error' => 'Le message est requis et ne peut pas être vide.'
            ], 400);
        }

        $userMessage = trim($data['message']);

        // Validation de base : longueur maximale
        if (strlen($userMessage) > 500) {
            return new JsonResponse([
                'error' => 'Le message est trop long (maximum 500 caractères).'
            ], 400);
        }

        try {
            // Obtenir la réponse du service chatbot
            $response = $this->chatbotService->getResponse($userMessage);

            return new JsonResponse([
                'response' => $response,
                'timestamp' => date('c') // Format ISO 8601
            ]);

        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse d'erreur
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors du traitement de votre message.'
            ], 500);
        }
    }
}

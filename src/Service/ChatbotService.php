<?php

namespace App\Service;

/**
 * Service de chatbot simple pour MuseHub
 * Fournit des réponses prédéfinies aux questions fréquentes de la communauté
 */
class ChatbotService
{
    /**
     * Liste des questions et réponses prédéfinies
     */
    private array $faq = [
        // Publication et contenu
        'comment publier' => 'Pour publier sur MuseHub, connectez-vous et utilisez le formulaire en haut de la page communauté. Vous pouvez partager du texte, des images et choisir une catégorie.',
        'comment ajouter une image' => 'Vous pouvez ajouter une image en uploadant un fichier ou en fournissant une URL d\'image dans le formulaire de publication.',
        'categories' => 'Les catégories disponibles sont : News (actualités), Questions (discussions), et Memes (humour).',
        'règles publication' => 'Respectez les autres membres, évitez le contenu offensant ou illégal. Les publications sont modérées automatiquement.',

        // Commentaires
        'comment commenter' => 'Cliquez sur "Commenter" sous une publication et écrivez votre message. Vous pouvez aussi répondre aux commentaires existants.',
        'limite commentaires' => 'Il y a une limite de 5 commentaires par minute pour éviter le spam.',

        // Réactions
        'comment liker' => 'Cliquez sur le bouton "J\'aime" sous une publication pour montrer votre appréciation.',
        'comment disliker' => 'Cliquez sur "Je n\'aime pas" si le contenu ne vous convient pas, mais utilisez cette fonctionnalité avec modération.',

        // Communauté
        'qu\'est-ce que musehub' => 'MuseHub est une plateforme communautaire pour les artistes et amateurs d\'art. Partagez vos créations, découvrez de nouveaux talents et discutez avec la communauté.',
        'comment s\'inscrire' => 'Cliquez sur "S\'inscrire" dans le menu et remplissez le formulaire avec vos informations.',
        'mot de passe oublié' => 'Utilisez le lien "Mot de passe oublié" sur la page de connexion pour réinitialiser votre mot de passe.',

        // Modération
        'signalement' => 'Si vous voyez un contenu inapproprié, contactez un administrateur ou utilisez les outils de modération.',
        'modération' => 'MuseHub utilise une modération automatique pour filtrer les contenus offensants. Les administrateurs peuvent aussi intervenir manuellement.',

        // Aide générale
        'aide' => 'Je peux vous aider avec : publication, commentaires, réactions, inscription, modération. Posez-moi une question spécifique !',
        'bonjour' => 'Bonjour ! Je suis l\'assistant virtuel de MuseHub. Comment puis-je vous aider aujourd\'hui ?',
        'salut' => 'Salut ! Je suis là pour répondre à vos questions sur MuseHub. Que souhaitez-vous savoir ?',
        'au revoir' => 'Au revoir ! N\'hésitez pas à revenir si vous avez d\'autres questions.',
        'merci' => 'De rien ! Je suis là pour aider la communauté MuseHub.',
    ];

    /**
     * Recherche une réponse basée sur les mots-clés du message utilisateur
     *
     * @param string $message Le message de l'utilisateur
     * @return string La réponse appropriée ou une réponse par défaut
     */
    public function getResponse(string $message): string
    {
        // Normaliser le message (minuscules, supprimer la ponctuation)
        $normalizedMessage = strtolower(trim($message));
        $normalizedMessage = preg_replace('/[^\w\s]/u', '', $normalizedMessage);

        // Chercher des correspondances exactes d'abord
        foreach ($this->faq as $question => $answer) {
            if (str_contains($normalizedMessage, $question)) {
                return $answer;
            }
        }

        // Si pas de correspondance exacte, chercher des mots-clés individuels
        $words = explode(' ', $normalizedMessage);
        foreach ($words as $word) {
            if (strlen($word) < 3) continue; // Ignorer les mots trop courts

            foreach ($this->faq as $question => $answer) {
                if (str_contains($question, $word)) {
                    return $answer;
                }
            }
        }

        // Réponse par défaut si aucune correspondance
        return 'Désolé, je n\'ai pas compris votre question. Essayez de reformuler ou consultez l\'aide générale en tapant "aide".';
    }

    /**
     * Retourne toutes les questions disponibles (pour debug/admin)
     *
     * @return array Liste des questions supportées
     */
    public function getAvailableQuestions(): array
    {
        return array_keys($this->faq);
    }
}

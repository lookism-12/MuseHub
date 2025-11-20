<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RoleAssigner;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private RoleAssigner $roleAssigner
    ) {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user exists
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return new JsonResponse(['error' => 'User already exists'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username'] ?? $data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setBio($data['bio'] ?? null);
        $user->setAvatarUrl($data['avatar_url'] ?? null);

        // Assign role
        $requestedRole = $data['role'] ?? null;
        $this->roleAssigner->assignRole($user, $requestedRole);

        $this->userRepository->save($user, true);

        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'uuid' => $user->getUuid(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
            ],
        ], Response::HTTP_CREATED);
    }


    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'uuid' => $user->getUuid(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'bio' => $user->getBio(),
            'avatar_url' => $user->getAvatarUrl(),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}


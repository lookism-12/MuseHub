<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Form\ProfileType;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag,
        private SluggerInterface $slugger,
        private ImageUploadService $imageUploadService
    ) {
    }

    #[Route('', name: 'user_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile instanceof UploadedFile) {
                $uploadDir = (string) $this->parameterBag->get('app.user_avatar_directory');
                $filesystem = new Filesystem();

                if (!$filesystem->exists($uploadDir)) {
                    $filesystem->mkdir($uploadDir, 0775);
                }

                $originalName = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME) ?: 'avatar';
                $safeFilename = (string) $this->slugger->slug($originalName);
                $extension = $avatarFile->guessExtension() ?: $avatarFile->getClientOriginalExtension() ?: 'bin';
                $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);

                try {
                    $avatarFile->move($uploadDir, $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Le téléversement de la photo de profil a échoué. Veuillez réessayer.');
                    return $this->redirectToRoute('user_profile');
                }

                $previousAvatar = $user->getAvatarUrl();
                if ($previousAvatar && str_starts_with($previousAvatar, '/uploads/avatars/')) {
                    $previousPath = $uploadDir . DIRECTORY_SEPARATOR . basename($previousAvatar);
                    if (is_file($previousPath)) {
                        $filesystem->remove($previousPath);
                    }
                }

                $user->setAvatarUrl('/uploads/avatars/' . $newFilename);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('user_profile');
        }

        $uuid = $user->getUuid();
        $stats = [
            'artworks' => $this->entityManager->getRepository(Artwork::class)->count(['artistUuid' => $uuid]),
            'posts' => $this->entityManager->getRepository(Post::class)->count(['authorUuid' => $uuid]),
            'interactions' => $this->entityManager->getRepository(Comment::class)->count(['commenterUuid' => $uuid]),
        ];

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'stats' => $stats,
        ]);
    }

    #[Route('/api/avatar', name: 'api_upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $file = $request->files->get('avatar');
        if (!$file) {
            return $this->json(['error' => 'No avatar file provided'], 400);
        }

        try {
            $avatarUrl = $this->imageUploadService->uploadAvatar($file);
            $user->setAvatarUrl($avatarUrl);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => $avatarUrl,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}


<?php

namespace App\Controller;

use App\Entity\Community;
use App\Form\CommunityType;
use App\Repository\CommunityRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/community')]
class CommunityController extends AbstractController
{
    // Removed index route to avoid conflict with FrontOfficeController
    // The main community page (posts feed) is handled by FrontOfficeController::community()

    #[Route('/new', name: 'app_community_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $community = new Community();
        $form = $this->createForm(CommunityType::class, $community);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $community->setCreatorId($user ? $user->getId() : null);
            $community->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($community);
            $entityManager->flush();

            $this->addFlash('success', 'Community created successfully!');

            return $this->redirectToRoute('app_community_show', [
                'id' => $community->getId()
            ]);
        }

        return $this->render('community/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_community_show', methods: ['GET'])]
    public function show(?Community $community): Response
    {
        if (!$community) {
            throw $this->createNotFoundException('Community not found.');
        }

        return $this->render('community/show.html.twig', [
            'community' => $community,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_community_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Community $community, EntityManagerInterface $entityManager): Response
    {
        if (!$community) {
            throw $this->createNotFoundException('Community not found.');
        }

        $form = $this->createForm(CommunityType::class, $community);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $community->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Community updated successfully!');

            return $this->redirectToRoute('app_community_show', [
                'id' => $community->getId()
            ]);
        }

        return $this->render('community/edit.html.twig', [
            'form' => $form,
            'community' => $community,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_community_delete', methods: ['POST'])]
    public function delete(Request $request, ?Community $community, EntityManagerInterface $entityManager): Response
    {
        if (!$community) {
            throw $this->createNotFoundException('Community not found.');
        }

        if ($this->isCsrfTokenValid('delete' . $community->getId(), $request->request->get('_token'))) {
            $entityManager->remove($community);
            $entityManager->flush();

            $this->addFlash('success', 'Community deleted successfully!');
        }

        return $this->redirectToRoute('app_community_index');
    }

    // Admin page to list all communities
    #[Route('/admin', name: 'app_community_admin', methods: ['GET'])]
    public function admin(CommunityRepository $communityRepository): Response
    {
        $communities = $communityRepository->findAll();

        return $this->render('community/admin.html.twig', [
            'communities' => $communities,
        ]);
    }
}

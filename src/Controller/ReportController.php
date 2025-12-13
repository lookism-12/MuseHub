<?php

namespace App\Controller;

use App\Entity\Report;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/reports')]
#[IsGranted('ROLE_USER')]
class ReportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReportRepository $reportRepository,
        private PostRepository $postRepository,
        private CommentRepository $commentRepository
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['content_type'], $data['content_id'], $data['reason'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $contentType = $data['content_type'];
        $contentId = (int) $data['content_id'];
        $reason = $data['reason'];

        // Validate content type
        if (!in_array($contentType, ['post', 'comment'])) {
            return $this->json(['error' => 'Invalid content type'], Response::HTTP_BAD_REQUEST);
        }

        // Validate reason
        $validReasons = ['spam', 'harassment', 'inappropriate', 'misinformation', 'other'];
        if (!in_array($reason, $validReasons)) {
            return $this->json(['error' => 'Invalid reason'], Response::HTTP_BAD_REQUEST);
        }

        // Check if content exists
        if ($contentType === 'post') {
            $content = $this->postRepository->find($contentId);
        } else {
            $content = $this->commentRepository->find($contentId);
        }

        if (!$content) {
            return $this->json(['error' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if user already reported this content
        if ($this->reportRepository->hasUserReported($user->getUuid(), $contentType, $contentId)) {
            return $this->json(['error' => 'You have already reported this content'], Response::HTTP_BAD_REQUEST);
        }

        $report = new Report();
        $report->setReporterUuid($user->getUuid());
        $report->setContentType($contentType);
        $report->setContentId($contentId);
        $report->setReason($reason);
        $report->setDescription($data['description'] ?? null);

        $this->em->persist($report);
        $this->em->flush();

        return $this->json([
            'message' => 'Report submitted successfully. Our team will review it shortly.',
            'report_id' => $report->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/pending', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function listPending(): JsonResponse
    {
        $reports = $this->reportRepository->findPending();

        $data = array_map(fn(Report $r) => [
            'id' => $r->getId(),
            'content_type' => $r->getContentType(),
            'content_id' => $r->getContentId(),
            'reason' => $r->getReason(),
            'description' => $r->getDescription(),
            'reporter_uuid' => $r->getReporterUuid(),
            'created_at' => $r->getCreatedAt()->format('c'),
            'status' => $r->getStatus(),
        ], $reports);

        return $this->json($data);
    }

    #[Route('/{id}/review', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function review(int $id, Request $request): JsonResponse
    {
        $report = $this->reportRepository->find($id);
        if (!$report) {
            return $this->json(['error' => 'Report not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? 'dismissed'; // dismissed or action_taken

        if (!in_array($action, ['dismissed', 'action_taken'])) {
            return $this->json(['error' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $report->setStatus($action);
        $report->setReviewedAt(new \DateTimeImmutable());
        $report->setReviewedByUuid($user->getUuid());

        $this->em->flush();

        return $this->json([
            'message' => 'Report reviewed successfully',
            'status' => $action,
        ]);
    }
}

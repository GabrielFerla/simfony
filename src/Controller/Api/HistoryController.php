<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\DailyEntry;
use App\Entity\User;
use App\Repository\DailyEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/history', name: 'api_history_')]
final class HistoryController extends AbstractController
{
    public function __construct(
        private readonly DailyEntryRepository $dailyEntryRepository,
    ) {
    }

    #[Route('/recent', name: 'recent', methods: ['GET'])]
    public function recent(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $entries = $this->dailyEntryRepository->findRecentByUser($user, 7);

        return new JsonResponse(array_map($this->entryToArray(...), $entries));
    }

    #[Route('', name: 'month', methods: ['GET'])]
    public function month(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $month = $request->query->getString('month');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return new JsonResponse(
                ['errors' => ['month' => 'Formato inválido. Use YYYY-MM.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $entries = $this->dailyEntryRepository->findByUserAndMonth($user, $month);

        $summary = [
            'total_days' => count($entries),
            'completed' => 0,
            'not_completed' => 0,
            'skipped' => 0,
        ];

        foreach ($entries as $entry) {
            if ($entry->isSkipped()) {
                $summary['skipped']++;
            } elseif ($entry->isCompleted() === true) {
                $summary['completed']++;
            } elseif ($entry->isCompleted() === false) {
                $summary['not_completed']++;
            }
        }

        return new JsonResponse([
            'month' => $month,
            'entries' => array_map($this->entryToArray(...), $entries),
            'summary' => $summary,
        ]);
    }

    /** @return array<string, mixed> */
    private function entryToArray(DailyEntry $entry): array
    {
        $date = $entry->getDate();

        return [
            'id' => $entry->getId()?->toRfc4122(),
            'date' => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : null,
            'intention' => $entry->getIntention(),
            'completed' => $entry->isCompleted(),
            'skipped' => $entry->isSkipped(),
            'createdAt' => $entry->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $entry->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}

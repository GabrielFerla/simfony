<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\DailyEntry;
use App\Entity\User;
use App\Repository\DailyEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/today', name: 'api_today_')]
final class TodayController extends AbstractController
{
    public function __construct(
        private readonly DailyEntryRepository $dailyEntryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $today = $this->getTodayDateForUser($user);
        $entry = $this->dailyEntryRepository->findByUserAndDate($user, $today);

        if ($entry === null) {
            return new JsonResponse(null);
        }

        return new JsonResponse($this->entryToArray($entry));
    }

    #[Route('', name: 'post', methods: ['POST'])]
    public function post(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $constraints = new Assert\Collection([
            'intention' => [new Assert\NotBlank(), new Assert\Length(max: 65535)],
        ]);
        $violations = $this->validator->validate($data, $constraints);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $today = $this->getTodayDateForUser($user);
        $existing = $this->dailyEntryRepository->findByUserAndDate($user, $today);
        if ($existing !== null && !$existing->isSkipped()) {
            return new JsonResponse(
                ['message' => 'Já existe uma entrada para hoje.'],
                Response::HTTP_CONFLICT
            );
        }

        if ($existing !== null) {
            $existing->setIntention($data['intention']);
            $existing->setSkipped(false);
            $existing->setCompleted(null);
            $this->entityManager->flush();
            return new JsonResponse($this->entryToArray($existing), Response::HTTP_OK);
        }

        $entry = new DailyEntry();
        $entry->setUser($user);
        $entry->setDate($today);
        $entry->setIntention($data['intention']);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return new JsonResponse($this->entryToArray($entry), Response::HTTP_CREATED);
    }

    #[Route('/complete', name: 'complete', methods: ['PATCH'])]
    public function complete(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $constraints = new Assert\Collection([
            'completed' => [new Assert\NotNull(), new Assert\Type('bool')],
        ]);
        $violations = $this->validator->validate($data, $constraints);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $today = $this->getTodayDateForUser($user);
        $entry = $this->dailyEntryRepository->findByUserAndDate($user, $today);
        if ($entry === null) {
            return new JsonResponse(
                ['message' => 'Não existe entrada para hoje.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $entry->setCompleted($data['completed']);
        $this->entityManager->flush();

        return new JsonResponse($this->entryToArray($entry));
    }

    #[Route('/skip', name: 'skip', methods: ['PATCH'])]
    public function skip(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $today = $this->getTodayDateForUser($user);
        $entry = $this->dailyEntryRepository->findByUserAndDate($user, $today);

        if ($entry === null) {
            $entry = new DailyEntry();
            $entry->setUser($user);
            $entry->setDate($today);
            $entry->setIntention('');
            $entry->setSkipped(true);
            $this->entityManager->persist($entry);
        } else {
            $entry->setSkipped(true);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->entryToArray($entry));
    }

    private function getTodayDateForUser(User $user): \DateTimeImmutable
    {
        $tz = new \DateTimeZone($user->getTimezone());
        $now = new \DateTimeImmutable('now', $tz);

        return $now->setTime(0, 0, 0);
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

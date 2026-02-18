<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/me', name: 'api_me_')]
final class MeController extends AbstractController
{
    public function __construct(
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

        return new JsonResponse($this->userToArray($user));
    }

    #[Route('', name: 'patch', methods: ['PATCH'])]
    public function patch(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $constraints = new Assert\Collection([
            'displayName' => [new Assert\Optional([new Assert\NotBlank(), new Assert\Length(max: 255)])],
            'timezone' => [new Assert\Optional([new Assert\NotBlank(), new Assert\Length(max: 64)])],
        ]);
        $violations = $this->validator->validate($data, $constraints);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['displayName'])) {
            $user->setDisplayName($data['displayName']);
        }
        if (isset($data['timezone'])) {
            $user->setTimezone($data['timezone']);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->userToArray($user));
    }

    /** @return array<string, mixed> */
    private function userToArray(User $user): array
    {
        return [
            'id' => $user->getId()?->toRfc4122(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'timezone' => $user->getTimezone(),
            'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}

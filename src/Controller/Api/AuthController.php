<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true) ?: [];
        $constraints = new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank(), new Assert\Length(min: 6)],
            'displayName' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
        ]);
        $violations = $this->validator->validate($data, $constraints);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[$v->getPropertyPath()] = $v->getMessage();
            }
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        if ($this->userRepository->findOneByEmail($email) !== null) {
            return new JsonResponse(
                ['errors' => ['email' => 'Este e-mail já está em uso.']],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setDisplayName($data['displayName']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId()?->toRfc4122(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
            ],
        ], Response::HTTP_CREATED);
    }

    //create a login method that uses the json_login bundle
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true) ?: [];

        $user = $this->userRepository->findOneByEmail($data['email']);
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Usuário não encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $token = $this->jwtManager->create($user);

        return new JsonResponse(['token' => $token]);
    }

    /**
     * Renovar token. Requer Authorization: Bearer {token} válido.
     */
    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId()?->toRfc4122(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
            ],
        ]);
    }
}

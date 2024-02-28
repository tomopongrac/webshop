<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Authentication\LoginRequest;
use App\DTO\Authentication\LoginResponse;
use App\Repository\UserRepository;
use App\Service\ValidatorService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraint;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly UserRepository $userRepository,
        private readonly ValidatorService $validatorService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $JWTTokenManager,
    ) {
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login',
        tags: ['Authentication'],
        description: 'Login a user and return a JWT token'
    )]
    #[OA\RequestBody(
        description: 'User credentials',
        required: true,
        content: new Model(type: LoginRequest::class, groups: ['login:request'])
    )]
    #[OA\Response(
        response: 200,
        description: 'User logged in',
        content: new Model(type: LoginResponse::class, groups: ['login:response'])
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials'
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error'
    )]
    public function __invoke(Request $request): Response
    {
        $loginRequest = $this->serializer->deserialize($request->getContent(), LoginRequest::class, 'json', [
            'groups' => ['login:request'],
        ]);

        $this->validatorService->validate($loginRequest, [Constraint::DEFAULT_GROUP]);

        $user = $this->userRepository->findOneBy(['email' => $loginRequest->getEmail()]);

        if (null === $user) {
            return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $loginRequest->getPassword())) {
            return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
        }

        $loginResponse = (new LoginResponse())
            ->setToken($this->JWTTokenManager->create($user))
            ->setUser($user);

        return new JsonResponse($this->serializer->serialize($loginResponse, 'json', [
            'groups' => ['login:response'],
        ]), Response::HTTP_OK, [], true);
    }
}

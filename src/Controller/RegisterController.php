<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\ValidatorService;
use Doctrine\ORM\EntityManagerInterface;
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

class RegisterController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorService $validatorService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[
        Route('/api/register', name: 'register', methods: ['POST']),
        OA\Post(
            path: '/api/register',
            tags: ['Authentication'],
            description: 'Register a new user',
        ),
        OA\RequestBody(
            description: 'User data',
            required: true,
            content: new Model(type: User::class, groups: ['user:write'])
        ),
        OA\Response(
            response: 201,
            description: 'User created',
        ),
        OA\Response(
            response: 422,
            description: 'Validation error',
        )
    ]
    public function __invoke(Request $request): Response
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json', [
            'groups' => 'user:write',
        ]);

        $this->validatorService->validate($user, [Constraint::DEFAULT_GROUP]);

        // Hash the password
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}

<?php

declare(strict_types=1);

namespace App\DTO\Authentication;

use App\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

final class LoginResponse
{
    #[Groups(['login:response'])]
    private string $token;

    #[Groups(['login:response'])]
    private User $user;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}

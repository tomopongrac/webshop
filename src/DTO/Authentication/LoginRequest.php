<?php

declare(strict_types=1);

namespace App\DTO\Authentication;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest
{
    #[Groups(['login:request'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[Groups(['login:request'])]
    #[Assert\NotBlank]
    #[Assert\Type(type: Types::STRING)]
    private string $password;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }
}

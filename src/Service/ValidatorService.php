<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiValidationException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidatorService
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @param array<int, string> $groups
     */
    public function validate(object $object, array $groups): void
    {
        $errors = $this->validator->validate($object, null, $groups);

        if (count($errors) > 0) {
            // Transform ConstraintViolationList into an array
            $errorMessages = array_map(function (ConstraintViolationInterface $violation) {
                return $violation->getMessage();
            }, iterator_to_array($errors));

            throw new ApiValidationException($errorMessages);
        }
    }
}

<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator as BaseJWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;

class JWTAuthenticator extends BaseJWTAuthenticator
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function authenticate(Request $request): Passport
    {
        // Decode JSON request
        $data = json_decode($request->getContent(), true);

        // Define validation constraints
        $constraints = new Assert\Collection([
            'email' => [
                new Assert\NotBlank(['message' => 'Email cannot be blank.']),
                new Assert\Email(['message' => 'Invalid email format.']),
            ],
            'password' => [
                new Assert\NotBlank(['message' => 'Password cannot be blank.']),
                new Assert\Length(['min' => 6, 'minMessage' => 'Password must be at least 6 characters.']),
            ],
        ]);

        // Validate data
        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Proceed with authentication (returning a Passport)
        return parent::authenticate($request);
    }
}


<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    public function __construct(EntityManagerInterface $entityManager,UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    /**
     * get the user details
     */
    public function getUserDetails(int $userId): array
    {
        $responseData=[];
        $user = $this->userRepository->find($userId);

        if (!$user) {
            $responseData = [
                'data'=>[],
                'status'=>404,
                'message'=>'',
                'error'=>'User not found'

            ];
        }
        else {
            $responseData = [
                'data'=>[
                    'name'=>$user->getName(),
                    'email'=>$user->getEmail(),
                    'role'=>$user->getRoles(),
                ],
                'status'=>200,
                'message'=>'success',
                'error'=>''

            ];
        }
        return  $responseData;
    }
}
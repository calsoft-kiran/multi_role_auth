<?php

namespace App\Controller;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

final class UserController extends AbstractController
{
    const ERRORMSG = 'User not authenticated';
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/api/common/user/profile', name: 'user_profile')]
    public function getUserProfile(): JsonResponse
    {
        try
        {
            $response='';
            $user = $this->getUser();
            if (!$user) {
                $response = $this->handleResponse([],Response::HTTP_UNAUTHORIZED,'',self::ERRORMSG);
            }
            else {
                $responseData = $this->userService->getUserDetails($user->getId());
                $response =  $this->handleResponse($responseData['data'],$responseData['status'],$responseData['message'],$responseData['error']);
            }
        }catch(Exception $e){
            return $this->handleResponse([],Response::HTTP_INTERNAL_SERVER_ERROR,'',$e->getMessage());
        }
        return $response;
        
    }

    /***
     * function to handle response of api
     */
    public function handleResponse($data,$status,$message,$error){
        return new JsonResponse(
            [
                'data'=>$data,
                'message' => $message,
                'error'=>$error,
            ],$status );
    }
}

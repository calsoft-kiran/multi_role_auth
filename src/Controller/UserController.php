<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use OpenApi\Attributes as OA;

final class UserController extends AbstractController
{
    const ERRORMSG = 'User not authenticated';
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    #[Route('/api/common/user/profile', name: 'user_profile',methods: ['GET'])]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    #[OA\Get(
        summary: "Get the profile details of logged in user!",
        security: [['bearerAuth' => []]],
        description: "This endpoint allows users get there profile details.",
        tags: ["common"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful API call will returns a user details",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "json", example: "data{}")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid JWT token"),
            new OA\Response(response: 403, description: "Access Denied"),
            new OA\Response(response: 500, description: "Internal Server Error")
        ]
    )]
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

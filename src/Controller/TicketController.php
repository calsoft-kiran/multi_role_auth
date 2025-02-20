<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use App\Service\TicketService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Http\Attribute\Security;
use OpenApi\Attributes as OA;

final class TicketController extends AbstractController
{
    const ERRORMSG = 'User not authenticated';
    const ERRORMSGTWO = 'Ticket details not found';

    private TicketService $ticketService;
    private EntityManagerInterface $entityManager;

    public function __construct(TicketService $ticketService, EntityManagerInterface $entityManager)
    {
        $this->ticketService = $ticketService;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/common/ticket/create', name: 'create_ticket', methods: ['post'])]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    #[OA\Post(
        summary: "Create new ticket!",
        security: [['bearerAuth' => []]],
        description: "This endpoint allows all users to raise ticket.",
        tags: ["common"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "title", type: "string", example: "testtitle"),
                    new OA\Property(property: "description", type: "string", example: "testdescription"),
                    new OA\Property(property: "priority", type: "string", example: "low"),
                    new OA\Property(property: "status", type: "string", example: "open"),
                    new OA\Property(property: "category", type: "string", example: "admin"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful API call will returns ticket id",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "json", example: "data{'id':'ticket_id'}")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Invalid JWT token"),
            new OA\Response(response: 403, description: "Access Denied"),
            new OA\Response(response: 500, description: "Internal Server Error")
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        try
        {
            $response='';
            $user = $this->getUser();
            if (!$user) {
                $response = $this->handleResponse([],Response::HTTP_UNAUTHORIZED,'',self::ERRORMSG);
            }
            else {
                $data = json_decode($request->getContent(), true);
                $responseData = $this->ticketService->CreateTicket($data,$user);
                $response =  $this->handleResponse($responseData['data'],$responseData['status'],$responseData['message'],$responseData['error']);
            }
        }catch(Exception $e){
            return $this->handleResponse([],Response::HTTP_INTERNAL_SERVER_ERROR,'',$e->getMessage());
        }
        return $response;
    }

    #[Route('/api/common/ticket/{id}', name: 'get_ticket_details', methods: ['get'])]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    #[OA\Get(
        summary: "get ticket details",
        security: [['bearerAuth' => []]],
        description: "This endpoint allows all users to get ticket details by id",
        tags: ["common"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "The ID of the ticket",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 123)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful API call will returns ticket details",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "json", example: "{data{'id':'ticket_id',....}}")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid JWT token"),
            new OA\Response(response: 403, description: "Access Denied"),
            new OA\Response(response: 500, description: "Internal Server Error")
        ]
    )]
    public function getTicketDetails(int $id): JsonResponse
    {
        try
        {
            $response='';
            $user = $this->getUser();
            if (!$user) {
                $response = $this->handleResponse([],Response::HTTP_UNAUTHORIZED,'',self::ERRORMSG);
            }
            else
            {
                $responseData = $this->ticketService->getTicketDetails($id);
                $response = $this->handleResponse($responseData['data'],$responseData['status'],$responseData['message'],$responseData['error']);
            }
        }catch(Exception $e){
            return $this->handleResponse([],Response::HTTP_INTERNAL_SERVER_ERROR,'',$e->getMessage());
        }
        return $response;
    }

    #[Route('/api/common/myticket', name: 'get_myticket_details', methods: ['get'])]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    #[OA\Get(
        summary: "get my ticket list",
        security: [['bearerAuth' => []]],
        description: "This endpoint allows users to get ticket details created by them",
        tags: ["common"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful API call will returns ticket list created by logged in user",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "json", example: "{data[{'id':'ticket_id',....}}]")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid JWT token"),
            new OA\Response(response: 403, description: "Access Denied"),
            new OA\Response(response: 500, description: "Internal Server Error")
        ]
    )]
    public function getMyTickets(): JsonResponse
    {
        try
        {
            $response='';
            $user = $this->getUser();
            if (!$user) {
                $response = $this->handleResponse([],Response::HTTP_UNAUTHORIZED,'',self::ERRORMSG);
            }
            else
            {
                $responseData = $this->ticketService->getUserTickets($user->getId());
                $response = $this->handleResponse($responseData['data'],$responseData['status'],$responseData['message'],$responseData['error']);
            }
        }catch(Exception $e){
            return $this->handleResponse([],Response::HTTP_INTERNAL_SERVER_ERROR,'',$e->getMessage());
        }
        return $response;
    }
    
    #[Route('/api/admin/ticket/viewall', name: 'viewall_ticket', methods: ['get'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    #[OA\Get(
        summary: "view all tickets",
        security: [['bearerAuth' => []]],
        description: "This endpoint allows admin user to view all tickets",
        tags: ["admin"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful API call will returns ticket list of all tickets",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "json", example: "{data[{'id':'ticket_id',....}}]")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid JWT token"),
            new OA\Response(response: 403, description: "Access Denied"),
            new OA\Response(response: 500, description: "Internal Server Error")
        ]
    )]
    public function viewAll(TicketRepository $ticketRepository): JsonResponse
    {
        try
        {
            $response=[];
            $user = $this->getUser();
            if (!$user) {
                $response = $this->handleResponse([],Response::HTTP_UNAUTHORIZED,'',self::ERRORMSG);
            }
            else {
                $responseData = $this->ticketService->getAllTicketDetails();
                $response = $this->handleResponse($responseData['data'],$responseData['status'],$responseData['message'],$responseData['error']);
            }
        }catch(Exception $e){
            return $this->handleResponse([],Response::HTTP_INTERNAL_SERVER_ERROR,'',$e->getMessage());
        }
        return $response;
    }

    #[Route('/api/manager/ticket/assign', name: 'assign_ticket', methods: ['post'])]
    #[IsGranted('ROLE_MANAGER')]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    #[OA\Post(
        summary: "Assign tickets",
        security: [['bearerAuth' => []]],
        description: "This endpoint allows manager user to assign ticket",
        tags: ["manager"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "ticketId", type: "integer", example: "123"),
                    new OA\Property(property: "userId", type: "integer", example: "123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful API call will returns ticket assigned id",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "json", example: "{data[{'id':'ticket_id',....}}]")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Invalid JWT token"),
            new OA\Response(response: 403, description: "Access Denied"),
            new OA\Response(response: 404, description: "Invalid ticketId or userId"),
            new OA\Response(response: 500, description: "Internal Server Error")
        ]
    )]
    public function assignTicket(Request $request): JsonResponse
    {
        try
        {
            $response='';
            $user = $this->getUser();
            if (!$user) {
                $response = $this->handleResponse([],Response::HTTP_UNAUTHORIZED,'',self::ERRORMSG);
            }
            else {
                $data = json_decode($request->getContent(), true);
                $responseData = $this->ticketService->assignTicket($data,$user);
                $response =  $this->handleResponse($responseData['data'],$responseData['status'],$responseData['message'],$responseData['error']);
            }
        }catch(Exception $e){
            return $this->handleResponse([],Response::HTTP_INTERNAL_SERVER_ERROR,'',$e->getMessage());
        }
        return $response;
    }

    #[Route('/api/user/ticket/close', name: 'close_ticket', methods: ['patch'])]
    #[IsGranted('ROLE_USER')]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    #[OA\Patch(
        summary: "close tickets",
        security: [['bearerAuth' => []]],
        description: "This endpoint allows user to close ticket which they have created",
        tags: ["user"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "ticketId", type: "integer", example: "123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful API call will returns ticket closed id",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "json", example: "{data[{'id':'ticket_id',....}}]")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Bad Request"),
            new OA\Response(response: 401, description: "Invalid JWT token"),
            new OA\Response(response: 403, description: "Access Denied"),
            new OA\Response(response: 404, description: "Invalid ticketId"),
            new OA\Response(response: 406, description: "You are not authorised to close this ticket"),
            new OA\Response(response: 500, description: "Internal Server Error")
        ]
    )]
    public function closeTicket(Request $request): JsonResponse
    {
        try
        {
            $response='';
            $user = $this->getUser();
            if (!$user) {
                $response = $this->handleResponse([],Response::HTTP_UNAUTHORIZED,'',self::ERRORMSG);
            }
            else {
                $data = json_decode($request->getContent(), true);
                $responseData = $this->ticketService->closeTicket($data,$user);
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

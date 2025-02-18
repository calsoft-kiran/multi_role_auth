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

    #[Route('/api/admin/ticket/viewall', name: 'viewall_ticket', methods: ['get'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
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

    #[Route('/api/common/myticket', name: 'get_myticket_details', methods: ['get'])]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
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

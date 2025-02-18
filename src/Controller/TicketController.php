<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Exception;
use DateTimeImmutable;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Enum\TicketCategory;
use Symfony\Component\Security\Http\Attribute\Security;


final class TicketController extends AbstractController
{
    const ERRORMSG = 'User not authenticated';
    const ERRORMSGTWO = 'Ticket details not found';
    #[Route('/api/common/ticket/create', name: 'create_ticket', methods: ['post'])]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    public function create(Request $request, EntityManagerInterface $entityManager,ValidatorInterface $validator): JsonResponse
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
                $constraints = new Assert\Collection([
                    'title' => [new Assert\NotBlank(['message' => 'Model is required.']), new Assert\Length(['min' => 3, 'max' => 100])],
                    'description' => [new Assert\NotBlank(['message' => 'Description is required.']), new Assert\Length(['min' => 3])],
                    'priority' =>[new Assert\Choice([
                        'choices' => array_map(fn(TicketPriority $s) => $s->value, TicketPriority::cases()),
                        'message' => 'Invalid priority value. Allowed values: ' . implode(', ', array_map(fn(TicketPriority $s) => $s->value, TicketPriority::cases())),
                    ])],
                    'status' => [new Assert\Choice([
                        'choices' => array_map(fn(TicketStatus $s) => $s->value, TicketStatus::cases()),
                        'message' => 'Invalid status value. Allowed values: ' . implode(', ', array_map(fn(TicketStatus $s) => $s->value, TicketStatus::cases())),
                    ])],
                    'category' => [new Assert\Choice([
                        'choices' => array_map(fn(TicketCategory $s) => $s->value, TicketCategory::cases()),
                        'message' => 'Invalid category value. Allowed values: ' . implode(', ', array_map(fn(TicketCategory $s) => $s->value, TicketCategory::cases())),
                    ])],
                ]);
            
                $errors = $validator->validate($data, $constraints);
        
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                    }
                    $response = $this->handleResponse([],Response::HTTP_BAD_REQUEST,'',$errorMessages);
                }
                else {
                    $now = new DateTimeImmutable();
                    $ticket = new Ticket();
                    $ticket->setTitle($data['title']);
                    $ticket->setDescription($data['description']);
                    $ticket->setPriority(TicketPriority::from($data['priority']));
                    $ticket->setStatus(TicketStatus::from($data['status']));
                    $ticket->setCategory(TicketCategory::from($data['category']));
                    $ticket->setCreatedAt($now);
                    $ticket->setUpdatedAt($now);
                    $ticket->setCreatedBy($user);
                    $ticket->setUpdatedBy($user);
                    $ticket->setAssignedTo($user);
                    $entityManager->persist($ticket);
                    $entityManager->flush();
                    $response = $this->handleResponse(['id' => $ticket->getId()],Response::HTTP_OK,'ticket created successfully','');
                }
            }
        }catch(Exception $e){
            return $this->handleResponse([],Response::HTTP_INTERNAL_SERVER_ERROR,'',$e->getMessage());
        }
        return $response;
    }

    #[Route('/api/common/ticket/{id}', name: 'get_ticket_details', methods: ['get'])]
    #[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
    public function getTicketDetails(int $id,TicketRepository $ticketRepository): JsonResponse
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
                $ticket = $ticketRepository->findTicketById($id);

                if (!$ticket) {
                    $response = $this->handleResponse([],404,'','Ticket not found');
                }
                else {
                    $data = [
                        'id' => $ticket->getId(),
                        'title' => $ticket->getTitle(),
                        'description' => $ticket->getDescription(),
                        'prioroty'=>$ticket->getPriority(),
                        'status'=>$ticket->getStatus(),
                        'category'=>$ticket->getCategory(),
                        'created_at'=>$ticket->getCreatedAt()

                    ];
                    $response = $this->handleResponse($data,Response::HTTP_OK,'success','');
                }
                
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
                $ticketList = $ticketRepository->findAllTickets();

                $response = $this->handleResponse($ticketList,Response::HTTP_OK,'success','');
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

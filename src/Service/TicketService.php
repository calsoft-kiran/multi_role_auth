<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use DateTimeImmutable;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Enum\TicketCategory;

class TicketService
{
    private EntityManagerInterface $entityManager;
    private TicketRepository $ticketRepository;
    private UserRepository $userRepository;
    private ValidatorInterface $validator;
    public function __construct(EntityManagerInterface $entityManager,TicketRepository $ticketRepository,ValidatorInterface $validator,UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->ticketRepository = $ticketRepository;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
    }

    /** function to create ticket */
    public function createTicket(array $data,$user):array
    {
        $responseData=[];
        $constraints = new Assert\Collection([
            'title' => [new Assert\NotBlank(['message' => 'Title is required.']), new Assert\Length(['min' => 3, 'max' => 100])],
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
    
        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData=[
                'data'=>[],
                'status'=>Response::HTTP_BAD_REQUEST,
                'message'=>'',
                'error'=>$errorMessages
            ];
        } else {
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
            $this->entityManager->persist($ticket);
            $this->entityManager->flush();

            $responseData=[
                'data'=>['id' => $ticket->getId()],
                'status'=>Response::HTTP_OK,
                'message'=>'ticket created successfully',
                'error'=>''
            ];
        }
        return $responseData;
    }

    /** function to create ticket */
    public function getTicketDetails(int $id):array
    {
        $responseData=[];
        $ticket = $this->ticketRepository->findTicketById($id);

        if (!$ticket) {
            $responseData=[
                'data'=>[],
                'status'=>Response::HTTP_NOT_FOUND,
                'message'=>'',
                'error'=>'Ticket not found'
            ];
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
            $responseData=[
                'data'=>$data,
                'status'=>Response::HTTP_OK,
                'message'=>'success',
                'error'=>''
            ];
        }
        return $responseData;
    }

    /** function to create ticket */
    public function getAllTicketDetails():array
    {
        $responseData=[];
        $ticket = $this->ticketRepository->findAllTickets();
        if (!$ticket) {
            $responseData=[
                'data'=>[],
                'status'=>Response::HTTP_NOT_FOUND,
                'message'=>'',
                'error'=>'Ticket not found'
            ];
        }
        else {
            $responseData=[
                'data'=>$ticket,
                'status'=>Response::HTTP_OK,
                'message'=>'success',
                'error'=>''
            ];
        }
        return $responseData;
    }

    /**
     * Get skills of a user by user ID
     */
    public function getUserTickets(int $userId): array
    {
       $responseData=[];
        $tickets = $this->ticketRepository->findByExampleField($userId);
        if (!$tickets) {
            $responseData=[
                'data'=>[],
                'status'=>Response::HTTP_NOT_FOUND,
                'message'=>'',
                'error'=>'Ticket not found'
            ];
        }
        else {
            $responseData=[
                'data'=>$tickets,
                'status'=>Response::HTTP_OK,
                'message'=>'success',
                'error'=>''
            ];
        }
        return $responseData;
    }

    /** function to assign ticket to user */
    public function assignTicket($data,$user)
    {
        $responseData=[];
        $constraints = new Assert\Collection([
            'ticketId' => [new Assert\NotBlank(['message' => 'TicketId is required.']), new Assert\Type(type: "integer"),new Assert\GreaterThan(0)],
            'userId' => [new Assert\NotBlank(['message' => 'userId is required.']), new Assert\Type(type: "integer"),new Assert\GreaterThan(0)]
        ]);
    
        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData=[
                'data'=>[],
                'status'=>Response::HTTP_BAD_REQUEST,
                'message'=>'',
                'error'=>$errorMessages
            ];
        } else {
            $now = new DateTimeImmutable();
            $ticket = $this->ticketRepository->findTicketById($data['ticketId']);
            $assignedTo = $this->userRepository->find($data['userId']);
            if(empty($ticket) || empty($assignedTo)){
                $responseData=[
                    'data'=>[],
                    'status'=>Response::HTTP_NOT_FOUND,
                    'message'=>'',
                    'error'=>'Invalid ticketId or userId'
                ];
            } else {
                $ticket->setUpdatedAt($now);
                $ticket->setUpdatedBy($user);
                $ticket->setAssignedTo($assignedTo);
                $this->entityManager->flush();
                $responseData=[
                    'data'=>['id' => $ticket->getId(),'updatedBy'=>$user->getId(),'assignedTo'=>$assignedTo->getId()],
                    'status'=>Response::HTTP_OK,
                    'message'=>'ticket assigned successfully',
                    'error'=>''
                ];
            }
        }
        return $responseData;
    }

    /** function to close the ticket */
    public function closeTicket($data,$user): array
    {
        $responseData=[];
        $constraints = new Assert\Collection([
            'ticketId' => [new Assert\NotBlank(['message' => 'TicketId is required.']), new Assert\Type(type: "integer"),new Assert\GreaterThan(0)]
        ]);
    
        $errors = $this->validator->validate($data, $constraints);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData=[
                'data'=>[],
                'status'=>Response::HTTP_BAD_REQUEST,
                'message'=>'',
                'error'=>$errorMessages
            ];
        } else {
            $now = new DateTimeImmutable();
            $ticket = $this->ticketRepository->findTicketById($data['ticketId']);
            if(empty($ticket)){
                $responseData=[
                    'data'=>[],
                    'status'=>Response::HTTP_NOT_FOUND,
                    'message'=>'',
                    'error'=>'Invalid ticketId'
                ];
            }
            else {
                $createdBy = $ticket->getCreatedBy();
                if($createdBy->getId() == $user->getId()){
                    $ticket->setUpdatedAt($now);
                    $ticket->setUpdatedBy($user);
                    $ticket->setStatus(TicketStatus::from('closed'));
                    $this->entityManager->flush();
                    $responseData=[
                        'data'=>['id' => $ticket->getId(),'updatedBy'=>$user->getId(),'closed by'=>$user->getId()],
                        'status'=>Response::HTTP_OK,
                        'message'=>'ticket closed successfully',
                        'error'=>''
                    ];
                } else {
                    $responseData=[
                        'data'=>[],
                        'status'=>Response::HTTP_NOT_ACCEPTABLE,
                        'message'=>'',
                        'error'=>'You are not authorised to close this ticket, make sure you are closing ticket created by you!'
                    ];
                }
            }
        }
        return $responseData;
    }
}
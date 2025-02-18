<?php
namespace App\Tests\Service;

use App\Service\TicketService;
use App\Repository\TicketRepository;
use App\Entity\Ticket;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Enum\TicketCategory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\HttpFoundation\Response;
use DateTimeImmutable;

class TicketServiceTest extends TestCase
{
    private $entityManagerMock;
    private $ticketRepositoryMock;
    private $validatorMock;
    private $ticketService;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->ticketRepositoryMock = $this->createMock(TicketRepository::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);

        $this->ticketService = new TicketService(
            $this->entityManagerMock,
            $this->ticketRepositoryMock,
            $this->validatorMock
        );
    }

    public function testCreateTicketWithValidData()
    {
        $user = $this->createMock(User::class);

        $data = [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => TicketPriority::HIGH->value,
            'status' => TicketStatus::OPEN->value,
            'category' => TicketCategory::ADMIN->value,
        ];

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList()); // No validation errors

        $this->entityManagerMock->expects($this->once())
            ->method('persist');

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $response = $this->ticketService->createTicket($data, $user);

        $this->assertEquals(Response::HTTP_OK, $response['status']);
        $this->assertEquals('ticket created successfully', $response['message']);
    }

    public function testCreateTicketWithInvalidData()
    {
        $user = $this->createMock(User::class);

        $data = [
            'title' => '', // Invalid: empty title
            'description' => 'Test Description',
            'priority' => 'INVALID_PRIORITY', // Invalid value
            'status' => TicketStatus::OPEN->value,
            'category' => TicketCategory::ADMIN->value,
        ];

        $violations = new ConstraintViolationList([
            new ConstraintViolation('Title is required.', null, [], '', 'title', ''),
            new ConstraintViolation('Invalid priority value.', null, [], '', 'priority', 'INVALID_PRIORITY')
        ]);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $response = $this->ticketService->createTicket($data, $user);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response['status']);
        $this->assertArrayHasKey('title', $response['error']);
        $this->assertArrayHasKey('priority', $response['error']);
    }

    public function testGetTicketDetailsWithValidId()
    {
        $ticket = new Ticket();
        $ticket->setTitle('Test Ticket');
        $ticket->setDescription('Test Description');
        $ticket->setPriority(TicketPriority::HIGH);
        $ticket->setStatus(TicketStatus::OPEN);
        $ticket->setCategory(TicketCategory::ADMIN);
        $ticket->setCreatedAt(new DateTimeImmutable());

        $this->ticketRepositoryMock->expects($this->once())
            ->method('findTicketById')
            ->with(1)
            ->willReturn($ticket);

        $response = $this->ticketService->getTicketDetails(1);

        $this->assertEquals(Response::HTTP_OK, $response['status']);
        $this->assertEquals('Test Ticket', $response['data']['title']);
    }

    public function testGetTicketDetailsWithInvalidId()
    {
        $this->ticketRepositoryMock->expects($this->once())
            ->method('findTicketById')
            ->with(999)
            ->willReturn(null);

        $response = $this->ticketService->getTicketDetails(999);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
        $this->assertEquals('Ticket not found', $response['error']);
    }

    public function testGetAllTicketDetailsWithData()
    {
        $tickets = [
            new Ticket(),
            new Ticket(),
        ];

        $this->ticketRepositoryMock->expects($this->once())
            ->method('findAllTickets')
            ->willReturn($tickets);

        $response = $this->ticketService->getAllTicketDetails();

        $this->assertEquals(Response::HTTP_OK, $response['status']);
        $this->assertCount(2, $response['data']);
    }

    public function testGetAllTicketDetailsWithNoData()
    {
        $this->ticketRepositoryMock->expects($this->once())
            ->method('findAllTickets')
            ->willReturn([]);

        $response = $this->ticketService->getAllTicketDetails();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
        $this->assertEquals('Ticket not found', $response['error']);
    }

    public function testGetUserTicketsWithData()
    {
        $tickets = [new Ticket(), new Ticket()];

        $this->ticketRepositoryMock->expects($this->once())
            ->method('findByExampleField')
            ->with(1)
            ->willReturn($tickets);

        $response = $this->ticketService->getUserTickets(1);

        $this->assertEquals(Response::HTTP_OK, $response['status']);
        $this->assertCount(2, $response['data']);
    }

    public function testGetUserTicketsWithNoData()
    {
        $this->ticketRepositoryMock->expects($this->once())
            ->method('findByExampleField')
            ->with(1)
            ->willReturn([]);

        $response = $this->ticketService->getUserTickets(1);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
        $this->assertEquals('Ticket not found', $response['error']);
    }
}

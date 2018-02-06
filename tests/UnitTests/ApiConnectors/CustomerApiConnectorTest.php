<?php

namespace PhpTwinfield\UnitTests;

use Money\Currency;
use Money\Money;
use PhpTwinfield\ApiConnectors\BankTransactionApiConnector;
use PhpTwinfield\ApiConnectors\CustomerApiConnector;
use PhpTwinfield\BankTransaction;
use PhpTwinfield\Customer;
use PhpTwinfield\Enums\Destiny;
use PhpTwinfield\Exception;
use PhpTwinfield\Response\Response;
use PhpTwinfield\Secure\Connection;
use PhpTwinfield\Services\ProcessXmlService;
use PHPUnit\Framework\TestCase;

class CustomerApiConnectorTest extends TestCase
{
    /**
     * @var CustomerApiConnector
     */
    protected $apiConnector;

    /**
     * @var ProcessXmlService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processXmlService;

    protected function setUp()
    {
        parent::setUp();

        $this->processXmlService = $this->getMockBuilder(ProcessXmlService::class)
            ->setMethods(["sendDocument"])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->any())
            ->method("getAuthenticatedClient")
            ->willReturn($this->processXmlService);

        $this->apiConnector = new CustomerApiConnector($connection);
    }

    private function createCustomer(): Customer
    {
        $customer = new Customer();
        return $customer;
    }

    public function testSendAllReturnsMappedObjects()
    {
        $response = Response::fromString(file_get_contents(
            __DIR__."/resources/customers-response.xml"
        ));

        $this->processXmlService->expects($this->once())
            ->method("sendDocument")
            ->willReturn($response);

        $customer = $this->createCustomer();

        $mapped = $this->apiConnector->send($customer);

        $this->assertInstanceOf(Customer::class, $mapped);
        $this->assertEquals("D1001", $mapped->getCode());
        $this->assertEquals("Hr E G H Küppers en/of MW M.J. Küppers-Veeneman", $mapped->getName());
        $this->assertEquals("BE", $mapped->getCountry());
    }
}
<?php


namespace Paysera\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Paysera\CommissionTask\Service\CalculateCommissionFees;


class CalculateCommissionFeesTest extends TestCase
{
    private $calculateCommissionFees;

    public function setUp():void
    {
        $this->calculateCommissionFees = new CalculateCommissionFees(dirname(__FILE__)."/../../example.csv");

    }

    public function testCalculateFees(){
        $this->assertIsString($this->calculateCommissionFees->calculateFees(),
        "resul output is string");
    }




}
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
        $this->assertEquals($this->calculateCommissionFees->calculateFees(),
        $this->expectOutputString("0.6\n3\n0\n0.06\n1.5\n0\n0.69\n0.3\n0.3\n3\n0\n0\n8611.43\n"));
    }




}
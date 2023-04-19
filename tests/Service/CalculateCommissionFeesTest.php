<?php


namespace Paysera\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Paysera\CommissionTask\Service\CalculateCommissionFees;


class CalculateCommissionFeesTest extends TestCase
{
    private $calculateCommissionFees;

    public function test__construct()
    {
        $this->calculateCommissionFees = new CalculateCommissionFees($this->provider());

    }

    public function provider()
    {
        return dirname(__FILE__)."/../../example.csv";
    }

    public function testCalculateFees(){
        $this->assertEquals(
        $this->expectOutputString("0.6\n3\n0\n0.06\n1.5\n0\n0.69\n0.3\n0.3\n3\n0\n0\n"),
            $this->calculateCommissionFees->calculateFees());
    }




}
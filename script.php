<?php

require_once __DIR__ . '/vendor/autoload.php';

use Paysera\CommissionTask\Service\CalculateCommissionFees;

$csvFile = $argv[1];

$commissionFees = new CalculateCommissionFees($csvFile);

try {
    echo $commissionFees->calculateFees();
} catch (Exception $e) {
    echo 'Message: ' .$e->getMessage();
}
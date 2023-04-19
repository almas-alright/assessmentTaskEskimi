<?php
$csvFile = $argv[1];

$data = array();
$url = 'https://developers.paysera.com/tasks/api/currency-exchange-rates';
$json = file_get_contents($url);
$json = json_decode($json);
$rates = json_decode(json_encode($json->rates), true);

$handle = fopen($csvFile, 'r');
while (($row = fgetcsv($handle, 1000, ',')) !== false) {
    $read['date'] = $row[0];
    $read['userId'] = $row[1];
    $read['userType'] = $row[2];
    $read['operationType'] = $row[3];
    $read['amount'] = $row[4];
    $read['currency'] = $row[5];
    $read['commission'] = 0;

//    $eur = round($row[4] / $rates[$row[5]], 2);
//    echo  $eur. PHP_EOL;
    $data[] = $read;
}
fclose($handle);

$arr = array();
foreach ($data as $key => $item) {
    $arr[$item['userId']][$key] = $item;
}
ksort($arr, SORT_NUMERIC);

try {
    echo "EEE ".getWithdrawalsThisWeek($arr,4, '2016-01-05');
} catch (Exception $e) {
    echo $e;
}

function getExchangeRate($currency){
//    $url = 'https://developers.paysera.com/tasks/api/currency-exchange-rates';
    $url = '../../fuck.json';
    $json = file_get_contents($url);
    $json = json_decode($json);
    $rates = json_decode(json_encode($json->rates), true);
    return $rates[$currency];
}

/**
 * @throws Exception
 */
function calculateCommissionFee($csvData, $index)
{
    $commissionFee = 0;

    // Convert amount to Euros if needed
//    if ($currency !== 'EUR') {
//        $amount = convertCurrency($amount, $currency, 'EUR', $date);
//    }

    if ($csvData['operationType'] === 'deposit') {
        // Deposit rule - 0.03% of deposit amount
        $commissionFee = $csvData['amount'] * 0.0003;
    } elseif ($csvData['operationType'] === 'withdraw') {
        if ($csvData['userType'] === 'private') {

        } elseif ($csvData['userType'] === 'business') {
            // Business client rule - 0.5% from withdrawn amount
            $commissionFee = $csvData['amount'] * 0.005;
        }
    }

    // Round up to currency decimal places
    $commissionFee = ceil($commissionFee * 100) / 100;

    return $commissionFee;
}

function convertCurrency($amount, $fromCurrency, $toCurrency, $date)
{
    // TODO: Implement currency conversion using rates from API
}

/**
 * @throws Exception
 */
function getWithdrawalsThisWeek($xx,$userId, $date) {
    $now = new DateTime($date);
    $startOfWeek = $now->modify('last monday')->format('Y-m-d');
    $endOfWeek = $now->modify('next sunday')->format('Y-m-d');
    $withdrawalsThisWeek = 0;
    $countWithdraw = 0;

    $url = 'https://developers.paysera.com/tasks/api/currency-exchange-rates';
    $json = file_get_contents($url);
    $json = json_decode($json);
    $rates = json_decode(json_encode($json->rates), true);

    foreach ($xx[$userId] as $operation) {
        $commissionFee = 0;
        if ($operation['operationType'] === 'withdraw' && $operation['userType'] === 'private') {
            $countWithdraw ++;
            $operation['amount'] = round($operation['amount'] / $rates[$operation['currency']], 1);
            $operationDate = new DateTime($operation['date']);
            if ($operationDate->format('Y-m-d') >= $startOfWeek && $operationDate->format('Y-m-d') <= $endOfWeek) {
                $withdrawalsThisWeek += $operation['amount'];
                if ($countWithdraw <= 3 && $withdrawalsThisWeek <= 1000) {
                    $commissionFee = 0;
                    echo "1Fuck ".$operationDate->format('Y-m-d')." ".$operation['amount']."  ".round($commissionFee,2)."\n";
                } elseif ($countWithdraw <= 3 && $withdrawalsThisWeek > 1000) {
                    $commissionable = $withdrawalsThisWeek - 1000;
                    $commissionFee = ($commissionable) * 0.003;
                    $withdrawalsThisWeek = $withdrawalsThisWeek - $commissionable;
                    echo "2Fuck ".$operationDate->format('Y-m-d')." ".$operation['amount']."  ".round($commissionFee,2)."\n";
                } elseif ($countWithdraw > 3 && $withdrawalsThisWeek > 1000) {
                    $commissionable = $withdrawalsThisWeek - 1000;
                    $commissionFee = $commissionable * 0.003;
                    $withdrawalsThisWeek = $withdrawalsThisWeek - $commissionable;
                    echo "3Fuck ".$operationDate->format('Y-m-d')." ".$operation['amount']."  ".round($commissionFee,2)."\n";
                }elseif ($countWithdraw > 3 && $withdrawalsThisWeek < 1000) {
                    $commissionFee = $operation['amount'] * 0.003;
                    $withdrawalsThisWeek = $withdrawalsThisWeek - $operation['amount'];
                    echo "3Fuck ".$operationDate->format('Y-m-d')." ".$operation['amount']."  ".round($commissionFee,2)."\n";
                }

            }
        } elseif ($operation['operationType'] === 'withdraw' && $operation['userType'] === 'business'){
            $commissionFee = $operation['amount'] * 0.005;
            echo "business Fuck ".$operation['date']." ".$operation['amount']."  ".round($commissionFee,2)."\n";
        }elseif ($operation['operationType'] === 'deposit'){
            $commissionFee = $operation['amount'] * 0.003;
            echo "deposit Fuck ".$operation['date']." ".$operation['amount']."  ".round($commissionFee,2)."\n";
        }
    }

    return $withdrawalsThisWeek;
}

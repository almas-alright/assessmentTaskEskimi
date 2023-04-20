<?php


namespace Paysera\CommissionTask\Service;


use DateTime;

class CalculateCommissionFees
{
    const EXCHANGE_RATE_URL = 'https://developers.paysera.com/tasks/api/currency-exchange-rates';
    private $exchangeRates;
    private $csvData = array();
    private $userWideOperation;

    public function __construct($csvFile)
    {
        $json = file_get_contents(self::EXCHANGE_RATE_URL);
        $json = json_decode($json);
        $this->exchangeRates = json_decode(json_encode($json->rates), true);
        $this->readCsv($csvFile);
        $this->userWideOperation = $this->groupDataBy('userId');
    }

    /**
     * @param $csvFile
     */
    private function readCsv($csvFile){
        $handle = fopen($csvFile, 'r');
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $read['date'] = $row[0];
            $read['userId'] = $row[1];
            $read['userType'] = $row[2];
            $read['operationType'] = $row[3];
            $read['amount'] = $row[4];
            $read['currency'] = $row[5];
            $read['commission'] = 0;
            $read['calculated'] = false;
            $this->csvData[] = $read;
        }
        fclose($handle);
    }

    /**
     * @param $index
     * @return array
     * @throws \Exception
     */
    private function groupDataBy($index): array
    {
        $arr = array();
        foreach ($this->csvData as $key => $item) {
            $now = new DateTime($item['date']);
            $startOfWeek = $now->modify('last monday')->format('Y-m-d');
            $arr[$item[$index]][$startOfWeek][$key] = $item;
        }
        return $arr;
    }

    /**
     * @param $userId
     * @param $date
     * @throws \Exception
     */
    private function calculate($userId, $date){
        $now = new DateTime($date);
        $startOfWeek = $now->modify('last monday')->format('Y-m-d');
        $endOfWeek = $now->modify('next sunday')->format('Y-m-d');
        $operations = $this->userWideOperation[$userId][$startOfWeek];
        $withdrawalsThisWeek = 0;
        $countWithdraw = 0;

        foreach ($operations as $key => $operation){
            $commissionFee = 0;
                if ($operation['operationType'] === 'withdraw' && $operation['userType'] === 'private' && !$operation['calculated']) {
                    //converting operation amount for second rule
                    $amount = round($operation['amount'] / $this->exchangeRates[$operation['currency']], 1);
                    $operationDate = new DateTime($operation['date']);
                    if ($operationDate->format('Y-m-d') >= $startOfWeek && $operationDate->format('Y-m-d') <= $endOfWeek) {
                        $countWithdraw ++;
                        $withdrawalsThisWeek += $amount;
                        if ($countWithdraw <= 3 && $withdrawalsThisWeek <= 1000) {
                            $commissionFee = 0;
                        } elseif ($countWithdraw <= 3 && $withdrawalsThisWeek > 1000) {
                            $commissionable = $withdrawalsThisWeek - 1000;
                            $commissionFee = ($commissionable * 0.003) * $this->exchangeRates[$operation['currency']] ;
                            $withdrawalsThisWeek = $withdrawalsThisWeek - $commissionable;
                        } elseif ($countWithdraw > 3 && $withdrawalsThisWeek > 1000) {
                            $commissionable = $withdrawalsThisWeek - 1000;
                            $commissionFee = ($commissionable * 0.003); //* $this->exchangeRates[$operation['currency']];
                            $withdrawalsThisWeek = $withdrawalsThisWeek - $commissionable;
                        }elseif ($countWithdraw > 3 && $withdrawalsThisWeek < 1000) {
                            $commissionFee = ($commissionable * 0.003); //* $this->exchangeRates[$operation['currency']];
                            $withdrawalsThisWeek = $withdrawalsThisWeek - $operation['amount'];
                        }

                    }
                } elseif ($operation['operationType'] === 'withdraw' && $operation['userType'] === 'business' && !$operation['calculated']){
                    $commissionFee = $operation['amount'] * 0.005;
                }elseif ($operation['operationType'] === 'deposit' && !$operation['calculated']){
                    $commissionFee = $operation['amount'] * 0.0003;
                }
                $this->csvData[$key]['calculated'] = true;
                $this->csvData[$key]['commission'] = round($commissionFee,2);

        }
    }

    /**
     * @throws \Exception
     */
    public function calculateFees(){
        $result = "";
        foreach ($this->csvData as $key => $value){
            $this->calculate($value['userId'], $value['date']);
            $result .= $this->csvData[$key]['commission']."\n";
        }
        return $result;
    }

}
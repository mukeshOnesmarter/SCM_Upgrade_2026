<?php

namespace App\Services\Reports;

use App\Models\FinancialQuarter;

class CashFlowService
{
    protected $balanceSheetService;

    protected $corporatePnlService;

    public function __construct(
        BalanceSheetService $balanceSheetService,
        CorporatePnlService $corporatePnlService
    ) {
        $this->balanceSheetService = $balanceSheetService;
        $this->corporatePnlService = $corporatePnlService;
    }

    public function generateReport($simulation_id, $faculty_id, $group_id, $quarter_id)
    {

        if ($quarter_id == 0) {
            return [
                'starting_cash' => 100000, // or whatever your default is
                'marketable_securities_converted' => 0,
                'loans_repaid' => 0,
                'finished_goods_inventory_delta' => 0,
                'procurement_inventory_delta' => 0,
                'plant_investment_delta' => 100000000,
                'net_income' => 0,
                'preliminary_cash' => 100000,
                'dividends' => 0,
                'actual_cash' => 100000,
                'cash_excess' => 0,
                'cash_deficit' => 0,
                'final_cash' => 100000,
            ];
        }

        $prev = FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter_id' => $quarter_id - 1,
        ])->first();

        $current = FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter_id' => $quarter_id,
        ])->first();

        if (! $prev || ! $current) {
            return ['error' => 'Required financial data not found.'];
        }

        $startingCash = $prev->cash ?? 0;
        $marketableInflow = $prev->marketable_securities ?? 0;
        $loanRepaid = ($prev->loans ?? 0) - ($current->loans ?? 0);

        $finishedGoodsDelta = array_sum(array_map(
            fn ($item, $key) => ($current->finished_goods_inventory[$key] ?? 0) - ($item ?? 0),
            $prev->finished_goods_inventory ?? [],
            array_keys($prev->finished_goods_inventory ?? [])
        ));

        $procurementDelta = array_sum(array_map(
            fn ($item, $key) => ($current->procurement_inventory[$key] ?? 0) - ($item ?? 0),
            $prev->procurement_inventory ?? [],
            array_keys($prev->procurement_inventory ?? [])
        ));

        $plantInvestmentDelta = ($current->plant_investment ?? 0) - ($prev->plant_investment ?? 0);

        $netIncome = $current->net_income ?? 0;
        $dividends = $current->dividends ?? 0;

        $preliminaryCash = $startingCash
          + $marketableInflow
          - $loanRepaid
          - $finishedGoodsDelta
          - $procurementDelta
          + $plantInvestmentDelta
          + $netIncome;

        $actualCash = $preliminaryCash - $dividends;

        $revenue = $current->revenue ?? 0;
        $minCash = $revenue * 0.05;
        $maxCash = $revenue * 0.10;

        $cashExcess = $actualCash > $maxCash ? $actualCash - $maxCash : 0;
        $cashDeficit = $actualCash < $minCash ? $minCash - $actualCash : 0;

        $finalCash = $current->cash ?? $actualCash;

        return [
            'starting_cash' => $startingCash,
            'marketable_securities_converted' => $marketableInflow,
            'loans_repaid' => $loanRepaid,
            'finished_goods_inventory_delta' => $finishedGoodsDelta,
            'procurement_inventory_delta' => $procurementDelta,
            'plant_investment_delta' => $plantInvestmentDelta,
            'net_income' => $netIncome,
            'preliminary_cash' => $preliminaryCash,
            'dividends' => $dividends,
            'actual_cash' => $actualCash,
            'cash_excess' => $cashExcess,
            'cash_deficit' => $cashDeficit,
            'final_cash' => $finalCash,
        ];
    }
}

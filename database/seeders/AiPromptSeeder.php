<?php

namespace Database\Seeders;

use App\Models\AiPrompt;
use Illuminate\Database\Seeder;

class AiPromptSeeder extends Seeder
{
    public function run()
    {
        $prompts = [
            [
                'slug' => 'corporate-pnl',
                'title' => 'Corporate P&L Report',
                'prompt' => 'Analyze overall profitability. Are net profits improving? What key cost components (e.g., Emergency Procurement, IT, Consulting Fees) are impacting margins?',

            ],
            [
                'slug' => 'product-pnl',
                'title' => 'Product P&L Report',
                'prompt' => 'Provide insight into product-level profitability. Which product performs better and why? Break down operating margin, returns, and cost structure.',
            ],
            [
                'slug' => 'balance-sheet',
                'title' => 'Balance Sheet Report',
                'prompt' => 'Evaluate financial health. Is working capital adequate? Are inventory levels and plant investment optimized?',
            ],
            [
                'slug' => 'cash-flow',
                'title' => 'Cash Flow Report',
                'prompt' => 'Break down sources and uses of cash. Are there liquidity concerns? How are loans or dividends affecting cash position?',
            ],
            [
                'slug' => 'finished-goods',
                'title' => 'Finished Goods Inventory Report',
                'prompt' => 'Analyze finished goods inventory. Are inventory levels aligned with demand? Any risk of overstock or stockouts?',
            ],
            [
                'slug' => 'procurement',
                'title' => 'Procurement Report',
                'prompt' => 'Assess procurement strategy. Are supplier choices optimal in terms of cost/failure rate/delivery? Any excessive emergency procurement?',
            ],
            [
                'slug' => 'forecast-accuracy',
                'title' => 'Forecast Accuracy Report',
                'prompt' => 'Evaluate forecasting performance. Are there consistent errors impacting operations? What improvements can be made?',
            ],
            [
                'slug' => 'service-ops',
                'title' => 'Service Ops Report',
                'prompt' => 'Assess service outsourcing quality and cost. Are SQ guarantees met? Is the service spend aligned with customer satisfaction?',
            ],
            [
                'slug' => 'decision-vars',
                'title' => 'Decision Variables Report',
                'prompt' => 'Interpret current decisions in procurement, manufacturing, service, distribution, and IT. Are they well balanced for cost, flexibility, and demand?',
            ],
            [
                'slug' => 'industry-bulletin',
                'title' => 'Industry Bulletin Report',
                'prompt' => 'Summarize relevant industry trends. What competitive insights or external changes should inform strategy adjustments?',
            ],
        ];

        foreach ($prompts as $prompt) {
            AiPrompt::updateOrCreate(['slug' => $prompt['slug']], $prompt);
        }
    }
}

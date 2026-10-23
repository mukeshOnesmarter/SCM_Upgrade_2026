<?php

return [
    'endpoints' => [
        'forecasting' => env('AI_FORECAST_ENDPOINT', 'http://analysis.flexee.org/api/forecast-recommendation/'),
        'manufacturing' => env('AI_MANUFACTURING_ENDPOINT', 'http://analysis.flexee.org/api/manufacturing-recommendation/'),
        'procurement' => env('AI_PROCUREMENT_ENDPOINT', 'http://analysis.flexee.org/api/procurement-recommendation/'),
        'distribution' => env('AI_DISTRIBUTION_ENDPOINT', 'http://analysis.flexee.org/api/distribution-recommendation/'),
        'transportation' => env('AI_TRANSPORTATION_ENDPOINT', 'http://analysis.flexee.org/api/transportation-recommendation/'),
        'demand_planning' => env('AI_DEMAND_PLANNING_ENDPOINT', 'http://analysis.flexee.org/api/demand-recommendation/'),
        'service_management' => env('AI_SERVICE_MANAGEMENT_ENDPOINT', 'http://analysis.flexee.org/api/service-recommendation/'),
        'information_technology' => env('AI_IT_ENDPOINT', 'http://analysis.flexee.org/api/it-recommendation/'),
    ],
];

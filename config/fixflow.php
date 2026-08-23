<?php

return [

    'triage' => [

        'driver' => env('FIXFLOW_TRIAGE_DRIVER', 'fake'),

        'confidence' => [
            'auto_route_min' => (float) env('FIXFLOW_AUTO_ROUTE_MIN', 0.90),
            'review_floor' => (float) env('FIXFLOW_REVIEW_FLOOR', 0.70),
        ],
    ],

    'dispatch' => [

        'auto_execute_cost_limit_cents' => (int) env('FIXFLOW_AUTO_COST_LIMIT_CENTS', 30000),

        'require_verified_contractor' => (bool) env('FIXFLOW_REQUIRE_VERIFIED_CONTRACTOR', true),

        'block_on_severities' => ['critical'],
    ],
];

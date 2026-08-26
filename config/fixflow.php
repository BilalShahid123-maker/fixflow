<?php

return [

    'triage' => [

        'driver' => env('FIXFLOW_TRIAGE_DRIVER', 'fake'),

        'confidence' => [
            'auto_route_min' => (float) env('FIXFLOW_AUTO_ROUTE_MIN', 0.90),
            'review_floor' => (float) env('FIXFLOW_REVIEW_FLOOR', 0.70),
        ],
    ],

    'llm' => [

        'provider' => env('FIXFLOW_LLM_PROVIDER', 'anthropic'),

        'model' => env('FIXFLOW_LLM_MODEL', 'claude-sonnet-4-5'),

        'cost_per_million_tokens' => [
            'input' => (float) env('FIXFLOW_COST_INPUT_PER_M', 3.0),
            'output' => (float) env('FIXFLOW_COST_OUTPUT_PER_M', 15.0),
        ],
    ],

    'dispatch' => [

        'auto_execute_cost_limit_cents' => (int) env('FIXFLOW_AUTO_COST_LIMIT_CENTS', 30000),

        'require_verified_contractor' => (bool) env('FIXFLOW_REQUIRE_VERIFIED_CONTRACTOR', true),

        'block_on_severities' => ['critical'],
    ],

    'rag' => [

        'embedding' => [
            'driver' => env('FIXFLOW_EMBEDDING_DRIVER', 'fake'),
            'provider' => env('FIXFLOW_EMBEDDING_PROVIDER', 'openai'),
            'model' => env('FIXFLOW_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'dimensions' => (int) env('FIXFLOW_EMBEDDING_DIMENSIONS', 1536),
        ],

        'chunk' => [
            'size' => (int) env('FIXFLOW_RAG_CHUNK_SIZE', 500),
            'overlap' => (int) env('FIXFLOW_RAG_CHUNK_OVERLAP', 50),
        ],

        'search' => [
            'top_k' => (int) env('FIXFLOW_RAG_TOP_K', 3),
            'min_score' => (float) env('FIXFLOW_RAG_MIN_SCORE', 0.0),
        ],
    ],
];

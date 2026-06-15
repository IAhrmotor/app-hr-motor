<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-5.5'),
    'analysis_top_candidates' => (int) env('OPENAI_ANALYSIS_TOP_CANDIDATES', 5),
    'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 120),
];

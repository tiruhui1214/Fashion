<?php
// Copy to ai.local.php and configure a compatible provider on the server.
return [
    'enabled' => false,
    'endpoint' => '', // HTTPS Chat Completions endpoint, not a website homepage.
    'api_key' => getenv('FASHION_AI_KEY') ?: '',
    'model' => getenv('FASHION_AI_MODEL') ?: '',
    'cart_ai_enabled' => false,
    'timeout_seconds' => 20,
];

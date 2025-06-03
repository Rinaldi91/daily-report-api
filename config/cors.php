<?php

return [
    'paths' => ['api/*', 'login', 'dashboard', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'], // Ubah ke domain Next.js Anda
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Penting untuk autentikasi
];
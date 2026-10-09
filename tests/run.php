<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega primeiro a classe base de testes
require_once __DIR__ . '/Testing/Test.php';

echo "🚀 Starting LiteTable Test Suite...\n\n";

// Require individual test files
require_once __DIR__ . '/QueryTest.php';
require_once __DIR__ . '/TableTest.php';

// Print final summary
\LiteTable\Testing\Test::summary();
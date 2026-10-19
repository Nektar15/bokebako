<?php

declare(strict_types=1);

/**
 * コード整形（CODING_RULES 5章）：PSR-12 ＋ CodeIgniter の標準
 * 実行：vendor/bin/php-cs-fixer fix
 */
use CodeIgniter\CodingStandard\CodeIgniter4;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->in([__DIR__ . '/app', __DIR__ . '/tests'])
    ->exclude(['Views'])
    ->append([__FILE__]);

$overrides = [
    'declare_strict_types' => true,
];

return Factory::create(new CodeIgniter4(), $overrides, ['finder' => $finder])->forProjects();

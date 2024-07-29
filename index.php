<?php

require 'vendor/autoload.php';

use Zamplate\Template;
use Zamplate\Helpers;

// Define o diretório dos templates
$templateDir = __DIR__ . '/templates';

// Cria uma instância do Zamplate
$zamplate = new Template($templateDir);

// Adicionar funções globais
$zamplate->addFunction('soma', [Helpers::class, 'soma']);

// Renderiza o template com dados
echo $zamplate->render('example.html', [
    'name' => 'text',
    'numero' => 23,
    'bool' => true,
    'array' => [[
            'id' => 345,
            'status' => true,
            'codigo' => 12343
        ],
        [
            'id' => 456,
            'status' => false,
            'codigo' => 67890
        ]]
]);

<?php

require 'vendor/autoload.php';

use Zamplate\Zamplate;

// Define o diretório dos templates
$templateDir = __DIR__ . '/templates';

// Cria uma instância do Zamplate
$zamplate = new Zamplate($templateDir);

// Renderiza o template com dados
echo $zamplate->renderizar('example.html', [
    'name' => 'World',
    'numero' => 23,
    'bool' => true,
    'array' => [
        345,
        'teste',
        true,
        12343
    ],
    'functionTeste' => functionTeste()
]);

function functionTeste(){
    echo 'teste';
}
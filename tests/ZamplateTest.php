<?php

use PHPUnit\Framework\TestCase;
use Zamplate\Zamplate;

class ZamplateTest extends TestCase
{
    public function testRenderTemplate()
    {
        $templateDir = __DIR__ . '/../templates';
        $zamplate = new Zamplate($templateDir);

        $output = $zamplate->renderizar('example.html', ['name' => 'Test']);
        $this->assertStringContainsString('Hello, Test!', $output);
    }
}

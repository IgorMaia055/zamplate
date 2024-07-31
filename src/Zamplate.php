<?php

namespace Zamplate;

class Zamplate
{
    private Template $template;

    public function __construct(string $templateDir)
    {
        $this->template = new Template($templateDir);
    }

    public function renderizar(string $template, array $data = []): string
    {
        return $this->template->render($template, $data);
    }
}

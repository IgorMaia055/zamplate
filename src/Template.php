<?php

namespace Zamplate;

class Template
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->templateDir = $templateDir;
    }

    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->templateDir . '/' . $template;

        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found: $templatePath");
        }

        $templateContent = file_get_contents($templatePath);
        $renderedContent = $this->replacePlaceholders($templateContent, $data);

        return $renderedContent;
    }

    private function replacePlaceholders(string $content, array $data): string
    {
        // Primeiro, lidamos com os loops
        $content = $this->parseLoops($content, $data);

        // Depois, lidamos com as condições
        $content = $this->parseConditions($content, $data);

        // Finalmente, substituímos os placeholders
        foreach ($data as $key => $value) {
            $placeholders = [
                '[ ' . $key . ' ]'      // New placeholder syntax
            ];

            foreach ($placeholders as $placeholder) {
                if (is_array($value)) {
                    continue;
                }
                $content = str_replace($placeholder, htmlspecialchars((string)$value), $content);
            }
        }
        return $content;
    }

    private function parseConditions(string $content, array $data): string
    {
        $pattern = '/\[\$ if (.*?) \$\](.*?)(?:\[\$ else \$\](.*?))?\[\$ endif \$\]/s';
        while (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $matches[0][0];
            $start = $matches[0][1];
            $length = strlen($fullMatch);
            $condition = $matches[1][0];
            $trueContent = $matches[2][0];
            $falseContent = isset($matches[3]) ? $matches[3][0] : '';

            // Avaliar a condição
            $result = $this->evaluateCondition($condition, $data);

            // Processar condições aninhadas dentro do conteúdo verdadeiro e falso
            $trueContent = $this->parseConditions($trueContent, $data);
            $falseContent = $this->parseConditions($falseContent, $data);

            // Substituir o conteúdo baseado na avaliação
            $replacement = $result ? $trueContent : $falseContent;
            $content = substr_replace($content, $replacement, $start, $length);
        }

        return $content;
    }

    private function parseLoops(string $content, array $data): string
    {
        $pattern = '/\[\$ for (\w+) of (\w+) \$\](.*?)\[\$ endfor \$\]/s';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $itemVar = $match[1];
            $arrayVar = $match[2];
            $loopContent = $match[3];

            if (!isset($data[$arrayVar]) || !is_array($data[$arrayVar])) {
                throw new \Exception("Variable '$arrayVar' is not defined or not an array.");
            }

            $replacement = '';
            foreach ($data[$arrayVar] as $item) {
                $loopIterationContent = str_replace('[ ' . $itemVar . ' ]', htmlspecialchars((string)$item), $loopContent);

                // Processar condições dentro do loop
                $loopIterationContent = $this->parseConditions($loopIterationContent, [$itemVar => $item]);

                $replacement .= $loopIterationContent;
            }

            $content = str_replace($match[0], $replacement, $content);
        }

        return $content;
    }

    private function evaluateCondition(string $condition, array $data): bool
    {
        if (preg_match("/(\w+)\s*==\s*'?(.*?)'?$/", $condition, $matches)) {
            $key = $matches[1];
            $value = $matches[2];

            // Verifica se a chave existe no array de dados
            if (!isset($data[$key])) {
                return false;
            }

            // Avalia a condição com base no tipo de dado esperado
            switch (strtolower($value)) {
                case 'true':
                    return $data[$key] === true;
                case 'false':
                    return $data[$key] === false;
                case 'null':
                    return $data[$key] === null;
                case 'undefined':
                    return !isset($data[$key]);
                default:
                    return (string)$data[$key] == $value;
            }
        }
        if (preg_match("/(\w+)\s*!=\s*'?(.*?)'?$/", $condition, $matches)) {
            $key = $matches[1];
            $value = $matches[2];

            // Verifica se a chave existe no array de dados
            if (!isset($data[$key])) {
                return false;
            }

            // Avalia a condição com base no tipo de dado esperado
            switch (strtolower($value)) {
                case 'true':
                    return $data[$key] != true;
                case 'false':
                    return $data[$key] != false;
                case 'null':
                    return $data[$key] != null;
                case 'undefined':
                    return !isset($data[$key]);
                default:
                    return (string)$data[$key] != $value;
            }
        }

        return false;
    }
}

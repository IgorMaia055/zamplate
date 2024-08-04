<?php
namespace Zamplate;

class Template
{
    private string $templateDir;
    private array $functions = [];

    public function __construct(string $templateDir)
    {
        $this->templateDir = $templateDir;
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->functions[$name] = $callback;
    }


    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->templateDir . '/' . $template;

        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found: $templatePath");
        }

        $templateContent = file_get_contents($templatePath);
        $templateContent = $this->replacePlaceholders($templateContent, $data);

        return $templateContent;
    }

    private function replacePlaceholders(string $content, array $data): string
    {
        // Primeiro, lidamos com os loops
        $content = $this->parseLoops($content, $data);

        // Depois, lidamos com as condições
        $content = $this->parseConditions($content, $data);

        // Finalmente, substituímos os placeholders
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $content = str_replace('[ ' . $key . ' ]', htmlspecialchars((string) $value), $content);
        }

        // E, por último, processamos as funções
        $content = $this->parseFunctions($content, $data);

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
                $loopIterationContent = $loopContent;

                // Verifica se o item é um array e substitui as propriedades
                if (is_array($item) || is_object($item)) {
                    foreach ($item as $key => $value) {
                        $loopIterationContent = str_replace('[ ' . $itemVar . '.' . $key . ' ]', htmlspecialchars((string) $value), $loopIterationContent);
                    }
                } else {
                    // Substitui diretamente se o item não for um array ou objeto
                    $loopIterationContent = str_replace('[ ' . $itemVar . ' ]', htmlspecialchars((string) $item), $loopIterationContent);
                }

                // Processa condições dentro do loop
                $loopIterationContent = $this->parseConditions($loopIterationContent, array_merge($data, [$itemVar => $item]));

                $replacement .= $loopIterationContent;
            }

            $content = str_replace($match[0], $replacement, $content);
        }

        return $content;
    }


    private function parseFunctions(string $content): string
    {
        $pattern = '/\[\$ (\w+)\((.*?)\) \$\]/';

        return preg_replace_callback($pattern, function ($matches) {
            $funcName = $matches[1];
            $args = array_map('trim', explode(',', $matches[2]));

            if (isset($this->functions[$funcName])) {
                return call_user_func_array($this->functions[$funcName], $args);
            }

            return $matches[0];
        }, $content);
    }

    private function evaluateCondition(string $condition, array $data): bool
    {
        // Adicionado suporte para comparação de variáveis diretas
        if (preg_match("/(\w+)\s*==\s*'?(.*?)'?$/", $condition, $matches)) {
            $variable = $matches[1];
            $value = $matches[2];

            if (!isset($data[$variable])) {
                return false;
            }

            return (string) $data[$variable] == $value;
        } elseif (preg_match("/(\w+)\s*!=\s*'?(.*?)'?$/", $condition, $matches)) {
            $variable = $matches[1];
            $value = $matches[2];

            if (!isset($data[$variable])) {
                return false;
            }

            return (string) $data[$variable] != $value;
        }

        // Código existente para avaliar propriedades de objetos
        if (preg_match("/(\w+)\.(\w+)\s*==\s*'?(.*?)'?$/", $condition, $matches)) {
            $object = $matches[1];
            $key = $matches[2];
            $value = $matches[3];

            if (!isset($data[$object]) || !is_array($data[$object])) {
                return false;
            }

            switch (strtolower($value)) {
                case 'true':
                    return $data[$object][$key] === true;
                case 'false':
                    return $data[$object][$key] === false;
                case 'null':
                    return $data[$object][$key] === null;
                case 'undefined':
                    return !isset($data[$object][$key]);
                default:
                    return (string) $data[$object][$key] == $value;
            }
        } elseif (preg_match("/(\w+)\.(\w+)\s*!=\s*'?(.*?)'?$/", $condition, $matches)) {
            $object = $matches[1];
            $key = $matches[2];
            $value = $matches[3];

            if (!isset($data[$object]) || !is_array($data[$object])) {
                return false;
            }

            switch (strtolower($value)) {
                case 'true':
                    return $data[$object][$key] != true;
                case 'false':
                    return $data[$object][$key] != false;
                case 'null':
                    return $data[$object][$key] != null;
                case 'undefined':
                    return !isset($data[$object][$key]);
                default:
                    return (string) $data[$object][$key] != $value;
            }
        }

        return false;
    }


}

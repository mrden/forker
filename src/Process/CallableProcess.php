<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Traits\FilePidStorageTrait;
use Mrden\Forker\Traits\SimpleProcessTrait;

final class CallableProcess extends Process
{
    use FilePidStorageTrait;
    use SimpleProcessTrait;

    /**
     * @var \Closure(CallableProcess): void
     */
    private \Closure $logic;

    public function __construct(\Closure $logic, array $params = [])
    {
        $this->logic = $logic;
        parent::__construct($params);
    }

    public function id(): string
    {
        // Сначала пробуем создать стабильный ID на основе содержимого
        $stableId = $this->tryCreateStableId();
        if ($stableId !== null) {
            return $stableId;
        }

        // Fallback к уникальному ID
        return $this->fallbackId();
    }

    private function tryCreateStableId(): ?string
    {
        try {
            $reflection = new \ReflectionFunction($this->logic);
            $fileName = $reflection->getFileName();
            $startLine = $reflection->getStartLine();
            $endLine = $reflection->getEndLine();

            if (!$fileName || !$startLine || !$endLine || !\file_exists($fileName)) {
                return null;
            }

            // Читаем исходный код замыкания
            $lines = \file($fileName, FILE_IGNORE_NEW_LINES);
            $closureLines = \array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $code = \implode("\n", $closureLines);

            // Извлекаем только содержимое функции (между фигурными скобками)
            $functionBody = $this->extractFunctionBody($code);

            // Если не удалось извлечь тело функции, используем весь код
            if ($functionBody === null) {
                $functionBody = $code;
            }

            // Нормализуем код для сравнения
            $normalizedCode = $this->normalizeClosureCode($functionBody);

            // Создаем отпечаток на основе нормализованного кода + параметров
            $fingerprint = [
                'code' => $normalizedCode,
                'params' => $this->getParametersSignature($reflection),
                'uses' => \array_keys($reflection->getStaticVariables()),
            ];

            $identifier = \md5(\serialize($fingerprint));
            return \md5(\get_class($this) . $identifier . \serialize($this->getParamsWithoutExclude()));

        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractFunctionBody(string $code): ?string
    {
        // Ищем функцию и извлекаем только её тело
        if (\preg_match('/function[^{]*\{(.*)\}/s', $code, $matches)) {
            return \trim($matches[1]);
        }

        // Альтернативный поиск для стрелочных функций
        if (\preg_match('/fn[^=]*=>\s*(.+)/', $code, $matches)) {
            return \trim($matches[1]);
        }

        return null;
    }

    private function normalizeClosureCode(string $code): string
    {
        // Удаляем комментарии
        $code = \preg_replace('/\/\*.*?\*\//s', '', $code);
        $code = \preg_replace('/\/\/.*$/m', '', $code);

        // Нормализуем пробелы
        $code = \preg_replace('/\s+/', ' ', $code);

        // Удаляем лишние пробелы вокруг операторов и скобок
        $code = \preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $code);

        return \trim($code);
    }

    private function getParametersSignature(\ReflectionFunction $reflection): array
    {
        return \array_map(function (\ReflectionParameter $param) {
            return $param->getName() . ':' . ($param->getType() ? $param->getType()->__toString() : '');
        }, $reflection->getParameters());
    }

    private function fallbackId(): string
    {
        return \md5(\get_class($this) . \serialize($this->getParamsWithoutExclude()) . \spl_object_hash($this->logic));
    }

    protected function execute(): void
    {
        \call_user_func($this->logic, $this);
    }
}

<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Contracts\ProcessManagerInterface;
use Mrden\Forker\Traits\SimpleProcessTrait;

final class CallableProcess extends Process
{
    use SimpleProcessTrait;

    /**
     * @var \Closure(CallableProcess): void
     */
    private \Closure $logic;

    public function __construct(
        \Closure $logic,
        array $params = [],
        ?ProcessManagerInterface $processManager = null,
        ?string $pidStorageClassName = null
    ) {
        $this->logic = $logic;
        parent::__construct($params, $processManager, $pidStorageClassName);
    }

    public function id(): string
    {
        $stableId = $this->tryCreateStableId();
        if ($stableId !== null) {
            return $stableId;
        }

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

            $lines = \file($fileName, FILE_IGNORE_NEW_LINES);
            $closureLines = \array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $code = \implode("\n", $closureLines);

            $functionBody = $this->extractFunctionBody($code);

            if ($functionBody === null) {
                $functionBody = $code;
            }

            $normalizedCode = $this->normalizeClosureCode($functionBody);

            $fingerprint = [
                'code' => $normalizedCode,
                'params' => $this->getParametersSignature($reflection),
                'uses' => \array_keys($reflection->getStaticVariables()),
            ];

            $identifier = \md5(\serialize($fingerprint));
            return \md5(\get_class($this) . $identifier . \serialize($this->getParamsWithoutExclude()));

        } catch (\Exception $e) {
            // todo: logging or ... ?
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

    protected function initGracefulShutdown(): void
    {
    }
}

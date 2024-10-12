# Php process forker

## Install

`composer require mrden/forker`

## Fork single callable process

```php
$context = 'any parent code context data';
$callable = new \Mrden\Forker\Process\CallableProcess(static function () use ($context) {
    echo 'context from parent process ' . $context;
});
$forker = new \Mrden\Forker\Forker($callable);
$forker->fork();
// any code in this parent process
```

## Fork single process of your implementation in 3 clones

```php
namespace Any;

class SingleProcess extends \Mrden\Forker\Contracts\Process
{
    use \Mrden\Forker\Traits\ProcessFileStorageTrait;

    public function execute(): void
    {
        $params = $this->getParams();
        echo 'context from parent process ' . ($params['context'] ?? '');
    }

    protected function prepare(): void
    {
    }
    
    protected function checkParams(): void
    {
    }
}
```
### Start in code

```php
$context = 'any parent code context data';
$singleProcess = new \Any\SingleProcess([
    'context' => $context
]);
$forker = new \Mrden\Forker\Forker($singleProcess);
$forker->fork(3);
// any code in this parent process
```
### Start via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --count=3 --process-context="any context data"`

### Stop via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --stop=1 --process-context="any context data"`

### Stop only 2 clones via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --stop=1 --count=2 --process-context="any context data"`

### Stop only 2-nd clone via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --stop=1 --clone_number=2 --process-context="any context data"`

### Restart all clones via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --restart=1 --process-context="any context data"`

### Restart only 2 clones via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --restart=1 --count=2 --process-context="any context data"`

### Restart only 2-nd clone via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --restart=1 --clone_number=2 --process-context="any context data"`
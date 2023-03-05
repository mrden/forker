# Php process forker

## Install

`composer require mrden/forker`

## Fork single callable process

```php
$context = 'any parent code context data';
$callable = new \Mrden\Fork\Process\CallableProcess(function () use ($context) {
    echo 'context from parent process ' . $context;
});
$forker = new \Mrden\Fork\Forker($callable);
$forker->run();
// any code in this parent process
```

## Fork single process of your implementation in 3 clones

```php
namespace Any;

class SingleProcess extends \Mrden\Fork\Contracts\Process
{
    use \Mrden\Fork\Traits\ProcessFileStorageTrait;

    public function execute(): void
    {
        echo 'context from parent process ' . $this->params['context'];
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
$forker = new \Mrden\Fork\Forker($singleProcess);
$forker->run(3);
// any code in this parent process
```
### Start via `bin/forker`
`php bin/forker --process="\Any\SingleProcess" --count=3 --process-context="any context data"`

## Fork single daemon process in 3 clones
```php
namespace Any;

class SingleDaemonProcess extends \Mrden\Fork\Contracts\DaemonProcess
{
    use \Mrden\Fork\Traits\ProcessFileStorageTrait;
    
    /**
     * in sec
     */
    protected $period = 5;
    
    protected function job(): void
    {
        echo 'I\'m the code of iteration daemon process';
    }
    
    protected function checkParams(): void
    {
    }

    protected function prepare(): void
    {
    }
}
```
### Start in code
```php
$singleProcess = new \Any\SingleDaemonProcess();
$forker = new \Mrden\Fork\Forker($singleProcess);
$forker->run(3);
```
### Start via `bin/forker`
`php bin/forker --process="\Any\SingleDaemonProcess" --count=3`

### Stop via `bin/forker`
`php bin/forker --process="\Any\SingleDaemonProcess" --stop=1`

### Stop only 2 clones via `bin/forker`
`php bin/forker --process="\Any\SingleDaemonProcess" --stop=1 --count=2`

## Start single damon watcher process
```php
namespace Any;

class SingleDaemonWatcherProcess extends \Mrden\Fork\Contracts\DaemonWatcherProcess
{
    use \Mrden\Fork\Traits\ProcessFileStorageTrait;
    
    protected function processes(): array
    {
        return return [
            [
                'process' => \Any\SingleProcess::class,
                'params' => [
                    'time' => 11,
                ],
                'count' => 1,
            ],
            [
                'process' => \Any\SingleDaemonProcess::class,
                'count' => 2,
            ],
        ];
    }

    protected function prepare(): void
    {
    }
}
```
Daemon watcher process forked only in 1 clone.

### Start in code
```php
$singleProcess = new \Any\SingleDaemonWatcherProcess();
$forker = new \Mrden\Fork\Forker($singleProcess);
$forker->run();
```

### Start via `bin/forker`
`php bin/forker --process="\Any\SingleDaemonWatcherProcess" --count=3`

### Stop via `bin/forker`
`php bin/forker --process="\Any\SingleDaemonWatcherProcess" --stop=1`
will be stopped all (self and child process)

### Stop via `kill`
* `kill PID` or `kill -15 PID` - will be stopped only daemon watcher, child process continue to work
* `kill -10 PID` - will be stopped all (self and child process)
# Pipeline

Pipeline is a class designed to perform chained modifications.

## Pipe

"Pipe" is a class performing some transformations to passed value.

```php
class Pipe
{
    public function handle($passable, callable $next)
    {
        // here you can perform modifications of passable 
        
        // and pass it to next pipe
        $modified = $next($passable);
        
        // and finally you can modify returned value here 
        
        return $modified;
    }
}
```

Calling `$next()` function runs next modifications. You can break modification chain by returning desired result without
calling this function.

## Usage

```php
use OpxCore\Container\Container;
use OpxCore\Pipeline\Pipeline;

$container = new Container;
$pipeline = new Pipeline($container);

$result = $pipeline
    ->send('some value')
    ->through(['pipe_1', 'pipe_2'])
    ->via('handle')
    ->then(function ($passable){
        return $passable;
    })
    ->run();
```

This will pass value through 'pipe_1', 'pipe_2', then modified value would be passed to callback and returned same way.

Pipes may be class names, so container must be set to resolve and make them (with all needed dependencies). Other way
you can pass already created classes, so this way you do not need container.
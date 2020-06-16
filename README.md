
> Class HookMgr manages PHP hooks
>
A hook is a (HookMgr) key for invoking callable(s)

A callable can be
 * simple function
 * anonymous function
 * instantiated object+method : \[ $object, 'methodName' ]
 * class name and static method : \[ 'namespaceClassName', 'methodName' ]
 * instantiated object, class with (magic) __call method : \[ $object, 'someMethod' ]
 * class name, class with (magic) __callStatic method : \[ 'namespaceClassName', 'someMethod' ]
 * instantiated object, class with (magic) __invoke method : $object
 
 Define a hook with callable
``` php
HookMgr::addAction( $hook, $callable );
```
Invoke callable using hook
``` php
$result = HookMgr::apply( $hook );
```

###### Methods

```HookMgr::addAction( hook, callable )```
* Add single hook with single callable, _syntax_only_ callable check
* ```hook``` _string_  
* ```callable``` _callable_
* Throws InvalidArgumentException
* static

```HookMgr::addActions( hook, callables )```
* Add single hook invoking an array of callables
* Note, if invoked with arguments, arguments are used for all callables
* ```hook``` _string_  
* ```callables``` _callable\[]_
* Throws InvalidArgumentException
* static

```HookMgr::setActions( actions )```
* Set all hooks, each for invoking single or array of callables
* ```actions``` _array_ *( hook => callable(s) )
* Throws InvalidArgumentException
* static

---

```HookMgr::apply( hook [, args ] )```
* Invoke 'hook' action(s), return (last) result
* ```hook``` _string_  
* ```args``` _array_ opt, \[ arg1, arg2... ]
  * Opt arguments are used in all hook invokes
  * To use an argument by-reference, use ```HookMgr::apply( 'hook', [ & $arg ] );```
* Return _mixed_
* Throws RuntimeException
* static

---

```HookMgr::count( [ hook ] )```
* Return 
  * count of hooks
  * count of callables for hook
  * not found hook return 0
* ```hook``` _string_  
* Return bool
* static

```HookMgr::exists( hook )```
* ```hook``` _string_  
* Return bool, true if hook is set
* static

```HookMgr::getCallables( hook )```
* Return array callables for hook, not found return []
* ```hook``` _string_  
* Return _callable\[]_
* static

```HookMgr::getHooks()```
* Return _array_ (string[]) hooks
* static

```HookMgr::init()```
* Clear (remove) all hooks with callables
* static


```HookMgr::remove( hook )```
* Remove single hook with callable(s)
* ```hook``` _string_  
* static

---

```HookMgr::toString()```
* Return _string_ nice rendered hooks with callable(s)
* static

###### Sponsorship

Donation using <a href="https://paypal.me/kigkonsult?locale.x=en_US" rel="nofollow">paypal.me/kigkonsult</a> are appreciated. 
For invoice, <a href="mailto:ical@kigkonsult.se">please e-mail</a>.

###### INSTALL

``` php
composer require kigkonsult/hookmgr:dev-master
```

Composer, in your `composer.json`:

``` json
{
    "require": {
        "kigkonsult/hookmgr": "dev-master"
    }
}
```

Composer, acquire access
``` php
use Kigkonsult\HookMgr\HookMgr;
...
include 'vendor/autoload.php';
```


Otherwise , download and acquire..

``` php
use Kigkonsult\HookMgr\HookMgr;
...
include 'pathToSource/kigkonsult/HookMgr/autoload.php';
```


###### Support

For support go to [github.com HookMgr]


###### License

This project is licensed under the LGPLv3 License


[Composer]:https://getcomposer.org/
[github.com HookMgr]:https://github.com/iCalcreator/HookMgr

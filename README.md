# Expirable Laravel Model Caching
## Предыстория
Разрабатывая проекты, где большое внимание приделяется слову Highload - я начал изучать реализацию кэш библиотек изнутри, так как я увидел множество ненужных кэш-запросов в Redis используя существующие библиотеки. 
Например, изучив **watson/rememberable** и **genealabs/laravel-model-caching** увидел помимо данных такие ключи для каждого запроса: ...-cooldown:saved-at, ...:invalidated-at, ...:seconds, ...:saved-at, ...:invalidated-at, ...:seconds. Эти данные используются для инвалидации самого кэша, но создают много лишних оперций для сервера PHP, и это, как я сказал помимо самых данных, которые хранятся не как положено в Key-Value парах(SET-GET), а в промежуточных буферах(SADD, SMEMBERS) что дополнительно расходует память и заставляет делать много запросов на Redis, сравнивать временные метки, и удалять что-то когда время вышло.

Моей же задачей стояло создать такую систему которая бы создавала максимально мало дополнительной нагрузки, за счёт использования всех возможностей сервера Redis - чего я и добился.

## Использования
### Установка
Библеотека доступна в репозиториях composer'а:
`composer require olegkravec/expirable`
### Включение кэширования
Для удобства использования я включил по-умолчанию кэширование как-только вы подключите трейт в свою модель **use Expirable;**
```php
<?php
namespace App;

use OlegKravec\Expirable\Expirable;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use Expirable;
    
    /**
    * Your code here...
    */
}
```

После подключения все данные автоматически будут кэшироваться на 300 секунд(5 минут)
### Дополнительные настройки
На данный момент доступно такие предустановки для всей модели данных: 
* Время жизни всех кэш данных привязаных до этой модели
* Кастомный префикс для ключа данных
* Включение хеширования кэш-ключа по md5 алгоритму, уменьшает длину ключа в найменьшей мере на 120 байт, но увеличивает дополнительную нагрузку за счет процесса хеширования.
```php
<?php
class YourModel extends Model
{
    use Expirable;
    
    public $_expirable_ttl = 100; 
    public $_expirable_prefix = "MyModelPrefix";
    public $_expirable_cache_hashing_enabled = true;
    
    /**
    * Your code here...
    */
}
```

#### Отключение кэша
В случае если вам не нужно получать данные с кэша для одного запроса можно вызвать метод **disableCache()**:

`User::where("key","value")->disableCache()->get()`


#### Изменение TTL
Так как по-умолчанию кэш будет сохранять данные на 5 минут, вы можете установить собственный лимит времени через **expire(int $seconds)**:

`User::where("key","value")->expire(10)->get()`

В этом случае полученные данные будут закэшированы на 10 секунд.


#### Обновление TTL
Иногда было бы хорошо обновлять время жизни данных только в том случае когда к ним кто-то обращается. Это бы существенно уменьшило количество выборок из базы данных, и уменьшило количество неиспользуемой памяти за счёт кэширования только тех данных - которые действительно пользуются спросом. Для этого вызовите **resetExpire(int $seconds)**:

`User::where("key","value")->resetExpire(10)->get()`

Если данные уже были в кэше, времям их жизни снова установиться на указанное число секунд, если их не было - дынные будут получены и сохранены на 10 секунд.


#### Хеширование ключей
В случаях, когда вам нужно сделать запросы с большой выборкой, я настоятельно рекомендую использовать хеширования ключей, это очень сильно позволить сократить расход памяти но увеличит нагрузку, - **hashExpirable()**:

`User::where("key","value")->where("key2","value2")->where("key3","value3")->where("key4","value4")->take(10)->skip(5)->hashExpirable()->get()`

Как указано в примере ниже, ключ любой длины будет преобразован в строку с 32 символами. Чем больше выборка к БД - тем больше экономия!

#### Собственный префикс
Так как ключ данных выглядит примерно таким образом:

`expirable:${tableName}:${yourPrefix}:[{"type":"Basic","column":"id","operator":">","value":2,"boolean":"and"}]:null:{"select":[],"from":[],"join":[],"where":[2],"having":[],"order":[],"union":[]}`

вы не можете сохранять по одному ключу разные данные, но если вам это нужно, то для каждого запроса укажите собственный префикс через **prefix(string $cachePrefix)**:

`User::where("key","value")->prefix("yourPrefix")->get()`






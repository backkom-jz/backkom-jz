# PHP 基础进阶与运行机制

## 1. PHP 请求生命周期

以 Nginx + PHP-FPM 为例，一次 PHP 请求大致流程如下：

```text
客户端请求
-> Nginx 接收 HTTP 请求
-> 判断是否为 PHP 脚本
-> 通过 FastCGI 协议转发给 PHP-FPM
-> PHP-FPM worker 处理请求
-> 加载 PHP 文件
-> OPcache 命中或编译为 opcode
-> 执行业务代码
-> 返回响应给 Nginx
-> Nginx 返回给客户端
```

面试回答重点：

- Nginx 不直接执行 PHP。
- PHP-FPM 负责管理 PHP worker 进程。
- PHP 文件会被编译成 opcode 执行。
- OPcache 可以缓存 opcode，减少重复编译。
- 普通 PHP-FPM 模式是请求级生命周期，请求结束后变量释放。

## 2. PHP-FPM 工作模型

PHP-FPM 是 FastCGI Process Manager，用于管理 PHP 进程池。

核心角色：

- master 进程：负责管理 worker、读取配置、平滑重启。
- worker 进程：真正处理请求。

常见配置：

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000
```

配置解析：

- `pm.max_children`：最大 worker 数，请求并发超过后会排队。
- `pm.max_requests`：单个 worker 处理多少请求后重启，可缓解内存泄漏。
- `pm = static`：固定 worker 数，适合流量稳定场景。
- `pm = dynamic`：动态调整 worker 数，适合多数业务。
- `pm = ondemand`：按需创建 worker，适合低流量服务。

面试题：PHP-FPM 连接打满怎么办？

回答思路：

1. 先看是否 `pm.max_children` 耗尽。
2. 查看 PHP-FPM slowlog，定位慢请求。
3. 检查 MySQL、Redis、外部接口是否阻塞。
4. 短期可以扩容 worker 或机器。
5. 长期要优化慢 SQL、接口超时、缓存、队列异步化。

## 3. OPcache

PHP 执行脚本前，需要将源码解析并编译成 opcode。

OPcache 的作用是缓存 opcode，避免每次请求都重复编译。

典型收益：

- 降低 CPU 消耗。
- 提高接口响应速度。
- 减少文件解析开销。

常见配置：

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```

线上注意：

- 生产环境通常开启 OPcache。
- 发布代码后要注意 OPcache 刷新。
- `validate_timestamps=0` 性能更好，但发布时必须主动 reload 或清理缓存。

面试题：为什么线上改了 PHP 文件没有立即生效？

可能原因：

- OPcache 缓存未刷新。
- PHP-FPM 未 reload。
- 部署到了错误机器或错误目录。
- Nginx 指向的 root 路径不是当前发布路径。

## 4. Composer 自动加载

Composer 是 PHP 的依赖管理和自动加载工具。

常见自动加载方式：

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    }
  }
}
```

执行：

```bash
composer dump-autoload
```

PSR-4 规则：

```text
命名空间前缀 -> 目录路径
App\Service\UserService -> app/Service/UserService.php
```

面试题：Composer 自动加载原理是什么？

回答：

Composer 会生成 `vendor/autoload.php`，注册自动加载函数。当使用某个类时，如果类尚未加载，自动加载函数会根据命名空间和类名映射到具体文件路径，然后 include 对应文件。

优化：

```bash
composer dump-autoload -o
```

`-o` 会生成 classmap，提高生产环境自动加载性能。

## 5. PHP 类型系统

PHP 是动态类型语言，但 PHP 7/8 已支持更严格的类型声明。

示例：

```php
declare(strict_types=1);

function add(int $a, int $b): int
{
    return $a + $b;
}
```

常见类型：

- 标量类型：`int`、`float`、`string`、`bool`
- 复合类型：`array`、`object`、`callable`、`iterable`
- 特殊类型：`mixed`、`void`、`never`
- 联合类型：`int|string`
- 可空类型：`?string`

面试题：`==` 和 `===` 区别？

- `==`：值相等，会进行类型转换。
- `===`：值和类型都相等，不进行隐式转换。

建议：

业务代码中优先使用 `===`，避免 PHP 弱类型转换导致异常判断。

## 6. isset、empty、is_null 区别

```php
$a = null;

isset($a);   // false
empty($a);   // true
is_null($a); // true
```

区别：

- `isset($var)`：变量存在且不为 null。
- `empty($var)`：变量为空，`0`、`"0"`、`""`、`false`、`[]`、`null` 都算 empty。
- `is_null($var)`：变量值是否为 null，变量必须已定义，否则可能 warning。

面试重点：

`empty("0")` 返回 `true`，这是常见坑。

## 7. PHP 数组底层特点

PHP 的 `array` 本质是有序 HashTable。

它既可以当普通数组：

```php
$list = [1, 2, 3];
```

也可以当字典：

```php
$map = [
    'name' => 'Tom',
    'age' => 18,
];
```

优点：

- 使用灵活。
- 支持有序遍历。
- key 可以是整数或字符串。

缺点：

- 内存占用较高。
- 不适合极大规模数据长期堆在内存里。

面试题：为什么 PHP array 比普通数组占内存？

因为 PHP array 不是连续内存数组，而是 HashTable。每个元素都要存 key、value、hash、指针等额外信息，所以灵活但内存成本高。

## 8. 引用与写时复制

PHP 变量赋值默认使用写时复制。

```php
$a = [1, 2, 3];
$b = $a;
$b[] = 4;
```

在 `$b` 修改前，`$a` 和 `$b` 可以共享同一份数据。真正修改时，PHP 才会复制一份。

引用示例：

```php
$a = 1;
$b =& $a;
$b = 2;

echo $a; // 2
```

面试重点：

- 普通赋值不是立刻深拷贝。
- 引用会让两个变量指向同一份值。
- `foreach` 中引用变量容易残留，使用后要 `unset($value)`。

示例：

```php
foreach ($arr as &$value) {
    $value++;
}
unset($value);
```

## 9. 错误与异常

PHP 中常见问题类型：

- Notice
- Warning
- Fatal error
- Exception
- Error

PHP 7 之后，很多致命错误被转换为 `Throwable` 体系。

```php
try {
    risky();
} catch (Throwable $e) {
    // 统一捕获 Error 和 Exception
}
```

结构：

```text
Throwable
├── Exception
└── Error
```

面试题：Exception 和 Error 区别？

- `Exception` 通常表示业务或可预期异常。
- `Error` 通常表示语言运行级错误，例如类型错误、调用不存在方法等。
- 实际项目中可以用 `Throwable` 做兜底捕获，但不能吞掉错误，要记录日志并告警。

## 10. trait、interface、abstract class

### interface

定义行为契约。

```php
interface LoggerInterface
{
    public function info(string $message): void;
}
```

### abstract class

可以定义公共实现，也可以定义抽象方法。

```php
abstract class BaseRepository
{
    protected string $table;

    abstract public function find(int $id): array;
}
```

### trait

用于横向复用代码。

```php
trait TimestampTrait
{
    public function now(): int
    {
        return time();
    }
}
```

面试题：三者怎么选？

- 定义能力规范，用 interface。
- 多个子类共享基础逻辑，用 abstract class。
- 多个无继承关系的类复用一段方法，用 trait。

## 11. yield 生成器

生成器适合处理大数据遍历，避免一次性把所有数据放入内存。

```php
function readLines(string $file): Generator
{
    $handle = fopen($file, 'r');
    while (($line = fgets($handle)) !== false) {
        yield $line;
    }
    fclose($handle);
}

foreach (readLines('large.log') as $line) {
    // handle line
}
```

面试题：yield 有什么用？

`yield` 可以按需返回数据，降低内存占用。适合读取大文件、大结果集、流式处理。

## 12. PHP 7 / PHP 8 常见特性

PHP 7 常见特性：

- 标量类型声明。
- 返回值类型声明。
- null 合并运算符 `??`。
- 太空船操作符 `<=>`。
- 匿名类。
- 性能大幅提升。

PHP 8 常见特性：

- JIT。
- union types 联合类型。
- named arguments 命名参数。
- attributes 注解。
- constructor property promotion 构造器属性提升。
- match 表达式。
- nullsafe operator `?->`。
- readonly 属性。

示例：

```php
$name = $user?->profile?->name;
```

## 13. 常驻内存模式与传统 FPM 区别

传统 PHP-FPM：

- 请求结束后释放变量。
- 全局状态影响较小。
- 模型简单，适合大多数 Web 应用。

Swoole / Hyperf 常驻内存：

- 进程长期运行。
- 对象、静态变量、全局变量可能跨请求存在。
- 性能更高，但更容易出现内存泄漏和状态污染。

面试题：FPM 项目迁移到 Swoole 要注意什么？

- 不能把请求级数据放在全局变量或静态属性里。
- 数据库、Redis 连接要使用连接池。
- 注意协程安全。
- 注意内存泄漏。
- 发布后需要重启 worker 才能彻底加载新代码。

## 14. 常见面试题速答

### PHP-FPM 和 Nginx 的关系？

Nginx 负责接收 HTTP 请求和静态资源处理，PHP-FPM 负责执行 PHP 脚本。两者通常通过 FastCGI 协议通信。

### PHP 为什么需要 OPcache？

为了缓存 PHP 编译后的 opcode，避免每次请求都重新解析和编译 PHP 文件，提高性能。

### Composer 自动加载如何优化？

生产环境使用：

```bash
composer install --no-dev -o
```

`--no-dev` 不安装开发依赖，`-o` 优化自动加载。

### PHP array 是线程安全的吗？

传统 PHP-FPM 是多进程模型，每个请求在独立 worker 中执行，不共享普通内存变量，所以通常不讨论线程安全。若在 Swoole 协程或共享内存场景下，则需要考虑并发读写安全。

### PHP 内存泄漏怎么排查？

常见方向：

- 大数组未释放。
- 静态变量或全局容器持续增长。
- 常驻内存服务中请求对象被长期引用。
- 资源句柄未关闭。
- 第三方扩展泄漏。

排查方式：

- 查看进程 RSS。
- 打日志记录内存变化。
- 使用 `memory_get_usage()`。
- 开启 FPM `pm.max_requests` 缓解。
- 常驻内存服务使用 worker reload。

## 15. 复习建议

优先掌握：

1. PHP-FPM 请求模型。
2. OPcache。
3. Composer 自动加载。
4. PHP array、引用、写时复制。
5. OOP、interface、trait、abstract class。
6. 错误与异常体系。
7. PHP 7/8 新特性。
8. FPM 与 Swoole 常驻内存差异。


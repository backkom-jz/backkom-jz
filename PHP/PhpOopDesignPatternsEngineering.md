# PHP 面向对象、设计模式与工程规范

## 1. 面向对象核心

面向对象不是简单地把函数放进类里，而是通过对象表达业务概念，通过封装、继承、多态降低复杂度。

三大特性：

- 封装：隐藏内部实现，对外暴露稳定接口。
- 继承：复用父类能力，但要避免过深继承。
- 多态：同一个接口，不同实现类有不同表现。

示例：

```php
interface PaymentInterface
{
    public function pay(int $amount): bool;
}

class WechatPay implements PaymentInterface
{
    public function pay(int $amount): bool
    {
        return true;
    }
}

class Alipay implements PaymentInterface
{
    public function pay(int $amount): bool
    {
        return true;
    }
}
```

面试解析：

如果业务里到处写 `if ($type === 'wechat')`、`if ($type === 'alipay')`，后续新增支付渠道会不断修改旧代码。通过接口和多态，可以把变化封装到不同实现类里。

## 2. 类、对象、属性与方法

PHP 类常用可见性：

- `public`：外部可访问。
- `protected`：当前类和子类可访问。
- `private`：仅当前类可访问。

示例：

```php
class User
{
    private int $id;
    private string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

面试解析：

属性不要全部设为 `public`。业务对象应该保护自己的状态，外部通过方法访问或修改，便于增加校验和维护不变量。

## 3. interface、abstract class、trait

### interface

接口定义能力，不关心具体实现。

```php
interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttl): bool;
}
```

适合场景：

- 日志接口
- 缓存接口
- 支付接口
- 消息队列接口
- 文件存储接口

### abstract class

抽象类适合沉淀公共逻辑。

```php
abstract class BaseRepository
{
    public function findById(int $id): array
    {
        return $this->query()->where('id', $id)->first();
    }

    abstract protected function query();
}
```

### trait

trait 用于横向复用代码。

```php
trait SoftDeleteTrait
{
    public function softDelete(): void
    {
        $this->deleted_at = date('Y-m-d H:i:s');
    }
}
```

面试解析：

- 定义能力契约，用 interface。
- 共享一部分基础实现，用 abstract class。
- 多个无继承关系的类需要复用方法，用 trait。
- trait 不应承载复杂业务规则，否则会让依赖关系变隐蔽。

## 4. 继承与组合

高级工程实践中，优先组合，谨慎继承。

继承的问题：

- 父类变化可能影响所有子类。
- 继承层级过深后，代码难以理解。
- 子类容易依赖父类内部细节。

组合示例：

```php
class OrderService
{
    public function __construct(
        private PaymentInterface $payment,
        private InventoryService $inventory
    ) {
    }

    public function createOrder(array $data): bool
    {
        $this->inventory->lock($data['sku_id']);

        return $this->payment->pay($data['amount']);
    }
}
```

面试解析：

组合让对象之间通过接口协作，替换实现更容易。例如支付可以从微信支付换成支付宝，不需要改 `OrderService` 主流程。

## 5. SOLID 原则

### S：单一职责原则

一个类只负责一类变化。

反例：

```php
class OrderController
{
    public function create()
    {
        // 参数校验
        // 库存扣减
        // 支付
        // 发短信
        // 写日志
    }
}
```

优化：

- Controller 负责接收请求。
- Service 负责业务流程。
- Repository 负责数据访问。
- Event / Job 负责异步任务。

### O：开闭原则

对扩展开放，对修改关闭。

常见做法：

- 使用接口。
- 使用策略模式。
- 使用事件机制。
- 使用配置映射。

### L：里氏替换原则

子类应该能替换父类，不破坏程序正确性。

### I：接口隔离原则

接口要小而专，不要设计大而全的接口。

反例：

```php
interface UserServiceInterface
{
    public function login();
    public function register();
    public function export();
    public function sendSms();
    public function resetPassword();
}
```

### D：依赖倒置原则

高层模块不依赖低层实现，而依赖抽象。

```php
class UserService
{
    public function __construct(private CacheInterface $cache)
    {
    }
}
```

面试解析：

SOLID 不是教条。它的价值是降低修改影响范围，让代码更容易测试、替换和扩展。

## 6. 依赖注入与控制反转

依赖注入是把对象依赖从类内部创建，改为从外部传入。

反例：

```php
class UserService
{
    private RedisCache $cache;

    public function __construct()
    {
        $this->cache = new RedisCache();
    }
}
```

优化：

```php
class UserService
{
    public function __construct(private CacheInterface $cache)
    {
    }
}
```

好处：

- 降低耦合。
- 便于单元测试。
- 便于替换实现。
- 便于统一管理对象生命周期。

Laravel 中的服务容器就是控制反转和依赖注入的典型实现。

面试解析：

控制反转是思想，依赖注入是实现方式。以前对象自己 new 依赖，现在由容器负责创建和注入依赖。

## 7. 常用设计模式

### 7.1 单例模式

保证一个类只有一个实例。

```php
class Config
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
```

面试解析：

传统 PHP-FPM 请求结束即释放，单例只在单次请求内有效。Swoole 常驻内存中，单例会跨请求存在，要注意状态污染。

### 7.2 工厂模式

用于封装对象创建逻辑。

```php
class PaymentFactory
{
    public function make(string $type): PaymentInterface
    {
        return match ($type) {
            'wechat' => new WechatPay(),
            'alipay' => new Alipay(),
            default => throw new InvalidArgumentException('invalid payment type'),
        };
    }
}
```

适合场景：

- 支付渠道创建。
- 短信渠道创建。
- 文件存储驱动创建。
- 第三方 API 客户端创建。

### 7.3 策略模式

将一组算法封装起来，运行时选择不同策略。

```php
interface DiscountStrategy
{
    public function calculate(int $amount): int;
}

class FullReductionDiscount implements DiscountStrategy
{
    public function calculate(int $amount): int
    {
        return $amount >= 10000 ? $amount - 1000 : $amount;
    }
}

class NoDiscount implements DiscountStrategy
{
    public function calculate(int $amount): int
    {
        return $amount;
    }
}
```

面试解析：

策略模式适合替换复杂 `if else`。比如优惠规则、风控规则、价格计算、物流计费。新增策略时新增类，不修改主流程。

### 7.4 观察者模式

一个事件发生后，通知多个观察者。

```php
interface EventListener
{
    public function handle(array $event): void;
}

class SendSmsListener implements EventListener
{
    public function handle(array $event): void
    {
        // send sms
    }
}
```

适合场景：

- 用户注册后发短信、发优惠券、写日志。
- 订单支付成功后发货、积分、通知。
- Laravel Event / Listener 就是观察者思想。

### 7.5 装饰器模式

在不修改原类的情况下增强功能。

```php
class CacheUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private CacheInterface $cache
    ) {
    }

    public function find(int $id): array
    {
        $key = 'user:' . $id;
        $user = $this->cache->get($key);

        if ($user) {
            return $user;
        }

        $user = $this->repository->find($id);
        $this->cache->set($key, $user, 300);

        return $user;
    }
}
```

适合场景：

- 给 Repository 增加缓存。
- 给 HTTP Client 增加日志。
- 给 Service 增加重试。

### 7.6 责任链模式

请求依次经过多个处理节点。

适合场景：

- 中间件。
- 风控规则。
- 表单校验。
- 审批流。

Laravel middleware 就是责任链思想。

```text
Request -> AuthMiddleware -> RateLimitMiddleware -> Controller -> Response
```

### 7.7 适配器模式

把不兼容接口转换成统一接口。

```php
class AliyunSmsAdapter implements SmsInterface
{
    public function __construct(private AliyunSmsClient $client)
    {
    }

    public function send(string $phone, string $content): bool
    {
        return $this->client->sendMessage($phone, $content);
    }
}
```

适合场景：

- 对接多个短信厂商。
- 对接多个支付渠道。
- 统一第三方 API 调用。

## 8. 工程分层规范

常见 PHP 后端分层：

```text
Controller：参数接收、权限校验、响应格式
Request：参数校验
Service：业务编排
Repository：数据访问
Model / Entity：数据模型
DTO：数据传输对象
Event / Listener：事件扩展
Job：异步任务
```

示例：

```text
app/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Services/
├── Repositories/
├── Models/
├── Events/
├── Listeners/
└── Jobs/
```

面试解析：

Controller 不应该写大量业务逻辑。Service 负责业务流程，Repository 负责数据访问。这样有利于测试、复用和维护。

## 9. 代码规范

推荐遵守：

- PSR-1：基础代码规范。
- PSR-4：自动加载规范。
- PSR-12：代码风格规范。

常见要求：

- 类名使用大驼峰：`UserService`
- 方法名使用小驼峰：`createOrder`
- 常量使用大写下划线：`MAX_RETRY_COUNT`
- 一个类一个文件。
- 命名空间与目录结构匹配。
- 避免超长方法和超大类。

工具：

- PHP-CS-Fixer
- PHP_CodeSniffer
- PHPStan
- Psalm
- PHPUnit

## 10. 异常与错误规范

建议：

- 业务异常和系统异常区分。
- 不要直接把底层异常暴露给用户。
- 日志里记录上下文和原始异常。
- API 响应使用统一错误码。

示例：

```php
class BusinessException extends RuntimeException
{
    public function __construct(
        string $message,
        private int $businessCode = 400
    ) {
        parent::__construct($message);
    }

    public function getBusinessCode(): int
    {
        return $this->businessCode;
    }
}
```

统一响应：

```json
{
  "code": 10001,
  "message": "库存不足",
  "data": null
}
```

面试解析：

高级工程师要能说明错误如何分类、如何记录、如何告警、如何给前端稳定响应，而不是只会 `try catch`。

## 11. 日志规范

日志应包含：

- request_id / trace_id
- user_id
- uri
- method
- params 摘要
- error message
- exception trace
- cost time

日志级别：

- debug：调试信息。
- info：正常业务节点。
- warning：可恢复异常。
- error：业务失败或系统异常。
- critical：严重故障，需要告警。

注意：

- 不记录密码、token、身份证等敏感信息。
- 第三方接口请求和响应要做脱敏。
- 慢请求要记录耗时。

## 12. 单元测试与可测试性

可测试代码特点：

- 依赖通过接口注入。
- 业务逻辑不依赖全局状态。
- 方法职责单一。
- 外部服务可以 mock。

示例：

```php
class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private PaymentInterface $payment
    ) {
    }
}
```

面试解析：

如果类里到处 `new Redis()`、`new PaymentClient()`，单元测试很难隔离外部依赖。依赖注入可以让测试传入 mock 对象。

## 13. 高级面试常见问法

### 你在项目中如何使用设计模式？

回答模板：

```text
我们在支付模块使用了策略模式和工厂模式。
不同支付渠道实现同一个 PaymentInterface。
工厂根据支付类型创建对应支付策略。
这样新增支付渠道时，只需要新增实现类并配置映射，不需要修改订单主流程。
```

### 设计模式是不是越多越好？

不是。设计模式是为了解决变化和复杂度，不是为了炫技。简单 CRUD 不需要强行套模式。只有当业务存在明显扩展点、重复判断、复杂依赖或多实现切换时，才值得引入。

### Service 层和 Repository 层怎么划分？

Service 负责业务流程，例如创建订单、扣库存、发消息。Repository 负责数据访问，例如查询订单、保存订单、更新状态。Service 可以调用多个 Repository，但 Repository 不应该包含业务编排逻辑。

### 如何避免代码越来越乱？

从几个方面控制：

- 分层清晰。
- 控制 Controller 体积。
- 复杂分支用策略、规则或状态机拆分。
- 公共能力抽接口。
- 写单元测试。
- 做代码评审。
- 用静态分析工具检查类型和死代码。

### Laravel 服务容器的价值是什么？

服务容器负责对象创建、依赖解析和生命周期管理。它让业务代码依赖接口而不是具体实现，便于替换实现、mock 测试和统一管理复杂依赖。

## 14. 面试前复习清单

必须能讲清楚：

- 面向对象三大特性。
- interface、abstract class、trait 区别。
- 继承和组合如何选择。
- SOLID 原则。
- 依赖注入和控制反转。
- 工厂、策略、观察者、装饰器、责任链、适配器。
- Controller、Service、Repository 分层。
- Composer PSR-4 自动加载。
- 统一异常、日志、错误码。
- 单元测试和 mock。


# Laravel、Yii2、CodeIgniter 4 框架源码面试手册

## 一、源码面试回答原则

框架源码问题不是背类名，而是证明你理解“请求如何进来、对象如何创建、路由如何匹配、中间件如何串联、ORM 如何查询、扩展点在哪里”。

通用回答结构：

```text
我重点看过入口启动、容器/服务定位、路由、中间件、控制器分发、ORM 查询构建和异常处理。
看源码的价值是知道框架边界，避免把业务逻辑塞错位置，也能在性能和扩展问题上定位到正确层级。
```

## 二、Laravel

### 1. 请求生命周期

1. `public/index.php` 加载 Composer 自动加载。
2. 创建 Application。
3. 解析 HTTP Kernel。
4. Request 进入 Kernel。
5. 经过全局 Middleware。
6. 路由匹配 Controller/Action。
7. 经过路由 Middleware。
8. 容器解析 Controller 依赖。
9. 执行业务逻辑并生成 Response。
10. 返回响应并执行 terminate。

### 2. 服务容器

容器负责对象创建、依赖注入和生命周期管理。

常见绑定：

- `bind`：每次解析新实例。
- `singleton`：单例。
- `instance`：绑定已有对象。
- 接口绑定实现：降低业务对具体类的耦合。

面试回答：

```text
Laravel 容器不是为了少写 new，而是为了解决复杂对象图的创建和依赖替换。
Controller、Job、Listener 等对象的构造函数依赖可以由容器通过反射自动解析。
```

### 3. Service Provider

Service Provider 是 Laravel 的启动扩展点。

- `register`：注册绑定，尽量不执行重逻辑。
- `boot`：所有 provider 注册后执行，可做路由、事件、视图、发布配置等启动动作。

### 4. Facade

Facade 表面是静态调用，本质是静态代理。它通过容器取出底层对象再调用方法。

面试注意：

- Facade 使用方便，但过度使用会隐藏依赖。
- 核心业务类更推荐构造函数注入接口，便于测试。

### 5. Middleware

中间件适合处理横切逻辑：

- 鉴权。
- 日志。
- 限流。
- CORS。
- trace id。
- 租户识别。

Laravel 使用 pipeline 思路把多个中间件串成洋葱模型。

### 6. Eloquent ORM

优点：

- Active Record 模式，上手快。
- 关联关系表达清晰。
- Scope、Accessor、Mutator 提升开发效率。

风险：

- N+1 查询。
- 隐式查询不易察觉。
- 大批量数据用模型循环处理成本高。
- 复杂报表 SQL 不适合强行 ORM。

优化：

- `with()` 预加载。
- `chunkById()` 分批。
- 必要时用 Query Builder 或原生 SQL。
- 查询只取需要字段。

## 三、Yii2

### 1. 请求生命周期

1. `web/index.php` 加载 Composer 和配置。
2. 创建 `yii\web\Application`。
3. Application 解析 Request。
4. UrlManager 匹配路由。
5. Controller 执行 Action。
6. 过滤器前置处理。
7. 执行业务逻辑。
8. Response 返回。

### 2. Component 与 Behavior

Yii2 中很多类继承 Component，支持事件和行为。

- Component：提供基础属性、事件和扩展机制。
- Behavior：把一组方法和事件响应挂到对象上，实现横向扩展。

面试回答：

```text
Behavior 适合通用横向能力，例如时间戳、操作者记录、软删除。
但复杂业务不要滥用 Behavior，否则依赖关系会变隐蔽。
```

### 3. DI Container

Yii2 容器可配置接口到实现，也支持构造函数依赖注入。大型项目中建议 Controller 调 Service，Service 依赖 Repository，避免 ActiveRecord 承载过多业务。

### 4. ActiveRecord

Yii2 AR 开发 CRUD 很快，但复杂业务应注意：

- 避免 Controller 直接堆 AR 操作。
- 避免循环查询导致 N+1。
- 复杂统计使用 Query Builder 或 SQL。
- 表单验证和业务校验要分层。

## 四、CodeIgniter 4

### 1. 请求生命周期

1. `public/index.php` 入口。
2. 启动框架并加载配置。
3. Router 匹配控制器方法。
4. Filter 前置处理。
5. Controller 调用 Model/Service。
6. 返回 Response。

### 2. CI4 特点

- 轻量，启动成本低。
- 约束少，上手快。
- Filter 处理鉴权、CSRF、限流。
- Model 提供基础数据访问。
- Service 类可集中创建常用服务。

### 3. 适用场景

CI4 适合中小型项目、后台管理、快速交付。复杂业务中要主动建立 Service、Repository、DTO、统一异常处理等工程约束，否则代码容易堆到 Controller。

## 五、三者对比

| 维度 | Laravel | Yii2 | CI4 |
| --- | --- | --- | --- |
| 生态 | 强 | 稳定 | 轻量 |
| 容器 | 强 | 有 | 较轻 |
| ORM | Eloquent | ActiveRecord | Model |
| 适合 | 中大型业务、生态型项目 | 管理系统、传统业务系统 | 中小项目、快速交付 |
| 风险 | 魔法方法多、隐式依赖 | AR 容易堆业务 | 约束不足 |

## 六、框架源码高频题

1. Laravel 一次请求怎么执行？
2. Service Container 解决什么问题？
3. Service Provider 的 register 和 boot 区别？
4. Facade 是静态类吗？
5. Middleware 的执行顺序是什么？
6. Laravel 事件和队列怎么用？
7. Eloquent N+1 怎么解决？
8. Yii2 Component 和 Behavior 是什么？
9. Yii2 Filter 和 Laravel Middleware 有什么类似点？
10. CI4 为什么轻量？
11. 框架中 Controller、Service、Repository 怎么分工？
12. 框架 ORM 慢怎么排查？
13. 如何在框架中做统一异常？
14. 如何做接口鉴权和权限控制？
15. 框架升级要注意什么？


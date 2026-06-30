# Go/PHP 一周复习计划

## 目标

一周内系统复习 Go 和 PHP 后端开发基础，重点覆盖语法、函数、面向对象、Web API、数据库、并发/缓存和综合项目。

细化复习目录：[Go_PHP_One_Week_Review](./Go_PHP_One_Week_Review/README.md)

建议每天安排：

- Go：1.5 小时
- PHP：1.5 小时
- 总结：30 分钟

最终产出：

- 每天完成对应练习代码
- 第 7 天完成一个用户订单 API 的 Go 版和 PHP 版
- 整理一份 Go/PHP 对比笔记

## Day 1：基础语法、类型、流程控制

Go 复习知识点：

- 变量、常量、基本类型
- 数组、切片、map
- 字符串、rune、byte
- `if`、`for`、`switch`
- 结构体基础

PHP 复习知识点：

- 变量、常量、字符串、数组
- 索引数组、关联数组
- 类型转换、弱类型比较
- `if`、`for`、`foreach`、`switch`
- PHP 8 的 `match`、空合并运算符 `??`

当天完成内容：

- Go 写一个字符串字符统计程序
- Go 写一个数组/切片去重程序
- Go 用结构体保存用户信息并输出
- PHP 写一个数组元素统计程序
- PHP 写一个订单关联数组并格式化输出
- 总结 Go 和 PHP 在类型系统上的区别

## Day 2：函数、错误处理、文件与 JSON

Go 复习知识点：

- 函数定义、多返回值
- 匿名函数、闭包
- 指针基础
- `error` 错误处理
- `panic`、`recover`
- 文件读写
- JSON 编码与解码

PHP 复习知识点：

- 函数、默认参数、可变参数
- 匿名函数、箭头函数
- 引用传参
- `try/catch/finally`
- 文件读写
- `json_encode`、`json_decode`

当天完成内容：

- Go 读取一个文本文件并统计行数
- Go 将用户列表写入 JSON 文件
- Go 从 JSON 文件读取并解析为结构体
- PHP 读取一个文本文件并统计行数
- PHP 将订单列表写入 JSON 文件
- PHP 从 JSON 文件读取并解析为数组
- 总结 Go 的 `error` 和 PHP 异常处理差异

## Day 3：面向对象、接口、模块组织

Go 复习知识点：

- 结构体方法
- 接口
- 接口隐式实现
- 组合
- 包管理
- `go mod`
- 项目目录组织

PHP 复习知识点：

- 类与对象
- 构造方法
- 继承
- 接口
- trait
- 命名空间
- Composer 自动加载

当天完成内容：

- Go 定义 `User` 结构体
- Go 定义 `UserRepository` 接口
- Go 用 map 实现用户增删改查
- Go 拆分为 `main`、`service`、`repository` 包
- PHP 定义 `User` 类
- PHP 定义 `UserRepositoryInterface`
- PHP 用数组实现用户增删改查
- PHP 使用命名空间组织代码
- 总结 Go 接口和 PHP 接口的区别

## Day 4：Web API 基础

Go 复习知识点：

- `net/http`
- Handler
- 路由处理
- 请求参数获取
- JSON 请求体解析
- JSON 响应
- HTTP 状态码

PHP 复习知识点：

- PHP Web 请求生命周期
- `$_GET`、`$_POST`、`$_SERVER`
- 请求参数处理
- JSON 输入读取
- JSON 响应输出
- 路由、控制器基础
- Laravel/ThinkPHP 基础路由

当天完成内容：

- Go 实现 `GET /users`
- Go 实现 `GET /users/{id}`
- Go 实现 `POST /users`
- Go 统一 JSON 响应格式
- PHP 实现同样的 3 个接口
- PHP 统一 JSON 响应格式
- 用 Postman 或 curl 测试所有接口
- 总结 Go 原生 HTTP 和 PHP 框架 Web 开发差异

## Day 5：数据库、事务、常见业务

Go 复习知识点：

- `database/sql`
- MySQL 驱动
- 连接池
- SQL 查询
- 参数绑定
- 事务
- 分页查询

PHP 复习知识点：

- PDO
- 预处理语句
- ORM 基础
- 数据库事务
- 分页查询
- SQL 注入防护

当天完成内容：

- 设计 `users` 表
- 设计 `orders` 表
- Go 实现用户 CRUD
- Go 实现订单创建
- Go 用事务处理创建订单
- PHP 实现用户 CRUD
- PHP 实现订单创建
- PHP 用事务处理创建订单
- 总结两门语言中数据库操作的差异

## Day 6：并发、缓存、队列、性能意识

Go 复习知识点：

- goroutine
- channel
- `sync.WaitGroup`
- `sync.Mutex`
- context
- 超时控制
- 限制并发数量

PHP 复习知识点：

- PHP-FPM 请求模型
- Redis 基础操作
- 缓存
- 分布式锁
- 计数器
- 简单队列
- 接口限流

当天完成内容：

- Go 写一个并发请求多个 URL 的程序
- Go 写一个 worker pool 任务处理程序
- Go 给 HTTP 请求加超时控制
- PHP 用 Redis 缓存用户详情
- PHP 用 Redis 实现访问计数器
- PHP 用 Redis 实现简单限流
- 总结 Go 并发模型和 PHP 请求模型差异

## Day 7：综合项目与复盘

Go 复习知识点：

- 项目结构
- Web API
- 数据库
- 参数校验
- 错误处理
- 日志
- 配置管理

PHP 复习知识点：

- MVC 分层
- 路由
- 控制器
- Service 层
- Repository 层
- 数据库事务
- 统一响应
- 异常处理

当天完成内容：

- 完成一个 Go 版用户订单 API
- 完成一个 PHP 版用户订单 API
- 至少包含用户注册、用户列表、用户详情、创建订单、订单列表、订单详情
- 所有接口返回统一 JSON 格式
- 所有写操作做参数校验
- 创建订单使用事务
- 写一份复盘笔记

复盘笔记必须包含：

- Go 和 PHP 类型系统区别
- Go 和 PHP 错误处理区别
- Go 和 PHP 面向对象/接口区别
- Go 和 PHP Web 开发区别
- Go 和 PHP 数据库操作区别
- Go 并发模型与 PHP-FPM 模型区别
- 自己最不熟的 5 个知识点
- 后续 2 周需要继续补强的内容

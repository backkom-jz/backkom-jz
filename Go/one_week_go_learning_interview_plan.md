# Go 语言一周学习与面试计划

## 目标

一周内不追求精通 Go，而是达到后端面试可表达、项目可落地、基础问题能回答的水平。

最终成果：

- 一个 Go Web CRUD 项目
- 一份项目 README
- 一份 Go 高频面试题笔记
- 一份项目讲解稿
- 一份自我介绍稿

## 每日时间安排

```text
09:00 - 11:00  学习核心知识
11:00 - 12:00  手写代码练习
14:00 - 17:00  项目实战
19:00 - 21:00  面试题整理
21:00 - 22:00  复盘与表达练习
```

## Day 1：Go 基础语法与开发环境

学习重点：

- `go mod`、`package`、`import`
- 基础类型：`int`、`string`、`bool`、`slice`、`map`、`struct`
- 函数、多返回值、命名返回值
- 指针基础
- `if`、`for`、`switch`
- `error` 错误处理

练习：

- 写一个 CLI 小程序，输入用户信息并格式化输出。
- 写 `slice` 和 `map` 的增删改查。
- 写 `struct` 和方法。

面试题：

- Go 和 Java/PHP/Python 的区别是什么？
- Go 为什么没有传统 class？
- slice 和 array 的区别？
- map 是否并发安全？
- Go 为什么不用异常作为主要错误处理方式？

## Day 2：函数、接口、错误处理

学习重点：

- 值接收者与指针接收者
- `interface`
- `interface{}` / `any`
- 类型断言、类型选择
- `defer`、`panic`、`recover`
- 错误包装：`fmt.Errorf("%w", err)`、`errors.Is`、`errors.As`

练习：

- 设计 `Storage` 接口，实现内存版和文件版。
- 写一个带错误包装的文件读取函数。
- 用 `defer` 管理资源释放。

面试题：

- Go interface 底层是什么？
- nil interface 为什么容易踩坑？
- defer 的执行顺序是什么？
- panic 和 error 的区别？
- 值接收者和指针接收者如何选择？

## Day 3：并发核心

学习重点：

- goroutine
- channel：无缓冲、有缓冲、关闭、遍历
- `select`
- `sync.WaitGroup`
- `sync.Mutex` / `sync.RWMutex`
- `context.Context`：超时、取消、链路传递

练习：

- 写一个 worker pool。
- 写一个生产者消费者模型。
- 用 context 控制任务超时取消。
- 写一个并发请求模拟器。

面试题：

- goroutine 和线程有什么区别？
- channel 关闭后读写行为是什么？
- select 有什么作用？
- 如何优雅停止 goroutine？
- context 主要解决什么问题？
- 如何避免 goroutine 泄漏？

## Day 4：内存、GC、调度模型

学习重点：

- 栈和堆
- 内存逃逸
- GMP 调度模型
- GC 基础：三色标记、写屏障
- `sync.Pool`
- 数据竞争
- `go test -race`

练习：

- 写一个会产生数据竞争的程序，再修复它。
- 用 `go test -race` 检查并发安全。
- 写简单 benchmark 比较不同实现。

面试题：

- Go GMP 模型是什么？
- goroutine 为什么轻量？
- Go GC 有什么特点？
- 什么是内存逃逸？
- `sync.Pool` 适合什么场景？
- 如何排查内存泄漏？

## Day 5：Web 后端开发

学习重点：

- `net/http`
- Gin 框架
- 路由、中间件、参数绑定
- JSON 序列化
- MySQL / PostgreSQL 连接
- `database/sql` 或 GORM
- Redis 基础使用

实战项目：用户管理服务。

接口：

- `POST /users` 创建用户
- `GET /users/:id` 查询用户
- `GET /users` 用户列表
- `PUT /users/:id` 更新用户
- `DELETE /users/:id` 删除用户

项目要求：

- 分层：`handler/service/repository/model`
- 参数校验
- 统一错误返回
- 日志
- context 传递

面试题：

- Go Web 项目怎么分层？
- Gin 中间件原理是什么？
- `database/sql` 连接池怎么配置？
- GORM 和原生 SQL 怎么选？
- 如何设计统一错误码？

## Day 6：项目强化与面试表达

学习重点：

- 配置管理：环境变量 / YAML
- 日志：`zap` / `slog`
- 单元测试
- mock 思路
- Dockerfile
- 常见后端设计题

项目增强：

- 添加配置文件。
- 添加日志。
- 添加单元测试。
- 添加 Dockerfile。
- 补充 README。

项目介绍模板：

```text
这个项目是一个 Go 实现的用户管理服务。
我采用 handler、service、repository 分层。
handler 负责参数解析和响应，service 负责业务逻辑，repository 负责数据库访问。
并发方面使用 context 控制请求生命周期。
错误处理采用统一错误码和包装错误，方便日志定位。
```

面试题：

- 如何设计限流？
- 如何设计登录鉴权？
- 如何处理高并发请求？
- 如何做接口幂等？
- 如何排查线上接口慢？

## Day 7：模拟面试与查漏补缺

上午复盘 Go 高频题：

- slice 扩容
- map 并发问题
- interface nil 坑
- defer 顺序
- goroutine 泄漏
- channel 关闭
- context 用法
- GMP
- GC
- error 处理
- mutex 和 channel 的选择

下午练项目讲解：

- 3 分钟自我介绍
- 3 分钟项目介绍
- 5 分钟技术细节
- 5 分钟面试追问

晚上模拟面试：

1. 自我介绍。
2. 为什么学习 Go？
3. Go 并发模型是什么？
4. 项目里怎么使用 context？
5. 如果接口 QPS 很高怎么办？
6. 数据库慢查询怎么排查？
7. goroutine 泄漏怎么发现？
8. 如何优雅关闭服务？
9. Go GC 会不会影响延迟？
10. 这个项目还有哪些可以优化？

## 优先级

如果时间不足，优先学习：

- Go 基础语法
- `slice` / `map` / `struct` / `interface`
- goroutine / channel / context
- Gin + MySQL
- 错误处理
- GMP / GC 基础
- 项目分层表达

## 核心策略

不要只刷语法，也不要只背面试题。用一个小项目把 Go 基础、并发、Web、数据库和面试表达串起来。

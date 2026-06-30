# MySQL 面试重点

## 1. 面试考察方向

高级 PHP 工程师的 MySQL 面试重点通常不是背 SQL 语法，而是考察：

- 表结构设计能力。
- 索引设计与 SQL 优化能力。
- 事务、锁、MVCC 理解。
- 慢查询排查能力。
- 高并发写入和一致性处理。
- 主从复制、读写分离、分库分表方案。
- 能否结合真实项目讲清楚问题和取舍。

## 2. MySQL 架构基础

MySQL 大致分为 Server 层和存储引擎层。

```text
客户端
-> 连接器
-> 查询缓存（MySQL 8 已移除）
-> 分析器
-> 优化器
-> 执行器
-> 存储引擎
```

Server 层负责：

- 连接管理
- SQL 解析
- SQL 优化
- 执行计划
- 函数、触发器、视图等

存储引擎层负责：

- 数据存储
- 索引实现
- 事务
- 锁
- 崩溃恢复

面试解析：

MySQL 的 SQL 优化器会根据统计信息选择执行计划，但不一定总是最优。线上慢 SQL 排查时，要通过 `explain` 和实际数据分布分析，而不是只看是否建了索引。

## 3. InnoDB 与 MyISAM

| 对比项 | InnoDB | MyISAM |
| --- | --- | --- |
| 事务 | 支持 | 不支持 |
| 行锁 | 支持 | 不支持，只支持表锁 |
| 外键 | 支持 | 不支持 |
| 崩溃恢复 | 支持 | 较弱 |
| MVCC | 支持 | 不支持 |
| 适合场景 | 绝大多数业务表 | 读多写少、历史遗留 |

面试回答：

现在业务开发一般优先选择 InnoDB，因为它支持事务、行级锁、MVCC 和崩溃恢复，更适合高并发业务系统。

## 4. B+Tree 索引

InnoDB 默认使用 B+Tree 索引。

特点：

- 非叶子节点只存 key 和指针。
- 叶子节点存完整数据或主键。
- 叶子节点之间通过链表连接。
- 树高较低，磁盘 IO 次数少。
- 适合范围查询和排序。

面试题：为什么 MySQL 使用 B+Tree 而不是 Hash？

回答：

Hash 查询等值匹配很快，但不支持范围查询、排序和最左前缀匹配。B+Tree 查询稳定，支持等值、范围、排序和前缀匹配，更适合数据库通用查询场景。

## 5. 聚簇索引与二级索引

InnoDB 表数据按照主键组织，主键索引就是聚簇索引。

```text
聚簇索引叶子节点：存整行数据
二级索引叶子节点：存索引字段 + 主键值
```

如果通过二级索引查询非索引字段，需要先查二级索引拿到主键，再通过主键回表查询整行数据。

面试题：什么是回表？

通过二级索引找到主键后，再回到聚簇索引查询完整数据的过程叫回表。

优化方向：

- 使用覆盖索引。
- 减少 `select *`。
- 合理设计联合索引。

## 6. 覆盖索引

如果查询字段都在索引里，就不需要回表，这叫覆盖索引。

示例：

```sql
select id, name from users where status = 1;
```

如果存在索引：

```sql
idx_status_name(status, name)
```

并且 `id` 是主键，二级索引叶子节点中已经包含主键，则可能直接通过索引返回结果。

面试解析：

覆盖索引可以减少回表次数，适合高频查询接口。但索引不是越多越好，索引会增加写入成本和存储成本。

## 7. 联合索引与最左前缀原则

联合索引示例：

```sql
create index idx_user_status_time on orders(user_id, status, created_at);
```

可以较好支持：

```sql
where user_id = ?
where user_id = ? and status = ?
where user_id = ? and status = ? and created_at > ?
```

不适合：

```sql
where status = ?
where created_at > ?
```

原因：

联合索引按字段顺序排序，先按 `user_id`，再按 `status`，再按 `created_at`。跳过最左字段时，后续字段整体上不是有序的。

索引设计原则：

- 区分度高的字段优先。
- 高频过滤字段优先。
- 等值条件字段通常放在范围条件前。
- 排序字段可以放入联合索引减少 filesort。
- 不要为了低频查询建立过多索引。

## 8. 索引失效场景

常见索引失效：

```sql
where left(name, 1) = 'a'
where age + 1 = 20
where name like '%tom'
where status != 1
where type is not null
where 字段类型和参数类型不一致
```

原因：

- 对索引字段使用函数。
- 对索引字段做计算。
- 前置通配符模糊查询。
- 隐式类型转换。
- 低选择性条件导致优化器放弃索引。

优化示例：

```sql
-- 不推荐
where date(created_at) = '2026-05-20'

-- 推荐
where created_at >= '2026-05-20 00:00:00'
  and created_at < '2026-05-21 00:00:00'
```

## 9. explain 执行计划

常看字段：

- `type`：访问类型。
- `possible_keys`：可能使用的索引。
- `key`：实际使用的索引。
- `rows`：预估扫描行数。
- `Extra`：额外信息。

`type` 常见级别：

```text
system > const > eq_ref > ref > range > index > ALL
```

一般来说：

- `const`、`eq_ref`、`ref` 较好。
- `range` 可接受。
- `index` 是全索引扫描。
- `ALL` 是全表扫描，需要重点关注。

Extra 常见值：

- `Using index`：覆盖索引。
- `Using where`：服务层过滤。
- `Using filesort`：额外排序。
- `Using temporary`：使用临时表。

面试解析：

不能只看是否使用索引，还要看扫描行数、过滤条件、排序方式和返回数据量。

## 10. 慢 SQL 排查流程

排查步骤：

1. 开启并查看慢查询日志。
2. 找到高频或耗时长 SQL。
3. 使用 `explain` 分析执行计划。
4. 查看索引是否合理。
5. 分析数据量和数据分布。
6. 检查是否返回过多字段或过多行。
7. 优化 SQL 或调整表结构。
8. 必要时加缓存、异步化、分表或归档。

常见优化手段：

- 避免 `select *`。
- 建合适联合索引。
- 使用覆盖索引。
- 避免深分页。
- 避免在索引字段上使用函数。
- 大表历史数据归档。
- 热点查询加缓存。

## 11. 深分页优化

问题 SQL：

```sql
select * from orders order by id limit 100000, 20;
```

MySQL 需要扫描并丢弃前 100000 行，性能差。

优化方案：

```sql
select * from orders
where id > 100000
order by id
limit 20;
```

或者先查主键：

```sql
select * from orders
where id in (
    select id from orders order by id limit 100000, 20
);
```

面试解析：

深分页优化核心是减少无效扫描。常用方式是基于上一页最大 id 的游标分页，或者先用覆盖索引查主键，再回表。

## 12. 事务 ACID

事务四大特性：

- Atomicity 原子性：事务内操作要么全部成功，要么全部失败。
- Consistency 一致性：事务前后数据满足约束。
- Isolation 隔离性：并发事务之间相互隔离。
- Durability 持久性：事务提交后数据持久保存。

面试解析：

ACID 不是孤立概念。原子性依赖 undo log，持久性依赖 redo log，隔离性依赖锁和 MVCC。

## 13. 事务隔离级别

| 隔离级别 | 脏读 | 不可重复读 | 幻读 |
| --- | --- | --- | --- |
| 读未提交 | 可能 | 可能 | 可能 |
| 读已提交 | 不会 | 可能 | 可能 |
| 可重复读 | 不会 | 不会 | 基本解决 |
| 串行化 | 不会 | 不会 | 不会 |

MySQL InnoDB 默认隔离级别是可重复读。

面试题：什么是幻读？

同一个事务中，两次按相同条件查询，第二次出现了第一次没有的新增行，这就是幻读。

InnoDB 在可重复读级别下，通过 MVCC 和 next-key lock 在很多场景下解决幻读问题。

## 14. MVCC

MVCC 是多版本并发控制。

InnoDB 每行记录有隐藏字段：

- `trx_id`：最近修改该行的事务 id。
- `roll_pointer`：指向 undo log 版本链。

查询时会生成 Read View，判断哪个版本对当前事务可见。

面试解析：

MVCC 让读写可以并发执行，普通快照读不加锁，提高并发性能。但当前读，如 `select ... for update`，会加锁读取最新数据。

快照读：

```sql
select * from users where id = 1;
```

当前读：

```sql
select * from users where id = 1 for update;
update users set name = 'a' where id = 1;
```

## 15. 锁机制

常见锁：

- 表锁
- 行锁
- 间隙锁
- next-key lock
- 意向锁

行锁基于索引实现。

如果更新条件没有走索引，可能导致扫描大量记录并加锁，甚至表现接近锁表。

示例：

```sql
update users set status = 1 where phone = '13800000000';
```

如果 `phone` 没有索引，可能扫描大量行并加锁，影响并发。

面试题：如何减少锁冲突？

- 更新条件走索引。
- 控制事务大小。
- 避免长事务。
- 固定资源访问顺序，减少死锁。
- 热点数据拆分。
- 使用乐观锁。

## 16. 死锁

死锁是两个或多个事务互相等待对方持有的锁。

示例：

```text
事务 A 锁住 order_1，等待 order_2
事务 B 锁住 order_2，等待 order_1
```

排查方式：

```sql
show engine innodb status;
```

优化方式：

- 保持相同加锁顺序。
- 减少事务范围。
- 为查询条件添加合适索引。
- 避免用户交互中长时间持有事务。
- 捕获死锁异常后重试。

## 17. redo log、undo log、binlog

### redo log

InnoDB 层日志，用于崩溃恢复，保证持久性。

### undo log

用于事务回滚和 MVCC 版本链。

### binlog

Server 层日志，用于主从复制和数据恢复。

面试题：redo log 和 binlog 区别？

- redo log 是 InnoDB 引擎层，binlog 是 MySQL Server 层。
- redo log 是物理日志，binlog 偏逻辑日志。
- redo log 用于崩溃恢复，binlog 用于复制和恢复。
- redo log 循环写，binlog 追加写。

## 18. 主从复制

基本流程：

```text
主库写入 binlog
从库 IO 线程拉取 binlog
写入 relay log
从库 SQL 线程重放 relay log
```

主从延迟原因：

- 主库写入压力大。
- 从库重放慢。
- 大事务。
- 网络延迟。
- 从库配置较差。

处理方式：

- 避免大事务。
- 提升从库配置。
- 并行复制。
- 关键读走主库。
- 业务上接受最终一致性。

## 19. 读写分离

读写分离：

- 写请求走主库。
- 读请求走从库。

问题：

- 主从延迟导致刚写入的数据读不到。

解决方案：

- 写后短时间内读主库。
- 根据业务一致性要求路由。
- 使用缓存承接热点读。
- 对强一致接口不走从库。

面试解析：

读写分离不能盲目使用。涉及支付、订单状态、权限等强一致场景，通常需要读主库或做一致性保护。

## 20. 分库分表

什么时候考虑分库分表：

- 单表数据过大，索引和查询性能下降。
- 单库写入压力过大。
- 数据增长趋势明确。

常见分片键：

- `user_id`
- `order_id`
- `tenant_id`

设计原则：

- 分片键尽量出现在高频查询条件中。
- 避免跨库事务。
- 避免跨库 join。
- 提前设计全局唯一 ID。
- 考虑扩容和迁移成本。

面试题：分库分表后会带来什么问题？

- 跨库查询复杂。
- 跨库事务困难。
- 全局唯一 ID。
- 分页排序困难。
- 数据迁移复杂。
- 运维成本增加。

所以分库分表不是优先方案，通常先做索引优化、缓存、归档、读写分离，再考虑拆分。

## 21. 表结构设计

设计原则：

- 主键建议使用自增 bigint 或分布式 ID。
- 字段类型尽量小。
- 金额用整数分存储，避免 float。
- 时间字段统一使用 `datetime` 或 `timestamp`，团队内保持一致。
- 状态字段用 tinyint。
- 高频查询字段建索引。
- 避免过多 nullable 字段。
- 大字段拆到扩展表。

示例：

```sql
create table orders (
    id bigint unsigned primary key auto_increment,
    order_no varchar(64) not null,
    user_id bigint unsigned not null,
    status tinyint unsigned not null default 0,
    amount int unsigned not null default 0,
    created_at datetime not null,
    updated_at datetime not null,
    unique key uk_order_no(order_no),
    key idx_user_status_time(user_id, status, created_at)
) engine=InnoDB default charset=utf8mb4;
```

## 22. 常见项目案例回答模板

### 慢 SQL 优化案例

```text
当时订单列表接口响应超过 2 秒。
我先通过慢查询日志定位到 SQL，再用 explain 发现走了全表扫描，并且有 Using filesort。
原因是查询条件包含 user_id、status，并按 created_at 排序，但原来只有单列索引。
我增加了联合索引 idx_user_status_time(user_id, status, created_at)，同时将 select * 改为只查列表需要的字段。
优化后扫描行数明显下降，接口响应从 2 秒以上降到 200ms 左右。
```

### 主从延迟案例

```text
用户下单后立即查询订单详情，偶尔查不到。
排查后发现读请求走了从库，主从复制有延迟。
解决方案是订单创建后的短时间内按用户维度强制读主库，或者根据订单号查询时走主库。
同时监控主从延迟，超过阈值时自动切换关键读请求到主库。
```

### 库存扣减案例

```text
库存扣减需要防止超卖。
方案上可以使用数据库条件更新：
update goods set stock = stock - 1 where id = ? and stock > 0;
然后判断 affected rows。
高并发场景下，可以先用 Redis 预扣库存，再通过 MQ 异步落库，并保证订单创建幂等。
```

## 23. 高频面试题速答

### MySQL 索引是不是越多越好？

不是。索引可以提升查询，但会增加写入、更新和删除成本，也会占用磁盘空间。索引要围绕高频查询、过滤、排序和关联条件设计。

### count(*)、count(1)、count(column) 区别？

`count(*)` 和 `count(1)` 都统计行数，MySQL 通常会优化。`count(column)` 只统计该字段非 null 的行。一般统计总行数用 `count(*)`。

### varchar 和 char 区别？

`char` 定长，适合长度固定字段。`varchar` 变长，适合长度变化字段。多数业务字符串用 `varchar`。

### datetime 和 timestamp 区别？

`timestamp` 会受时区影响，范围较小。`datetime` 不受时区转换影响，范围更大。业务系统中常统一使用 `datetime`，也可以按团队规范选择。

### delete、truncate、drop 区别？

- `delete` 删除数据，可带 where，逐行删除，可回滚。
- `truncate` 清空整表，速度快，通常会重置自增值。
- `drop` 删除表结构和数据。

### 如何避免大事务？

- 控制单次处理数据量。
- 分批提交。
- 避免事务中调用外部接口。
- 避免事务中等待用户操作。
- 尽快提交或回滚。

## 24. 面试前必背清单

- InnoDB 和 MyISAM 区别。
- B+Tree 索引原理。
- 聚簇索引、二级索引、回表、覆盖索引。
- 联合索引与最左前缀原则。
- 索引失效场景。
- explain 常见字段。
- 慢 SQL 排查流程。
- 事务 ACID。
- 隔离级别。
- MVCC。
- 行锁、间隙锁、next-key lock。
- 死锁排查。
- redo log、undo log、binlog。
- 主从复制和主从延迟。
- 读写分离。
- 分库分表的收益和代价。
- 表结构设计原则。


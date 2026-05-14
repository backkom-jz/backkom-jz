# MySQL 学习笔记（按专题整理）

> 目标：从“会写 SQL”进阶到“能解释为什么快/慢、稳/不稳”。

## 一、基础篇

### 1. MySQL 执行流程

一条 SQL 从客户端发送到 MySQL Server 后，大致经过以下流程：

客户端 -> 连接器 -> 查询缓存（MySQL 8.0 已废弃） -> 解析器 -> 预处理器 -> 优化器 -> 执行器 -> 存储引擎

#### 1.1 连接器

连接器负责客户端连接、身份认证、权限校验和连接管理。

常用命令：

```sql
show variables like 'max_connections';
```

`max_connections` 表示 MySQL 允许的最大连接数。连接过多可能报错：

```text
Too many connections
```

#### 1.2 查询缓存

MySQL 8.0 已移除查询缓存。MySQL 8.0 之前，查询缓存以 key-value 保存在内存：

- key = SQL 语句
- value = 查询结果

相关参数：

```sql
show variables like 'query_cache_type';
```

常见值：

- `OFF`：关闭查询缓存
- `ON`：开启查询缓存
- `DEMAND`：按需使用查询缓存

查询缓存的问题是：只要表数据发生变更，该表相关缓存就会失效；高并发写入场景收益通常较低。

#### 1.3 解析 SQL

解析分两个阶段：

- 词法分析：识别关键字和标识符
- 语法分析：判断是否符合 MySQL 语法规则

示例 SQL：

```sql
select * from user where id = 1;
```

若语法错误，会直接返回：

```text
You have an error in your SQL syntax
```

#### 1.4 预处理器

预处理器主要负责：

- 检查表是否存在
- 检查字段是否存在
- 检查语义是否合法
- 展开 `select *`

示例：

```sql
select name from user;
```

会检查 `user` 表是否存在、`name` 字段是否存在。

#### 1.5 优化器

优化器负责生成执行计划，决定使用哪种执行方式，例如：

- 使用哪个索引
- 表连接顺序
- 是否全表扫描
- 是否使用覆盖索引
- 是否使用索引下推

查看执行计划：

```sql
explain select * from user where id = 1;
```

#### 1.6 执行器

执行器根据优化器生成的执行计划调用存储引擎接口，获取数据并返回结果。常见执行方式包括：

- 主键索引查询
- 二级索引查询
- 回表查询
- 全表扫描
- 覆盖索引
- 索引下推

---

### 2. 一行记录是如何存储的

#### 2.1 MySQL 表相关文件

查看数据目录：

```sql
show variables like 'datadir';
```

MySQL 5.7 常见文件：

- `db.opt`
- `*.frm`
- `*.ibd`

说明：

- `db.opt`：数据库默认字符集和排序规则
- `*.frm`：表结构定义
- `*.ibd`：InnoDB 表数据与索引

MySQL 8.0 之后：

- 废弃 `*.frm`，表结构信息存储在数据字典中
- 常见文件仍有 `*.ibd`

注：文件扩展名是 `*.ibd`，不是 `*.idb`。

#### 2.2 InnoDB 存储结构

InnoDB 逻辑存储结构：

表空间 -> 段（Segment）-> 区（Extent）-> 页（Page）-> 行（Row）

页（Page）是 InnoDB 管理磁盘的基本单位，默认大小：

- `16KB`

查看页大小：

```sql
show variables like 'innodb_page_size';
```

区（Extent）由连续页组成，默认：

- 1 个区 = 64 个页
- 1 个页 = 16KB
- 1 个区 = 1MB

段（Segment）用于组织不同类型的数据，例如：

- 数据段
- 索引段
- 回滚段

---

### 3. InnoDB 行格式

常见行格式：

- `Redundant`
- `Compact`
- `Dynamic`
- `Compressed`

常用的是 `Compact` 和 `Dynamic`；MySQL 5.7/8.0 默认一般是 `Dynamic`。

查看默认行格式：

```sql
show variables like 'innodb_default_row_format';
```

#### 3.1 Compact 行格式

一行记录大致由以下部分组成：

- 变长字段长度列表
- NULL 值列表
- 记录头信息
- 隐藏字段
- 真实字段数据

结构示意：

```text
| 变长字段长度列表 | NULL值列表 | 记录头信息 | row_id | trx_id | roll_pointer | 真实字段 |
```

隐藏字段说明：

- `row_id`：隐藏主键（仅在无主键且无唯一非空索引时生成）
- `trx_id`：最近一次修改该记录的事务 ID
- `roll_pointer`：回滚指针，指向 undo log

注意：如果表有主键，不会生成 `row_id`。

记录头信息常见字段：

- `delete_mask`：标记记录是否被删除
- `next_record`：指向下一条记录
- `record_type`：记录类型

---

### 4. VARCHAR 和行大小限制

MySQL 单行记录存在大小限制。除 `TEXT`、`BLOB` 等大对象外，其他字段一行总长度不能超过约：

- `65535` 字节

注意：这是 MySQL Server 层限制，不是 InnoDB 单页 `16KB` 的限制。

`varchar(n)` 中 `n` 的含义是字符数，不是字节数。不同字符集下一个字符占用字节不同：

- `ascii`：1 字节
- `utf8`：最多 3 字节
- `utf8mb4`：最多 4 字节

示例：

```text
varchar(100) 在 utf8mb4 下最多可占 400 字节
```

行大小大致包含：

- 非大对象字段字节数
- 变长字段长度列表
- NULL 值列表
- 记录头信息
- 隐藏字段

可粗略理解为：

```text
上述总和 < 65535 字节
```

---

### 5. 行溢出

InnoDB 页默认大小 16KB。若一行数据过大，可能无法完整放入一个页，此时会发生行溢出（部分数据放入溢出页）。

`Compact` 行格式：

- 当前页存放：部分真实数据 + 溢出页地址

`Dynamic` / `Compressed` 行格式：

- 当前页尽量只存放：溢出页地址
- 真实数据主要放在溢出页

---

## 二、索引篇

### 1. 索引分类

#### 1.1 按数据结构分类

- B+ 树索引
- Hash 索引
- Full-text 全文索引

InnoDB 默认使用的是 B+ 树索引。

#### 1.2 按物理存储分类

- 聚簇索引
- 二级索引

#### 1.3 按字段特性分类

- 主键索引
- 唯一索引
- 普通索引
- 前缀索引
- 全文索引

#### 1.4 按字段个数分类

- 单列索引
- 联合索引

---

### 2. 聚簇索引和二级索引

#### 2.1 聚簇索引

InnoDB 中，聚簇索引叶子节点存储完整行数据。通常主键索引就是聚簇索引：

- 主键索引叶子节点 = 完整行数据

如果表没有主键：

1. 优先选择一个唯一非空索引作为聚簇索引
2. 若不存在，自动生成隐藏 `row_id` 作为聚簇索引

#### 2.2 二级索引

二级索引叶子节点存储：

- 索引字段值 + 主键值

若查询字段无法由二级索引覆盖，需要根据主键值回到聚簇索引取完整行，这个过程叫回表。

---

### 3. InnoDB 和 MyISAM 索引区别

`InnoDB`：

- 聚簇索引叶子节点存完整行
- 二级索引叶子节点存主键值

`MyISAM`：

- B+ 树叶子节点存数据文件物理地址
- 索引和数据分开存储

---

### 4. B+ 树相关数据结构

常见数据结构包括：

- 二叉查找树
- 平衡二叉树（AVL）
- 红黑树
- B 树
- B+ 树

InnoDB 选择 B+ 树主要因为：

- 树更矮胖，磁盘 IO 次数更少
- 非叶子节点仅存键值和指针，可容纳更多分支
- 叶子节点通过链表连接，范围查询更高效
- 数据都在叶子节点，查询性能更稳定

---

### 5. 为什么常说单表不要超过 2000 万行

“单表不要超过 2000 万行”不是 MySQL 硬性限制，而是工程经验值。主要原因：

1. B+ 树层级可能增加，查询 IO 成本变高
2. 数据量越大，索引维护成本越高
3. DDL、备份、恢复、迁移成本上升
4. 大事务、慢查询、锁等待风险更高
5. Buffer Pool 命中率可能下降

是否需要分表，取决于：

- 单行大小
- 索引数量
- 查询模式
- 硬件配置
- Buffer Pool 大小
- QPS/TPS
- 业务增长速度

---

### 6. 索引优化

#### 6.1 前缀索引优化

对于长字符串字段可只索引前缀：

```sql
create index idx_email on user(email(10));
```

适用于 `varchar` / `text` 等长字段。

优点：

- 索引更小
- 查询更快（通常）
- 磁盘 IO 更低

缺点：

- 不能天然覆盖完整字段查询
- 区分度不足时效果差

#### 6.2 覆盖索引优化

若查询字段都在索引中，可避免回表。

```sql
create index idx_name_age on user(name, age);
select name, age from user where name = 'Tom';
```

该查询只扫描二级索引，不需要回表。

#### 6.3 主键最好自增

InnoDB 主键索引是聚簇索引。主键自增通常带来顺序写，插入更友好。

主键无序（如 UUID）可能导致：

- 页分裂
- 随机 IO
- 索引碎片
- 插入性能下降

通常推荐：自增 `bigint`（或趋势递增的主键方案）。

#### 6.4 索引字段尽量 `NOT NULL`

原因：

- 减少 NULL 相关比较与统计复杂度
- 避免三值逻辑带来的查询歧义
- 降低优化器额外判断开销

示例：

```sql
name varchar(64) not null default '';
age int not null default 0;
```

#### 6.5 防止索引失效

常见失效场景与优化方式：

1) 左模糊或全模糊匹配

```sql
-- 可能失效
where name like '%abc';
where name like '%abc%';

-- 可利用索引
where name like 'abc%';
```

2) 对索引字段使用函数

```sql
-- 不推荐
where date(create_time) = '2026-05-14';

-- 推荐
where create_time >= '2026-05-14 00:00:00'
  and create_time <  '2026-05-15 00:00:00';
```

3) 对索引字段做表达式计算

```sql
-- 不推荐
where age + 1 = 18;

-- 推荐
where age = 17;
```

4) 隐式类型转换

```sql
-- phone 为 varchar(20)
-- 不推荐
where phone = 13800138000;

-- 推荐
where phone = '13800138000';
```

5) 联合索引不满足最左前缀

```sql
create index idx_a_b_c on t(a, b, c);
```

通常可较好使用索引：

- `where a = 1`
- `where a = 1 and b = 2`
- `where a = 1 and b = 2 and c = 3`

效果不稳定或可能较差：

- `where b = 2`
- `where c = 3`
- `where b = 2 and c = 3`

6) `OR` 条件

```sql
where a = 1 or b = 2;
```

若其中一个条件缺少索引，容易退化为全表扫描。

---

### 7. COUNT 计数优化

#### 7.1 `count` 的含义

`count(expr)` 统计的是 `expr` 非 `NULL` 的记录数。

#### 7.2 `count(*)` 和 `count(1)`

```sql
select count(*) from user;
select count(1) from user;
```

在 InnoDB 中两者通常性能接近，通常推荐 `count(*)`（语义更清晰）。

#### 7.3 `count(主键)`

```sql
select count(id) from user;
```

若 `id` 主键且非空，结果与 `count(*)` 一致；性能一般也接近，但不作为首选表达。

#### 7.4 `count(普通字段)`

```sql
select count(name) from user;
```

统计的是 `name` 非空数量。若字段可空，结果可能小于 `count(*)`；是否快取决于字段索引和执行计划。

#### 7.5 性能经验

通常可粗略理解为：

`count(*) ≈ count(1) >= count(主键) >= count(普通字段)`

但最终以 `EXPLAIN` 和实际压测为准。在很多场景下，优化器会优先选择更窄的二级索引来完成 `count(*)` 扫描。

---

## 三、事务篇

### 1. ACID 与 InnoDB 落地机制

- 原子性（A）：通过 `undo log` 保证，失败可回滚。
- 一致性（C）：依赖应用约束 + 引擎约束（主键、唯一键、外键、检查约束）。
- 隔离性（I）：通过 MVCC + 锁机制实现。
- 持久性（D）：通过 `redo log` + 刷盘策略保证。

记忆方式：

- `undo` 负责“回得去”
- `redo` 负责“丢不了”

### 2. 事务开启与提交流程

默认 `autocommit = 1`，每条 DML 会独立提交。显式事务示例：

```sql
begin;
update account set balance = balance - 100 where id = 1;
update account set balance = balance + 100 where id = 2;
commit;
```

异常回滚：

```sql
rollback;
```

### 3. 并发异常与隔离级别

并发异常：

- 脏读：读到未提交数据
- 不可重复读：同一事务两次读同一行结果不同
- 幻读：同一事务两次范围查询返回记录数量不同

隔离级别（InnoDB）：

- `READ UNCOMMITTED`：可能脏读
- `READ COMMITTED`：避免脏读，仍可能不可重复读
- `REPEATABLE READ`（默认）：通过 MVCC + Next-Key Lock 抑制大部分幻读
- `SERIALIZABLE`：强串行化，吞吐最低

查看当前隔离级别：

```sql
show variables like 'transaction_isolation';
```

### 4. MVCC 核心机制（面试高频）

每条记录的隐藏信息（简化理解）：

- `trx_id`：最后修改该行的事务 ID
- `roll_pointer`：指向旧版本（undo 记录）

`Read View` 关键要素：

- 当前活跃事务集合
- 最小活跃事务 ID
- 最大已分配事务 ID

通过可见性规则决定“当前事务该读哪个版本”，实现读写并发。

### 5. 快照读与当前读

- 快照读：普通 `select`，基于 MVCC，不加锁
- 当前读：读取最新版本并加锁，典型语句：
  - `select ... for update`
  - `select ... for share`（或旧语法 `lock in share mode`）
  - `update/delete/insert`

### 6. 事务提交与两阶段提交（理解版）

简化过程：

1. 写 `redo log`（prepare）
2. 写 `binlog`
3. 写 `redo log`（commit）

目的是保证崩溃恢复后，存储引擎状态与复制日志状态一致。

### 7. 实战建议

- 事务尽量短，减少锁持有时间与 undo 堆积。
- 避免事务中放耗时操作（远程调用、复杂计算、文件 IO）。
- 固定访问顺序（按主键升序）降低死锁概率。
- 扣减库存建议单 SQL 原子条件更新：

```sql
update sku set stock = stock - 1
where id = 1001 and stock > 0;
```

根据影响行数判断是否成功，再结合幂等与重试策略。

---

## 四、锁篇

### 1. 锁类型总览
- 按粒度：表锁、行锁。
- 按模式：共享锁（S）与排他锁（X）。
- 意向锁：`IS/IX`，用于快速判断表锁与行锁是否冲突。
- 行级相关锁：记录锁、间隙锁、临键锁（Next-Key Lock）。

### 2. InnoDB 加锁规则（重点）
- 行锁是“加在索引项上”，不是按物理行号加锁。
- 命中唯一索引等值查询，通常锁范围最小。
- 未命中索引时，可能扩大锁范围，显著降低并发。
- 范围更新/查询在 `REPEATABLE READ` 下常见 Next-Key Lock。
- `READ COMMITTED` 下间隙锁通常更少（特定场景仍会出现）。

### 3. 锁等待与死锁

死锁本质是循环等待。InnoDB 会自动检测并回滚代价较小的事务。

查看锁等待超时参数：

```sql
show variables like 'innodb_lock_wait_timeout';
```

查看最近一次死锁：

```sql
show engine innodb status;
```

业务侧建议：

- 固定访问顺序（例如都按主键升序更新）
- 缩短事务
- 减少单事务锁定记录数
- 捕获死锁错误并做有限重试

### 4. 线上排查视角（MySQL 8.0）

可以结合 `performance_schema` 观察锁链路：

- `performance_schema.data_locks`
- `performance_schema.data_lock_waits`

排查步骤：

1. 找阻塞源头事务
2. 看阻塞 SQL 是否走索引
3. 判断是否大事务或批量更新引起
4. 优化 SQL/索引，或拆小批处理

### 5. 常见误区

- “用了主键就一定无锁冲突”是错的。
- “`for update` 一定只锁一行”是错的。
- “加索引后一定无锁等待”是错的（热点写仍会冲突）。

---

## 五、日志篇

### 1. Redo Log（重做日志）
- InnoDB 引擎层日志，记录页修改，用于崩溃恢复（crash-safe）。
- 采用 WAL（先写日志再落数据页），提升写入吞吐。

关键参数：

```sql
show variables like 'innodb_flush_log_at_trx_commit';
```

常见取值：

- `1`：每次提交都写并刷盘（最安全）
- `2`：每次提交写 OS cache，周期刷盘
- `0`：周期写与刷（性能高，风险更高）

### 2. Undo Log（回滚日志）
- 记录数据修改前的旧版本。
- 用于事务回滚，也支撑 MVCC 的历史版本读取。
- 长事务会导致 undo 积压，影响后台 purge 回收。

### 3. Binlog（归档日志）
- Server 层逻辑日志，记录逻辑变更，用于主从复制和数据恢复。
- 常见格式：`STATEMENT`、`ROW`、`MIXED`（生产常见 `ROW`）。

关键参数：

```sql
show variables like 'sync_binlog';
```

生产常见取值：`sync_binlog = 1`（一致性更稳）。

### 4. 两阶段提交（Redo + Binlog）
- 为保证引擎层和 Server 层日志一致性，提交过程使用两阶段提交。
- 核心步骤：Redo prepare -> Binlog 落盘 -> Redo commit。
- 目标：避免“binlog 有记录但数据没落盘”或反过来的不一致。

### 5. 其他常用日志
- 慢查询日志：定位慢 SQL。
- 错误日志：定位启动失败、崩溃、主从异常。
- 中继日志（relay log）：从库复制过程使用。

慢日志常看参数：

```sql
show variables like 'slow_query_log';
show variables like 'long_query_time';
```

### 6. 日志配置思路

- 高一致场景：`innodb_flush_log_at_trx_commit = 1` + `sync_binlog = 1`
- 高吞吐且允许少量风险：可放宽其一，但必须先评估 RPO
- 日志保留策略需和备份联动，保证支持 PITR（按时间点恢复）

---

## 六、内存篇

### 1. Buffer Pool（核心）
- InnoDB 的数据页和索引页缓存区，热点数据优先在内存命中。
- 关键指标：`Buffer Pool Hit Rate`（命中率）。
- 一般是内存调优最先关注项。

关键参数：

```sql
show variables like 'innodb_buffer_pool_size';
show variables like 'innodb_buffer_pool_instances';
```

### 2. Change Buffer / Adaptive Hash Index
- `Change Buffer`：缓冲部分二级索引变更，合并后落盘，优化随机写。
- `Adaptive Hash Index`：将热点 B+Tree 搜索路径哈希化，提升点查性能。
- 高并发场景下可能出现哈希竞争，需结合监控评估是否保留。

### 3. 连接与执行相关内存
- `sort_buffer_size`、`join_buffer_size`、`tmp_table_size` 等会影响排序/连接/临时表。
- 还需关注：`read_buffer_size`、`read_rnd_buffer_size`、`max_heap_table_size`。
- 这类参数很多是“每连接/每操作”生效，不能盲目调大。

### 4. 内存问题排查思路
- 命中率低：看是否 SQL/索引不合理，或内存分配不足。
- 临时表频繁落磁盘：检查排序和分组 SQL，优化索引与参数。
- 连接数过高：检查连接池配置和慢事务堆积。

### 5. 常见性能抖动来源

- 脏页刷盘跟不上，前台查询被刷盘拖慢。
- `group by/order by/distinct` 触发大量临时表和 filesort。
- 连接数过高叠加大 buffer 参数，导致内存压力与抖动。

### 6. 调优顺序建议

1. 先优化 SQL 与索引
2. 再调连接池和并发上限
3. 最后谨慎放大内存参数

---

## 七、架构篇

### 1. 常见演进路径
- 单机 MySQL。
- 主从复制（读写分离）。
- 多副本高可用（自动故障切换）。
- 分库分表（解决容量和吞吐上限）。

### 2. 主从复制核心
- 主库写 `binlog`，从库 IO 线程拉取到 relay log，再由 SQL 线程重放。
- 复制模式：
  - 异步：性能好，主从有延迟风险。
  - 半同步：一致性更好，性能略受影响。
- 建议优先启用 GTID，方便故障切换和拓扑管理。
- 业务层必须考虑“写后读一致性”。

### 3. 高可用与容灾
- 高可用组件：如 `MHA`、`Orchestrator`、`ProxySQL`（按团队技术栈选型）。
- 备份策略：全量 + 增量 + binlog。
- 容灾演练：定期做恢复演练，保证备份可用。

核心目标：

- RTO（恢复时间目标）：多久恢复服务
- RPO（恢复点目标）：最多丢多少数据

### 4. 分库分表要点
- 垂直拆分：按业务域拆库。
- 水平拆分：按分片键拆表（如用户 ID）。
- 难点：跨库事务、全局 ID、跨分片查询和分页。

分片键选择原则：

- 分布均匀（避免热点）
- 稳定不变（避免迁移成本）
- 尽量高频出现在查询条件中

### 5. 监控指标建议
- QPS、TPS、慢 SQL 数量。
- 复制延迟、活跃连接数、锁等待。
- Buffer Pool 命中率、磁盘 IO、CPU 使用率。

建议增加告警维度：

- P95/P99 响应时间
- 死锁次数、回滚次数
- 从库复制线程状态（IO/SQL 线程）

### 6. 读写分离常见问题

- 写后立刻读可能读到从库旧数据（主从延迟）。
- 大事务提交后，从库回放滞后明显。
- 复杂查询全部压到从库，导致从库雪崩。

常见兜底：

- 关键读强制走主库（短时间窗口）。
- 按延迟阈值动态摘除异常从库。

---

## 附：面试高频问题速记
- MySQL 为什么用 B+Tree，而不是红黑树？
- 联合索引什么情况下会失效？
- `REPEATABLE READ` 下如何理解幻读？
- 行锁为什么会“锁范围变大”？
- Redo/Undo/Binlog 分别解决什么问题？
- 为什么有了 Buffer Pool 还会慢？
- 主从延迟出现后，业务如何兜底？

## 附：学习与实战建议
- 先建立“执行流程 + 索引 + 事务 + 锁 + 日志”的统一认知框架。
- 每学一块都用真实 SQL 做 `EXPLAIN` 和压测验证。
- 形成自己的故障排查手册：慢 SQL、死锁、主从延迟、磁盘打满。

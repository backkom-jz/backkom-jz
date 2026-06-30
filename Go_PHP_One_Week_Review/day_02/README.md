# Day 2：函数、错误处理、文件与 JSON

## 今日目标

今天要掌握 Go 和 PHP 中“把代码组织成可复用单元”的方式，并能处理真实后端开发中最常见的三类问题：错误处理、文件读写、JSON 编码解码。复习完成后，要能独立写出读取文件、写入 JSON、解析 JSON 的小程序。

## 一、Go 基础知识体系

### 1. 函数定义

Go 函数使用 `func` 定义。

```go
func add(a int, b int) int {
    return a + b
}
```

相同类型的参数可以合并写。

```go
func add(a, b int) int {
    return a + b
}
```

必须掌握：

- 函数参数需要明确类型
- 返回值也需要明确类型
- Go 函数可以返回多个值
- Go 函数没有默认参数
- Go 不支持函数重载

### 2. 多返回值

Go 常用多返回值同时返回结果和错误。

```go
func divide(a, b int) (int, error) {
    if b == 0 {
        return 0, fmt.Errorf("division by zero")
    }

    return a / b, nil
}
```

调用时要处理两个返回值。

```go
result, err := divide(10, 2)
if err != nil {
    fmt.Println(err)
    return
}

fmt.Println(result)
```

必须掌握：

- `result, err := fn()` 是 Go 中非常常见的写法
- 成功时 `err` 通常为 `nil`
- 失败时返回零值和非 nil 错误
- 不要忽略重要错误

### 3. 命名返回值

Go 支持给返回值命名。

```go
func buildUser() (name string, age int) {
    name = "Tom"
    age = 20
    return
}
```

注意点：

- 命名返回值适合短函数
- 长函数中裸 `return` 容易降低可读性
- 业务代码中不要滥用命名返回值

### 4. 匿名函数和闭包

匿名函数可以赋值给变量，也可以立即执行。

```go
sum := func(a, b int) int {
    return a + b
}

fmt.Println(sum(1, 2))
```

立即执行：

```go
func() {
    fmt.Println("run now")
}()
```

闭包可以捕获外层变量。

```go
func counter() func() int {
    count := 0

    return func() int {
        count++
        return count
    }
}
```

必须掌握：

- 函数在 Go 中是一等值，可以作为变量、参数、返回值
- 闭包会引用外部变量
- 闭包适合做状态封装，但不要滥用

### 5. 指针基础

指针保存变量的内存地址。

```go
name := "Tom"
p := &name

fmt.Println(*p)
```

修改指针指向的值：

```go
func changeName(name *string) {
    *name = "Jack"
}
```

必须掌握：

- `&变量` 获取地址
- `*指针` 取出指针指向的值
- 指针可以让函数修改外部变量
- Go 没有 C 语言那种指针运算

使用场景：

- 函数需要修改传入对象
- 避免复制较大的结构体
- 表示可选值或空值

### 6. error 错误处理

Go 的错误处理以显式返回 `error` 为主。

```go
func findUser(id int) (string, error) {
    if id <= 0 {
        return "", fmt.Errorf("invalid user id: %d", id)
    }

    return "Tom", nil
}
```

调用方必须判断错误。

```go
name, err := findUser(0)
if err != nil {
    fmt.Println("find user failed:", err)
    return
}

fmt.Println(name)
```

必须掌握：

- `error` 是接口类型
- `nil` 表示没有错误
- 错误要尽早处理
- 错误信息要能帮助定位问题

### 7. 错误包装

Go 可以使用 `%w` 包装错误，保留原始错误链。

```go
func readConfig(path string) error {
    _, err := os.ReadFile(path)
    if err != nil {
        return fmt.Errorf("read config %s failed: %w", path, err)
    }

    return nil
}
```

判断错误：

```go
if errors.Is(err, os.ErrNotExist) {
    fmt.Println("file not found")
}
```

必须掌握：

- `%w` 用于包装错误
- `errors.Is` 判断错误链中是否包含某个错误
- `errors.As` 提取特定错误类型
- 不要用字符串包含判断错误类型

### 8. panic 和 recover

`panic` 表示程序遇到不可恢复的问题。

```go
panic("something wrong")
```

`recover` 可以在 `defer` 中捕获 panic。

```go
func safeRun() {
    defer func() {
        if r := recover(); r != nil {
            fmt.Println("recover:", r)
        }
    }()

    panic("failed")
}
```

必须掌握：

- 普通业务错误使用 `error`
- 不要用 `panic` 替代正常错误处理
- `recover` 只有在 `defer` 函数中才有效
- `panic` 更适合不可继续执行的严重问题

### 9. defer

`defer` 用于延迟执行，常见于释放资源。

```go
file, err := os.Open("data.txt")
if err != nil {
    return err
}
defer file.Close()
```

必须掌握：

- `defer` 在函数返回前执行
- 多个 `defer` 后进先出执行
- 文件、锁、连接等资源释放常用 `defer`

### 10. 文件读写

读取整个文件：

```go
data, err := os.ReadFile("data.txt")
if err != nil {
    return err
}

fmt.Println(string(data))
```

写入文件：

```go
err := os.WriteFile("users.json", data, 0644)
if err != nil {
    return err
}
```

逐行读取：

```go
file, err := os.Open("data.txt")
if err != nil {
    return err
}
defer file.Close()

scanner := bufio.NewScanner(file)
for scanner.Scan() {
    line := scanner.Text()
    fmt.Println(line)
}

if err := scanner.Err(); err != nil {
    return err
}
```

必须掌握：

- 小文件可以用 `os.ReadFile`
- 大文件或日志文件适合逐行读取
- 打开文件后要关闭
- 文件错误必须处理

### 11. JSON 编码与解码

结构体转 JSON：

```go
type User struct {
    ID   int    `json:"id"`
    Name string `json:"name"`
}

user := User{ID: 1, Name: "Tom"}

data, err := json.Marshal(user)
if err != nil {
    return err
}
```

JSON 转结构体：

```go
var user User
err := json.Unmarshal(data, &user)
if err != nil {
    return err
}
```

格式化 JSON：

```go
data, err := json.MarshalIndent(user, "", "  ")
```

必须掌握：

- `json.Marshal` 编码
- `json.Unmarshal` 解码
- 解码时要传指针
- 结构体字段需要导出，也就是首字母大写
- JSON 字段名通过 struct tag 控制

## 二、PHP 基础知识体系

### 1. 函数定义

PHP 使用 `function` 定义函数。

```php
function add(int $a, int $b): int
{
    return $a + $b;
}
```

必须掌握：

- 参数可以声明类型
- 返回值可以声明类型
- PHP 8 推荐明确类型
- 没有返回值可以声明 `void`

```php
function logMessage(string $message): void
{
    echo $message;
}
```

### 2. 默认参数

PHP 支持默认参数。

```php
function greet(string $name = "guest"): string
{
    return "hello " . $name;
}
```

注意点：

- 默认参数一般放在参数列表后面
- 默认值应简单明确
- 不要用默认参数隐藏复杂业务逻辑

### 3. 可变参数

PHP 使用 `...` 表示可变参数。

```php
function sum(int ...$nums): int
{
    $total = 0;

    foreach ($nums as $num) {
        $total += $num;
    }

    return $total;
}
```

必须掌握：

- `...$nums` 会把多个参数收集为数组
- 适合不确定参数数量的场景
- 业务代码中要注意参数含义清晰

### 4. 匿名函数和箭头函数

匿名函数：

```php
$add = function (int $a, int $b): int {
    return $a + $b;
};
```

箭头函数：

```php
$double = fn (int $num): int => $num * 2;
```

必须掌握：

- 匿名函数适合回调
- 箭头函数适合简单表达式
- 箭头函数会自动捕获外层变量
- 普通匿名函数捕获外部变量需要 `use`

```php
$rate = 0.8;

$calc = function (float $price) use ($rate): float {
    return $price * $rate;
};
```

### 5. 引用传参

PHP 可以通过 `&` 引用传参。

```php
function increase(int &$num): void
{
    $num++;
}

$count = 1;
increase($count);
```

注意点：

- 引用传参会修改外部变量
- 一般业务代码中要谨慎使用
- 返回新值通常比修改外部变量更清晰

### 6. 异常处理

PHP 常用异常处理错误。

```php
try {
    throw new RuntimeException("something wrong");
} catch (RuntimeException $e) {
    echo $e->getMessage();
} finally {
    echo "done";
}
```

必须掌握：

- `try` 包住可能失败的代码
- `catch` 捕获异常
- `finally` 无论是否异常都会执行
- 异常对象包含 message、code、file、line、trace

常见异常类型：

- `Exception`
- `RuntimeException`
- `InvalidArgumentException`
- `PDOException`

### 7. 主动抛出异常

```php
function findUser(int $id): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException("invalid user id");
    }

    return [
        "id" => $id,
        "name" => "Tom",
    ];
}
```

调用方捕获：

```php
try {
    $user = findUser(0);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

必须掌握：

- 参数错误可以使用 `InvalidArgumentException`
- 运行时失败可以使用 `RuntimeException`
- 数据库错误常见 `PDOException`
- 不要把异常吞掉不处理

### 8. 文件读写

读取整个文件：

```php
$content = file_get_contents("data.txt");

if ($content === false) {
    throw new RuntimeException("read file failed");
}
```

写入文件：

```php
$result = file_put_contents("users.json", $json);

if ($result === false) {
    throw new RuntimeException("write file failed");
}
```

逐行读取：

```php
$handle = fopen("data.txt", "r");

if ($handle === false) {
    throw new RuntimeException("open file failed");
}

while (($line = fgets($handle)) !== false) {
    echo $line;
}

fclose($handle);
```

必须掌握：

- 小文件可以用 `file_get_contents`
- 写文件可以用 `file_put_contents`
- 大文件适合 `fopen` + `fgets`
- 文件操作可能返回 `false`，必须判断

### 9. JSON 编码与解码

数组转 JSON：

```php
$user = [
    "id" => 1,
    "name" => "Tom",
];

$json = json_encode($user, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
```

JSON 转数组：

```php
$data = json_decode($json, true);
```

必须掌握：

- `json_encode` 编码
- `json_decode($json, true)` 解码为关联数组
- `JSON_UNESCAPED_UNICODE` 避免中文被转义
- `JSON_PRETTY_PRINT` 格式化输出
- JSON 解析失败要检查错误

检查 JSON 错误：

```php
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new RuntimeException(json_last_error_msg());
}
```

PHP 7.3 之后也可以使用异常模式：

```php
$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
```

## 三、Go 与 PHP 对比

| 对比项 | Go | PHP |
| --- | --- | --- |
| 函数类型 | 参数和返回值必须明确 | 可不写类型，但推荐写 |
| 多返回值 | 原生支持 | 不支持原生多返回值，常用数组或对象 |
| 错误处理 | 显式返回 `error` | 常用异常 |
| 资源释放 | 常用 `defer` | 手动关闭或依赖生命周期 |
| 文件读取 | `os.ReadFile`、`bufio.Scanner` | `file_get_contents`、`fopen` |
| JSON 解码 | `json.Unmarshal` 到结构体 | `json_decode` 到数组或对象 |
| 数据结构 | struct 更明确 | array 更灵活 |

## 四、常见错误

Go 常见错误：

- 忽略 `err`
- JSON 解码时没有传指针
- 结构体字段小写导致 JSON 编码结果为空
- 文件打开后忘记 `Close`
- 把普通业务错误写成 `panic`

PHP 常见错误：

- `file_get_contents` 返回 `false` 后没有判断
- `json_decode` 后没有检查解析错误
- 异常被捕获后只打印不处理
- 过度使用引用传参
- 业务数组结构不固定，导致后续读取 key 报错

## 五、今日作业

### 作业 1：Go 统计文件行数

要求：

- 创建 `data.txt`
- 文件中至少写入 5 行文本
- 使用 Go 逐行读取文件
- 统计总行数并输出
- 如果文件不存在，要返回清晰错误

验收点：

- 使用 `os.Open`
- 使用 `bufio.Scanner`
- 使用 `defer file.Close()`
- 正确处理 `scanner.Err()`

### 作业 2：Go 用户列表写入 JSON

要求：

- 定义 `User` 结构体
- 字段包含 `ID`、`Name`、`Age`、`Mobile`
- 创建 3 个用户
- 使用 `json.MarshalIndent`
- 写入 `users.json`

验收点：

- 结构体字段首字母大写
- 添加正确的 `json` tag
- 使用 `os.WriteFile`
- 能看到格式化后的 JSON 文件

### 作业 3：Go 读取 JSON 为结构体

要求：

- 读取作业 2 生成的 `users.json`
- 解析成 `[]User`
- 输出年龄大于等于 18 的用户

验收点：

- 使用 `os.ReadFile`
- 使用 `json.Unmarshal`
- 解码时传入切片指针
- 正确处理 JSON 解析错误

### 作业 4：PHP 统计文件行数

要求：

- 创建 `data.txt`
- 文件中至少写入 5 行文本
- 使用 PHP 逐行读取
- 统计总行数并输出
- 文件打开失败时抛出异常

验收点：

- 使用 `fopen`
- 使用 `fgets`
- 使用 `fclose`
- 使用 `RuntimeException` 处理失败

### 作业 5：PHP 订单列表写入 JSON

要求：

- 用数组创建 3 条订单数据
- 字段包含 `order_no`、`user_id`、`amount`、`status`
- 使用 `json_encode`
- 写入 `orders.json`

验收点：

- 使用关联数组
- 使用 `JSON_UNESCAPED_UNICODE`
- 使用 `JSON_PRETTY_PRINT`
- 判断 `file_put_contents` 的返回值

### 作业 6：PHP 读取 JSON 并格式化输出

要求：

- 读取作业 5 生成的 `orders.json`
- 解析为数组
- 使用 `match` 把订单状态转成中文文本
- 输出订单摘要

状态码：

- `1`：待支付
- `2`：已支付
- `3`：已取消

验收点：

- 使用 `file_get_contents`
- 使用 `json_decode($json, true)`
- 检查 JSON 解析错误
- 使用 `match`

### 作业 7：对比总结

写一段不少于 300 字的总结，回答下面问题：

- Go 为什么习惯使用 `result, err`，而 PHP 更常用异常？
- Go 的 `defer` 对资源释放有什么帮助？
- JSON 在 Go 中为什么更适合解码到结构体？
- PHP 中 `json_decode($json, true)` 和不传 `true` 有什么区别？
- 文件读取时，小文件和大文件分别应该用什么方式？

## 六、今日完成标准

- 能写出 Go 函数并正确处理多返回值
- 能解释 Go 的 `error`、`panic`、`recover` 区别
- 能用 Go 完成文件读取、文件写入和 JSON 解析
- 能写出 PHP 函数并使用类型声明
- 能用 PHP 异常处理文件和 JSON 错误
- 完成 7 个作业并保存代码

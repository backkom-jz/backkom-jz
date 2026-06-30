# Day 1：基础语法、类型、流程控制

## 今日目标

今天要把 Go 和 PHP 的基础语法重新过一遍，重点掌握变量、类型、数组/切片/map、字符串、流程控制和基础数据建模。复习完成后，要能独立写出字符串统计、数组去重、用户信息建模和订单数据格式化。

## 一、Go 基础知识体系

### 1. 程序结构

Go 程序通常由 `package`、`import`、函数和类型定义组成。

```go
package main

import "fmt"

func main() {
    fmt.Println("hello go")
}
```

必须掌握：

- `package main` 表示可执行程序入口包
- `func main()` 是程序入口函数
- `import` 用来引入标准库或第三方包
- Go 代码必须格式化，常用 `gofmt`

### 2. 变量与常量

Go 是静态类型语言，变量类型在编译期确定。

```go
var name string = "Tom"
var age int = 20
city := "Shanghai"

const statusActive = 1
```

必须掌握：

- `var` 可以显式声明类型
- `:=` 只能在函数内部使用
- 常量使用 `const`
- 未赋值变量会有零值

常见零值：

- `int`：`0`
- `float64`：`0`
- `bool`：`false`
- `string`：`""`
- 指针、slice、map、函数、接口：`nil`

### 3. 基础类型

常用类型：

- 整数：`int`、`int64`、`uint`
- 浮点数：`float32`、`float64`
- 布尔：`bool`
- 字符串：`string`
- 字节：`byte`，本质是 `uint8`
- 字符：`rune`，本质是 `int32`，常用于 Unicode 字符

注意点：

- Go 不会自动做隐式类型转换
- 不同数字类型之间需要显式转换

```go
var a int = 10
var b int64 = int64(a)
```

### 4. 数组、切片、map

数组长度固定，长度也是类型的一部分。

```go
var nums [3]int = [3]int{1, 2, 3}
```

切片长度可变，是 Go 中更常用的数据结构。

```go
nums := []int{1, 2, 3}
nums = append(nums, 4)
```

map 是键值对集合。

```go
scores := map[string]int{
    "Tom": 90,
    "Jack": 88,
}

score, ok := scores["Tom"]
if ok {
    fmt.Println(score)
}
```

必须掌握：

- 数组适合固定长度数据
- 切片适合动态列表
- `append` 可能触发底层数组扩容
- map 查询要使用 `value, ok`
- map 删除使用 `delete(m, key)`
- map 不是并发安全的

### 5. 字符串、byte、rune

Go 字符串是只读字节序列。处理英文字符可以用 `byte`，处理中文等 Unicode 字符要用 `rune`。

```go
s := "hello中国"

for i, b := range []byte(s) {
    fmt.Println(i, b)
}

for i, r := range s {
    fmt.Println(i, r)
}
```

必须掌握：

- `len(s)` 返回字节数，不是字符数
- `range string` 按 rune 遍历
- 修改字符串需要先转成 `[]rune` 或 `[]byte`

### 6. 流程控制

Go 只有 `for`，没有 `while`。

```go
for i := 0; i < 10; i++ {
    fmt.Println(i)
}

for _, v := range nums {
    fmt.Println(v)
}
```

`if` 可以带初始化语句。

```go
if score, ok := scores["Tom"]; ok {
    fmt.Println(score)
}
```

`switch` 默认每个 case 自动 break。

```go
switch status {
case 1:
    fmt.Println("active")
case 2:
    fmt.Println("disabled")
default:
    fmt.Println("unknown")
}
```

### 7. 结构体

结构体用于表达业务对象。

```go
type User struct {
    ID     int
    Name   string
    Age    int
    Mobile string
}

user := User{
    ID:     1,
    Name:   "Tom",
    Age:    20,
    Mobile: "13800000000",
}
```

必须掌握：

- 字段名首字母大写表示包外可访问
- 字段名首字母小写表示包内可访问
- 结构体适合承载业务数据
- 结构体字段可以加 tag，后续 JSON 和数据库会用到

```go
type User struct {
    ID   int    `json:"id"`
    Name string `json:"name"`
}
```

## 二、PHP 基础知识体系

### 1. 程序结构

PHP 文件通常以 `<?php` 开头。

```php
<?php

echo "hello php";
```

必须掌握：

- PHP 脚本可以直接运行，也可以通过 Web Server/PHP-FPM 执行
- 变量以 `$` 开头
- 语句通常以分号结尾

### 2. 变量与常量

PHP 是动态类型语言，变量类型由运行时值决定。

```php
$name = "Tom";
$age = 20;

const STATUS_ACTIVE = 1;
define("APP_NAME", "demo");
```

必须掌握：

- 变量不用提前声明类型
- 同一个变量可以被赋值为不同类型
- 常量可以用 `const` 或 `define`
- PHP 8 支持更严格的类型声明，但默认仍有弱类型特征

### 3. 基础类型

常用类型：

- 整数：`int`
- 浮点数：`float`
- 布尔：`bool`
- 字符串：`string`
- 数组：`array`
- 对象：`object`
- 空值：`null`

类型判断：

```php
is_int($age);
is_string($name);
is_array($items);
```

注意点：

- PHP 存在弱类型比较问题
- 优先使用 `===` 和 `!==`
- 少用 `==`，避免隐式转换导致判断错误

```php
var_dump(0 == "0");   // true
var_dump(0 === "0");  // false
```

### 4. 字符串

```php
$name = "Tom";
$message = "hello {$name}";
$length = strlen($message);
```

必须掌握：

- 双引号字符串会解析变量
- 单引号字符串通常不解析变量
- `strlen` 返回字节长度
- 中文长度处理要使用 `mb_strlen`
- 字符串拼接使用 `.`

```php
$fullName = $firstName . $lastName;
```

### 5. 数组

PHP 数组同时承担列表和 map 的角色。

索引数组：

```php
$nums = [1, 2, 3];
$nums[] = 4;
```

关联数组：

```php
$user = [
    "id" => 1,
    "name" => "Tom",
    "age" => 20,
];
```

必须掌握：

- PHP 数组非常灵活，但要注意结构约定
- 列表适合保存一组同类数据
- 关联数组适合表示一条业务记录
- `isset($arr["key"])` 判断 key 是否存在且值不为 null
- `array_key_exists("key", $arr)` 只判断 key 是否存在

### 6. 流程控制

```php
if ($age >= 18) {
    echo "adult";
} else {
    echo "minor";
}
```

循环：

```php
foreach ($users as $user) {
    echo $user["name"];
}

foreach ($users as $index => $user) {
    echo $index . ":" . $user["name"];
}
```

`switch`：

```php
switch ($status) {
    case 1:
        echo "active";
        break;
    case 2:
        echo "disabled";
        break;
    default:
        echo "unknown";
}
```

PHP 8 `match`：

```php
$label = match ($status) {
    1 => "active",
    2 => "disabled",
    default => "unknown",
};
```

必须掌握：

- `foreach` 是 PHP 数组遍历最常用方式
- `switch` 使用弱比较，要小心类型转换
- `match` 使用严格比较，并且会返回值

### 7. 空值处理

空合并运算符：

```php
$name = $input["name"] ?? "unknown";
```

必须掌握：

- `??` 用于处理可能不存在的 key
- `empty()` 会把 `0`、`"0"`、空字符串、空数组都视为 empty
- 业务判断中不要滥用 `empty()`

## 三、Go 与 PHP 基础差异

| 对比项 | Go | PHP |
| --- | --- | --- |
| 类型系统 | 静态类型 | 动态类型，支持类型声明 |
| 类型转换 | 必须显式转换 | 经常发生隐式转换 |
| 数组结构 | 数组、切片、map 分开 | array 同时承担列表和 map |
| 字符串遍历 | byte/rune 区分明显 | 需要注意 `strlen` 和 `mb_strlen` |
| 错误暴露 | 编译期能发现大量类型错误 | 很多问题在运行时暴露 |
| 业务建模 | 常用 struct | 可用 array 或 class |

## 四、今日作业

### 作业 1：Go 字符统计

要求：

- 输入字符串：`"hello 世界 hello"`
- 统计每个字符出现次数
- 中文字符要按字符统计，不能按字节统计
- 输出格式自定，但要能清楚看到字符和次数

验收点：

- 使用 `map[rune]int`
- 使用 `range` 遍历字符串
- 能正确统计中文字符

### 作业 2：Go 切片去重

要求：

- 输入：`[]int{1, 2, 2, 3, 4, 4, 5}`
- 输出去重后的切片
- 保持原始出现顺序

验收点：

- 使用 `map[int]bool` 或 `map[int]struct{}`
- 使用 `append`
- 输出结果为 `[1 2 3 4 5]`

### 作业 3：Go 用户结构体

要求：

- 定义 `User` 结构体
- 字段包含 `ID`、`Name`、`Age`、`Mobile`
- 创建 3 个用户
- 用切片保存用户列表
- 遍历输出所有成年用户

验收点：

- 使用 `struct`
- 使用 `[]User`
- 使用 `for range`
- 使用 `if` 判断年龄

### 作业 4：PHP 数组元素统计

要求：

- 输入：`["php", "go", "php", "mysql", "go", "php"]`
- 统计每个元素出现次数
- 输出关联数组

验收点：

- 使用 `foreach`
- 使用关联数组作为计数器
- 正确输出 `php => 3`、`go => 2`、`mysql => 1`

### 作业 5：PHP 订单数据格式化

要求：

- 用关联数组表示一笔订单
- 字段包含 `order_no`、`user_id`、`amount`、`status`
- 使用 `match` 把状态码转成状态文本
- 输出订单摘要

状态码：

- `1`：待支付
- `2`：已支付
- `3`：已取消

验收点：

- 使用关联数组
- 使用 `match`
- 使用字符串拼接或模板输出

### 作业 6：对比总结

写一段不少于 300 字的总结，回答下面问题：

- Go 为什么更容易在编译期发现类型错误？
- PHP 的数组为什么方便，但也容易让业务结构混乱？
- Go 的 `map` 和 PHP 的关联数组有什么相似点和不同点？
- 处理中文字符串时，两门语言分别要注意什么？

## 五、今日完成标准

- 能写出 Go 基础程序并运行
- 能写出 PHP 基础脚本并运行
- 能解释 Go 静态类型和 PHP 动态类型的区别
- 能解释切片、map、PHP 数组的常见使用场景
- 完成 6 个作业并保存代码

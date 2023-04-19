# 网络请求
>👋 Hi, I’m @backkom-jz
## Http协议
> 一个HTTP请求报文由请求头（header）、请求行（request line）、空行和请求数据组成

### 请求头
```
请求头部由关键字 / 值对组成，每行一对，关键字和值用英文冒号 “:” 分隔。
请求头部通知服务器有关于客户端请求的信息，典型的 请求头有：
UserAgent：产生请求的浏览器类型。
Accept：客户端可识别的内容类型列表。
Host：请求的主机名，允许多个域名同处一个 IP 地址，即虚拟主机。

###################常见请求头#######################
// 信息型状态码，提示目前为止一切正常，客户端应该继续请求，如果已完成请求则忽略.
header(‘HTTP/1.1 100 OK’);
// 通知浏览器 页面不存在
header(‘HTTP/1.1 404 Not Found’);
// 资源被永久的重定向 301 ;302：临时重定向（该资源临时被改变位置）
header(‘HTTP/1.1 301 Moved Permanently’);
// 跳转到一个新的地址
header(‘Location: php.itcast.cn/');
// 延迟转向也就是隔几秒跳转
header(‘Refresh:10;url=php.itcast.cn/');



###################内容类型#######################
// 网页编码
header(‘Content-Type: text/html;charset=utf-8’);
// 纯文本格式
header(‘Content-Type:text/plain’);
//JPG、JPEG
header(‘Content-Type:image/jpeg’);
//ZIP 文件
header(‘Content-Type:application/zip’);
//PDF 文件
header(‘Content-Type:application/pdf’);
// 音频文件
header(‘Content-Type: ‘);
//css 文件
header(‘Content-type:text/css’);
### 声明一个下载的文件 ###
header(‘Content-Type:application/octet-stream’);
header(‘Content-Disposition:attachment;filename=”ITblog.zip”‘);
### 显示一个需要验证的登陆对话框 ###
header(‘HTTP/1.1 401Unauthorized’);
header(‘WWW-Authenticate:Basic realm=”TopSecret”‘);
```

### 请求行
```
请求行由请求方法字段、URL字段和HTTP协议版本字段三个字段组成，它们用空格分隔。
```
### 空行
```
请求头之后是一个空行，发送回车符和换行符，通知服务器以下不再有请求头。
```

### 请求数据
```
请求数据不在 GET 方法中使用，而是在 POST 方法中使用。POST 方法适用于需要客户填写表单的场合。
与请求数据相关的常使 用的请求头是 ContentType 和 ContentLength。
```

## HTTP相关报文
> Http响应由三个部分组成，分别是：状态行、消息响应头、响应报文

### 状态码
```
1xx: 请求已接受，继续处理
2xx: 请求已被成功接收、理解、接受
3xx: 重定向 要完成请求必须更进一步的操作
4xx: 客户端错误 请求由语法错误或者请求无法实现
5xx: 服务器错误 服务器未能实现合法的请求
```

### 常见状态码
```
200 OK：客户端请求成功。
400 Bad Request：客户端请求有语法错误，不能被服务器所理解。
401 Unauthorized：请求未经授权，这个状态代码必须和 WWW Authenticate 报头域一起使用。
403 Forbidden：服务器收到请求，但是拒绝提供服务。
404 Not Found：请求资源不存在，举个例子：输入了错误的 URL。 500 Internal Server Error：服务器发生不可预期的错误。
503 Server Unavailable：服务器当前不能处理客户端的请求，一段时间后可能恢复正常，举个例子：HTTP/1.1 200 OK（CRLF）。
```


## PHP基础函数
### 魔术方法
```
__autoload () 类文件自动加载函数

```



## PHP算法

### 斐波那契数列
```injectablephp
function fibonacci($n){
    if($n == 0){
        return 0;
    }else if ($n == 1){
        return 1;
    }else{
        return fibonacci($n -1) + fibonacci($n -2);
    }
}

$n = 10;
for ($i = 0; $i <$n; $i++){
    echo fibonacci($i).PHP_EOL;
}
```
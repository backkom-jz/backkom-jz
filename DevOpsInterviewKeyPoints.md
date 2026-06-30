# Docker、Kubernetes、Nginx、Linux 运维面试手册

## 一、Docker

### 1. 镜像与容器

镜像是分层只读文件系统，容器是在镜像基础上增加可写层后运行的进程。容器不是虚拟机，它共享宿主机内核，隔离依赖 namespace 和 cgroups。

高频题：

- **Dockerfile 怎么优化？**  
  合理利用缓存，减少无效层，使用多阶段构建，不把密钥写入镜像，固定基础镜像版本。

- **容器日志怎么处理？**  
  应用写 stdout/stderr，由 Docker 或 K8s 日志采集系统统一收集。

- **容器内时间、文件、网络异常怎么查？**  
  先确认镜像、环境变量、挂载卷、DNS、网络模式和容器资源限制。

### 2. 常用命令

```bash
docker build -t app:latest .
docker run -d --name app -p 8080:80 app:latest
docker ps
docker logs -f app
docker exec -it app sh
docker inspect app
```

## 二、Kubernetes

### 1. 核心对象

- Pod：最小调度单元。
- Deployment：管理副本、滚动升级和回滚。
- Service：提供稳定访问入口。
- Ingress：七层 HTTP 入口。
- ConfigMap：普通配置。
- Secret：敏感配置。
- HPA：自动扩缩容。
- PVC：持久化存储声明。

### 2. 高频题

**Pod 一直重启怎么排查？**

```bash
kubectl describe pod <pod>
kubectl logs <pod> --previous
kubectl get events --sort-by=.metadata.creationTimestamp
```

重点看：

- 应用启动报错。
- 配置缺失。
- 端口不匹配。
- liveness 探针过严。
- 内存 OOM。
- 依赖服务不可用。

**readiness 和 liveness 区别？**

- readiness：是否准备好接收流量。
- liveness：是否存活，不存活则重启。

**滚动发布如何回滚？**

```bash
kubectl rollout status deployment/app
kubectl rollout undo deployment/app
```

**如何做灰度？**

- 按 Deployment 版本拆分流量。
- Ingress 或网关按 header/user/比例路由。
- 先小流量验证，再扩大比例。
- 指标异常立即回滚。

## 三、Nginx

### 1. 核心能力

- 反向代理。
- 负载均衡。
- 静态资源。
- HTTPS 终止。
- 限流。
- 缓存。
- 访问日志。

### 2. PHP 转发

Nginx 不执行 PHP，而是通过 FastCGI 把请求转发给 PHP-FPM。

```nginx
location ~ \.php$ {
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 3. 高频题

- **502 是什么？** 上游服务不可用、连接失败、协议错误或 PHP-FPM 异常。
- **504 是什么？** 上游响应超时。
- **如何限流？** `limit_req_zone` 和 `limit_req`。
- **如何隐藏真实 IP 后获取客户端 IP？** 正确配置 `X-Forwarded-For` 和可信代理。
- **如何排查 Nginx 性能？** 看 access log、error log、upstream 响应时间、连接状态。

## 四、Linux 线上排障

### 1. CPU 高

```bash
top
ps -eo pid,ppid,cmd,%cpu,%mem --sort=-%cpu | head
```

排查方向：

- 慢 SQL 导致 PHP worker 忙。
- 死循环。
- JSON 编解码、加密、压缩。
- 日志量异常。
- 大量重试。

### 2. 内存高

```bash
free -m
ps -eo pid,cmd,%mem --sort=-%mem | head
```

排查方向：

- 应用内存泄漏。
- 常驻进程数组增长。
- 大查询一次性加载。
- 容器 limit 过小。

### 3. 磁盘满

```bash
df -h
du -sh * | sort -h
lsof | grep deleted
```

注意：删除正在被进程持有的日志文件，磁盘空间可能不会立即释放，需要重启或让进程重新打开文件。

### 4. 网络连接异常

```bash
ss -antp
lsof -i :80
curl -v http://127.0.0.1
```

关注：

- 端口是否监听。
- 防火墙和安全组。
- DNS 解析。
- TIME_WAIT、CLOSE_WAIT 是否异常。
- 上游连接超时。

## 五、部署稳定性

上线检查：

- 配置是否按环境区分。
- 数据库变更是否兼容。
- 是否可回滚。
- 是否有健康检查。
- 是否有监控和告警。
- 是否做灰度。
- 是否准备数据补偿脚本。


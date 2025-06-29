#### 安装
dist/rhist 文件拷贝到主机的/usr/local/bin/目录下

~/.bashrc 追加以下配置
```bash
rhist() {
    local result=$(command rhist "$@")
    [ -n "$result" ] && echo "$result" && echo "$result" >> ~/.bash_history && history -r
}
```
source ~/.bashrc 使配置生效

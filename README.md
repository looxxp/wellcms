# WellCMS 2.0

#### 介绍
WellCMS 是一款开源、倾向移动端的轻量级、高负载的CMS，是大数据量、高并发访问网站最佳选择的轻CMS。WellCMS前后台均可在移动端操作，具有安全、高效、稳定、速度快、负载超强的特点。自适应手机、平板、PC，也可以设置各端加载单独模板，并且URL保持不变，有着非常方便的插件机制。前台部分页面配备API，可通过JSON返回AJAX请求的数据，方便 APP 开发。

采用静态语言编程风格，充分发挥 PHP7 OPCache 的威力。前端基于 BootStrap 4.4、JQuery 3.4.1作为前端类库，对第三方类库依赖少。后端基于 PHP/7.* 数据库MySQL或MariaDB，缓存支持 XCache/Yac/Redis/Memcached...

WellCMS 基于XiunoPHP开发，只有22张表，运行速度非常快，处理单次请求在 0.01 秒级别，开启缓存可达到 0.003 秒级别。支持SSL，支持CDN，支持各种NoSQL操作，支持附件分离，支持多台DB主从读写分离，支持伪静态及各种路径切换。分布式服务器设计，每张表都可创建单独的DB服务器群和CACHE服务器(群)，单表可承载高达亿级以上的数据，方便部署和维护，是一个二次开发非常好的基石。

WellCMS不在mysql做任何运算，只把mysql当作储存库使用，并且将大量的运算放到了客户端，并发问题尽量由客户端控制。所以即使亿级以上数据，依然飞快顺滑的打开每个页面。作者灌水10亿数据压测，打开速度依旧如初。

运行环境要求CentOS 6或CentOS 7（CentOS 8要求最低2G内存，不推荐，可根据喜好安装任意linux系统，windows服务器未测试）、1核1G内存、Nginx或Apache、php7（最低支持php5.2）、mysql5.5.6（无需高版本，对于wellcms真心没必要，mysql5.6（包含5.6）以上要求最低1G内存）、OPcache、Yac。这样的硬件环境，已经可以承载亿级数据，并且运行依然飞快。

WellCMS优势之一便是，可在最低配置的环境下，高效运行。如遇高访问量的情况下，只需加带宽即可。

WellCMS 采用 MIT 协议发布，您可以自由修改、派生版本、商用而不用担心任何法律风险（但修改后应保留原来文件的版权信息）。

代码中预留了大量钩子和注释，AOP插件机制，采用 hook 插入，overwrite 方式覆盖，性能方面零损耗，不影响编译，强大而又简单。使用者可自由扩展程序的功能，开发者可尽情二次开发。

#### 安装教程

1. 确认您的主机支持 PHP，并且已经开通并且配置好了 MySQL。
3. 设置如下目录和文件为可写(Linux: 目录权限为 0777，Windows 设置用户 everyone 可读写）
    ./upload
    ./plugin
    ./tmp
    ./log
    ./conf
    ./view/template
4. 上传所有文件到你的网站根目录
5. 访问 http://www.domain.com/install/, 根据提示安装。
6. 删除 install 和 tool 目录

#### 使用说明

http://www.wellcms.cn/list-2.html

#### 参与贡献

1.  Fork 本仓库
2.  新建 Feat_xxx 分支
3.  提交代码
4.  新建 Pull Request

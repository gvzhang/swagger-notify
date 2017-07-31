# Swagger-Notify
基于Swagger API文档的GIT提交通知工具

## 通知流程
![notify flow](https://static.zgjian.cc/markdown/SwaggerNotifyFlow.jpg)

## 项目描述
#### 接口设计
使用了SOA的设计架构，所以接口是按照业务分模块编写的。目前通知模块中，**关联粒度只能到模块**。

具体的详细修改内容，可在description属性中进行说明。

#### Swagger接口文档规范
swagger的json数据文件需要按照module.json的分层来命名，中间使用"_"下划线间隔。

例如：module.json文件
```json
{
  "heygearsbusiness": [
    "client"
  ]
}
```
swagger的json数据文件命名则为 **heygearsbusiness_client.json**

##### 接口编写
每一个接口请确保有**description**属性，便于添加变更说明。
```json
"/token/get": {
  "get": {
    "tags": [
      "token"
    ],
    "summary": "获取TOKEN",
    "description": "",
    "parameters": [
```

#### 对比结果
只对swagger的json数据文件进行差异对比，获取到对比结果后进行通知。

新增文件、删除文件、重命名文件不做比较，即不会有通知。

**目前对于大量修改接口文档的情况，对比后的结果是不准确的。**

#### GIT提交
每次commit之后必须push代码到服务端才能触发最新的对比差异。如多个commit一同push则只对比最新的差异。

## 目录结构
- database：存储通知配置
  - manager.json：记录管理员信息
  - module.json：记录模块信息，第一级为项目名，第二级为项目模块
  - manager_module.json：记录管理员以及模块的关系
- scripts：部署的执行脚本
- src：项目主要代码
- template：邮件模板以及生成swagger文件的模板
- utils：工具库

## 部署说明
#### 配置项
你可以通过env.yaml来对项目进行基本的配置。

| Name          | Description   |
| -------------: |:-------------:|
| target      | 展示比较内容的项目路径 |
| email.host | 邮箱HOST      |
| email.port | 邮箱PORT      |
| email.username | 邮箱用户名      |
| email.password | 邮箱密码      |

#### 接口文档
部署的服务端需要有接口文档仓库，以便获取对比数据。

#### Swagger UI
部署的服务端需要有Swagger UI项目，用于展示对比结果；即为配置项中的target。

#### 配置文件重命名
- env.yaml.example重命名为env.yaml
- manager.json.example重命名为manager.json
- manager_module.json.example重命名为manager_module.json
- module.json.example重命名为module.json

#### Git Hooks
- 在每次git push之后就会进行通知，邮件通知是通过git hooks的post-receive来触发的
- 请在你的API文档Git项目的服务端中加上post-receive脚本
  - 脚本样例在scripts文件夹中
  - 代码中的unset GIT_DIR是关键，取消GIT_DIR这个变量，否则cd的路径将不能按预期工作
  - **为了解决中文路径问题，会将GIT的core.quotepath配置改成false**

#### 对比文件清理
scripts文件夹里面的clean.sh脚本是用于定期清理对比文件，自行加入定时任务中

#### 讨论
- 通知配置管理模块
- 发生异常重新通知机制
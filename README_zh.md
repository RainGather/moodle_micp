# MICP for Moodle（`mod_micp`）

[**English**](./README.md)

> **传统方式：** 教师花很多时间把互动活动拼在 Moodle 外面，评分和记录再手工补回系统。
> **MICP 方式：** 描述一次活动，上传一个课件包，让 Moodle 直接承载、记录、评分和报表。

`MICP for Moodle` 是一个面向教师的 Moodle 活动模块，适合那些希望把更丰富的互动学习活动放进 Moodle，但又不想为每节课单独开发插件、评分流程和报表流程的人。

它用于在 Moodle 中承载上传的 HTML 互动课件、记录学习者交互事件、按服务端规则评分，并将结果写入成绩册。

仓库根目录就是插件根目录。发布包解压后应直接落在 Moodle 的 `mod/micp` 目录下。

## 为什么教师会在意

很多教师其实能设计出很好的学习任务，但现实里真正卡住的是落地成本：

- 活动需要真正的界面，不是普通测验题型就能表达
- 活动结果还要回到 Moodle 成绩册
- 主观题和客观题混合时，教师复核流程很容易断掉
- 做出来的内容往往只是一次性网页演示，下一学期很难复用

MICP 改变的是这个成本结构：

- 互动课件可以作为正式 Moodle 活动发布，而不是课外散落网页
- 能自动判分的证据直接回到成绩册
- 需要教师判断的内容进入待复核队列，而不是脱离系统
- 课程团队可以持续复用和迭代一类互动活动，而不必每次重搭底层

对教学来说，真正的收益不是“页面更好看”，而是更丰富的学习任务终于能在 Moodle 里真正运行起来。

## 核心工作流

```text
教师的活动想法 -> AI 或作者生成课件包 -> 上传到 MICP
                                        -> 学生互动
                                        -> 服务端评分
                                        -> Moodle 成绩册与报告
```

关键不只是页面能互动，而是这个活动终于能在 Moodle 里成为一个正式、可运营的教学活动。

## 主要能力

- 以 ZIP 包或单个 HTML 文件形式上传课件
- 在活动页中嵌入并启动上传内容
- 通过 Moodle AJAX 服务记录学习者事件
- 使用 `micp-scoring.json` 在服务端评分
- 支持自动评分与教师人工复核混合流程
- 通过 Moodle gradebook API 发布成绩
- 通过隐私 API 导出、删除和枚举个人数据
- 备份与恢复活动配置、上传课件和学习记录

## 教学价值

MICP 适合这些传统测验页不太擅长的场景：

- 需要学生探索、比较、操作、观察的互动任务
- 需要图形化、可视化或半模拟界面的学习活动
- 同时包含自动评分证据和教师复核证据的混合评价
- 希望长期复用、持续改进的课件型活动包

它的核心不是把 HTML 塞进 Moodle，而是把原本难以管理的互动学习活动，变成 Moodle 里可启动、可记录、可评分、可复核、可报表化的正式活动。

## 运行要求

- Moodle 5.0
- PHP 8.1 及以上
- 运行时不需要 Composer 或 npm
- 学生使用时不需要外部 API Key

## 安装

将仓库直接克隆到 Moodle 的 `mod` 目录：

```bash
git clone git@github.com:YOUR_USERNAME/moodle-mod_micp.git /path/to/your/moodle/mod/micp
```

或者安装发布包，确保 Moodle 能看到：

```text
/path/to/your/moodle/mod/micp/version.php
```

然后访问 `站点管理 -> 通知`。

详细安装说明见 [INSTALL.md](./INSTALL.md)。

## 课件包格式

上传的课件包应包含：

- `index.html`
- `micp-scoring.json`
- 相关 `assets/`

`window.MICP` 是客户端运行时桥接层，负责上报事件和提交结果，但成绩始终由服务端计算。

如果没有 `micp-scoring.json`，插件会使用最小默认规则：只要记录到任意交互就给满分，没有交互则为零分。

## 仓库结构

仓库将 Moodle 插件代码放在根目录，并把非运行时示例资源单独归档。

```text
.
├── amd/
├── backup/
├── classes/
├── db/
├── examples/
├── lang/
├── pix/
├── templates/
├── tests/
├── version.php
├── lib.php
├── mod_form.php
└── view.php
```

- `examples/` 存放仓库附带的示例课件与打包样例
- `.gitattributes` 将仓库专用内容从发布归档中排除
- `tests/` 存放评分和提交流程相关的 PHPUnit 测试

## 教师使用流程

1. 在 Moodle 中创建一个 `mod_micp` 活动。
2. 上传 ZIP 课件包或单个 HTML 文件。
3. 学生打开活动并与嵌入式课件交互。
4. 课件运行时将事件和提交发送给 Moodle。
5. 客观题立即评分，需要人工处理的项目进入待审核状态。
6. 插件保存结果并更新成绩册。

## 隐私

插件只保存交付和评分所需的数据：

- 学习者交互事件
- 每位学习者最新一次提交快照
- 教师完成复核后留下的审核元数据

运行上传课件时不需要将学习者数据发送到外部服务。

## 开发资料

- [CONTRIBUTING.md](./CONTRIBUTING.md)
- [INSTALL.md](./INSTALL.md)
- [CHANGES.md](./CHANGES.md)
- [SECURITY.md](./SECURITY.md)

## 许可

GPL v3 或更高版本

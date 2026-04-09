# MICP for Moodle（`mod_micp`）

[**English**](./README.md)

MICP 是一个 Moodle 互动活动插件。

用最直接的话说：它可以把一个互动式 HTML 课件放进 Moodle，作为普通课程活动给学生使用，并把结果回写到 Moodle 成绩册。

它适合这些普通页面或普通测验不太好实现的教学活动：

- 分步点击练习
- 拖拽或过程型任务
- 可视化探索或小型模拟
- 客观题和简短主观题混合的活动

## 教师能得到什么

用了 MICP 以后，教师可以：

- 把一个互动课件包直接上传到 Moodle
- 让学生像打开普通活动一样打开它
- 自动判分客观部分
- 把主观部分留给教师复核
- 在 Moodle 里直接看成绩和结果，而不是把活动放在外部网站

最核心的价值很简单：原本很难放进 Moodle 的互动学习活动，现在可以作为正式教学活动运行起来。

## 它怎么用

1. 在 Moodle 里安装 MICP 插件。
2. 准备好一个课件包。
3. 在课程里创建一个 MICP 活动。
4. 上传这个课件包。
5. 学生打开活动并完成任务。
6. Moodle 保存结果并更新成绩册。

## 什么是课件包

课件包通常是一个 ZIP 文件，里面至少有：

- `index.html`
- `micp-scoring.json`
- 可选的 `assets/`

这个课件包可以有几种来源：

- 手工编写
- 让开发者制作
- 用 AI 辅助生成

MICP 本身并不依赖 AI 才能运行。AI 只是“如何制作课件包”的一种可选方式。插件本身负责的是把活动放进 Moodle、记录、评分和报表。

## 它对教学真正改变了什么

很多教师以前会遇到一个两难：

- 留在 Moodle 里，就只能用比较固定的活动形式
- 想做更丰富的互动，就容易跑到 Moodle 外面，最后不好管理、不好评分、也不好复用

MICP 的意义就是尽量去掉这个两难。

它特别适合这些教学任务：

- 让学生探索一个图、流程或现象
- 做一个分步骤推进的互动活动
- 在可视化讲解中插入过程检查
- 在同一个活动里既收集客观答案，也收集开放回答

## 快速开始

### 1. 安装插件

把这个仓库放到 Moodle 的这个位置：

```text
/path/to/your/moodle/mod/micp
```

然后访问：

```text
站点管理 -> 通知
```

详细安装说明见 [INSTALL.md](./INSTALL.md)。

### 2. 创建 MICP 活动

在 Moodle 课程里：

1. 打开编辑。
2. 添加活动。
3. 选择 `MICP`。
4. 上传一个课件 ZIP 或单个 HTML 文件。
5. 保存活动。

### 3. 给学生使用

学生在 Moodle 中打开活动、完成互动并提交。

MICP 可以：

- 记录互动过程
- 在服务端评分
- 把成绩写回 Moodle 成绩册
- 把需要人工判断的内容留给教师后续处理

## 主要能力

- 以 ZIP 包或单个 HTML 文件形式上传课件
- 在活动页中嵌入并启动上传内容
- 通过 Moodle AJAX 服务记录学习者事件
- 使用 `micp-scoring.json` 在服务端评分
- 支持自动评分与教师人工复核混合流程
- 通过 Moodle gradebook API 发布成绩
- 通过隐私 API 导出、删除和枚举个人数据
- 备份与恢复活动配置、上传课件和学习记录

## 运行要求

- Moodle 5.0
- PHP 8.1 及以上
- 运行时不需要 Composer 或 npm
- 学生使用时不需要外部 API Key

## 技术说明

仓库根目录就是插件根目录。发布包解压后应直接落在 Moodle 的 `mod/micp` 目录下。

上传的课件包应包含：

- `index.html`
- `micp-scoring.json`
- 相关 `assets/`

`window.MICP` 是客户端运行时桥接层，负责上报事件和提交结果，但成绩始终由服务端计算。

如果没有 `micp-scoring.json`，插件会使用最小默认规则：只要记录到任意交互就给满分，没有交互则为零分。

## 仓库结构

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

## 开发资料

- [CONTRIBUTING.md](./CONTRIBUTING.md)
- [INSTALL.md](./INSTALL.md)
- [CHANGES.md](./CHANGES.md)
- [SECURITY.md](./SECURITY.md)

## 许可

GPL v3 或更高版本

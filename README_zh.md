# mod_micp

`mod_micp` 是一个 Moodle 活动模块，用于在 Moodle 中承载上传的 HTML 互动课件、记录学习者交互事件、按服务端规则评分，并将结果写入成绩册。

仓库根目录就是插件根目录。发布包解压后应直接落在 Moodle 的 `mod/micp` 目录下。

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

# mod_micp — Moodle 互动内容协议

**mod_micp** 是一个 Moodle 活动模块，用于承载 AI 自动生成的互动 HTML 课件，并将学生成绩自动写入 Moodle 成绩册。

---

## 功能概述

1. 教师创建 **mod_micp** 活动，上传 ZIP 包（或选择内置示例）
2. 学生打开活动，在内嵌的 HTML 页面中进行互动
3. 每次互动通过 `MICP.sendEvent()` 上报到服务器
4. 学生点击"完成"时，`MICP.submit()` 触发服务端评分
5. 最终成绩通过 Moodle 官方 `grade_update()` API 写入成绩册

这是一个**通用互动框架**，不绑定任何特定题型，任何基于 HTML 的互动内容都可以接入。

---

## 环境要求

- **Moodle 5.x**（已在 Moodle 5.0.dev 下验证）
- PHP 8.1+

---

## 快速开始

### 1. 安装插件

将 `mod/micp/` 目录复制到 Moodle 的 `mod/` 目录下：

```bash
cp -r mod/micp /path/to/your/moodle/mod/
```

然后访问 **网站管理 → 通知**，触发数据库安装。

### 2. 创建活动

1. 在课程页面开启编辑模式
2. 点击 **添加活动** → 选择 **MICP**（互动内容协议）
3. 输入名称，（可选）上传 ZIP 包

### 3. 制作自己的互动课件

MICP 课件包是一个 ZIP 文件，内容结构如下：

```
my-lesson.zip
├── index.html          # 主入口 — 互动内容页面
├── micp-scoring.json   # 评分规则（可选；不提供时默认有互动就给 100 分）
└── assets/             # 图片、音频、字体等资源
```

**`micp-scoring.json`** 示例：

```json
{
  "rules": [
    {
      "id": "step1",
      "label": "阅读导言",
      "type": "interaction",
      "check": { "event": "interaction", "data.step": 1 },
      "weight": 1.0
    },
    {
      "id": "step2",
      "label": "完成练习",
      "type": "interaction",
      "check": { "event": "interaction", "data.action": "exercise_done" },
      "weight": 1.0
    }
  ],
  "scoring": {
    "strategy": "all_or_nothing",
    "passing_score": 50
  }
}
```

未提供 `micp-scoring.json` 时，服务器端规则为：**有任意一条互动记录得 100 分，否则得 0 分**。

---

## 前端 SDK

每个课件页面内均可使用全局对象 `window.MICP`：

```javascript
// 初始化（页面加载时自动调用）
MICP.init();

// 发送互动事件
MICP.sendEvent('interaction', { step: 1, action: 'click' });

// 提交课件 — 触发服务端评分 + 成绩册写入
MICP.submit({ raw: { actions: [...] } });

// 获取当前用户和活动上下文
const ctx = MICP.getContext();
// ctx.cmid, ctx.userid, ctx.courseid, ctx.sesskey
```

所有请求均自动携带 Moodle `sesskey`。

---

## 架构说明

```
mod/micp/
├── lib.php              # 核心：评分引擎、成绩册封装、文件解析
├── view.php             # 活动页面（iframe 容器）
├── file.php             # 插件文件访问（ZIP 包内文件服务）
├── report.php           # 参与者成绩报告
├── mod_form.php         # 活动设置表单
├── micp.js              # 客户端 SDK
├── db/
│   ├── install.xml      # 数据库表结构（micp_events, micp_submissions）
│   ├── services.php     # Moodle AJAX 服务
│   └── access.php       # 权限定义
├── classes/local/
│   └── scoring_service.php  # 服务端评分逻辑
├── sample_content/      # 内置示例课件
└── lang/en/micp.php     # 语言字符串
```

### 评分流程

```
学生互动
  → MICP.sendEvent() → AJAX → 写入 {micp_events}

学生点击"完成"
  → MICP.submit() → AJAX
  → scoring_service::evaluate() 读取 micp-scoring.json 规则
  → grade_update() 写入成绩册
  → 返回 { score, rawgrade, details }
```

---

## 内置示例课件

`generated/` 目录下包含三个可直接上传使用的课件包：

| 课件包 | 说明 |
|---|---|
| `audio-digitization-micp/` | 英语版 — 声音数字化基础，共 33 个互动节点 |
| `audio-digitization-micp-zh/` | 中文版 — 同上内容，中文语言 |
| `photosynthesis-micp/` | 英语版 — 光合作用，渐进式披露设计 |

ZIP 文件已预构建，可直接上传：
- `generated/audio-digitization-micp.zip`
- `generated/audio-digitization-micp-zh.zip`
- `generated/photosynthesis-micp.zip`

---

## 安全模型

- 所有写入操作均需 `require_login()` + `require_sesskey()`
- `userid` 始终从服务器会话读取，绝不信任客户端提交
- `score` 由服务端计算，客户端无法伪造成绩
- 重复提交覆盖旧成绩（幂等操作）

---

## 权限定义

| 权限 | 默认角色 | 说明 |
|---|---|---|
| `mod/micp:addinstance` | 教师 | 创建/编辑 MICP 活动 |
| `mod/micp:view` | 学生 | 查看活动并进行互动 |
| `mod/micp:submit` | 学生 | 提交课件并获取成绩 |
| `mod/micp:viewreports` | 教师 | 查看参与者成绩报告 |

---

## AI 辅助课件制作（本仓库配套 Skills）

本仓库附带了用于制作互动课件的 AI Agent Skill，可以配合 [OpenCode](https://opencode.dev/) 或兼容 Agent 使用。

> 💡 **重要**：这些 Skill 需要 AI Agent 环境（如 OpenCode）才能运行，**不是**可直接在 Moodle 中安装的插件。

### 内置 Skills

| Skill | 文件 | 用途 |
|---|---|---|
| **micp-html-authoring** | `.skills/micp-html-authoring/SKILL.md` | 为 mod_micp 生成完整互动 HTML 课件包（index.html + micp-scoring.json） |
| **ai-dynamic-course** | `.skills/ai-dynamic-course/SKILL.md` | 生成"AI 动态课程"整合包（mod_micp + Cloze 测验 + H5P 可选） |
| **moodle-cloze-question** | `.skills/moodle-cloze-question.md` | 生成 Moodle Cloze（完形填空）题型 |
| **moodle-h5p** | `.skills/moodle-h5p.md` | 创建 H5P 互动练习内容 |

### 使用方法（OpenCode Agent）

当你安装了 OpenCode 后，这些 Skill 会自动被加载。直接告诉 Agent 你的需求即可：

```
# 创建一个关于"光合作用"的 MICP 互动课件
"帮我制作一个关于光合作用的 MICP 互动课件"

# 生成一个 Cloze 测验题
"生成一个关于细胞分裂的 Cloze 题目"

# 制作一个 H5P 互动练习
"制作一个拖拽配对题用于练习元素周期表"

# 一站式生成完整动态课程
"生成一个关于'波的叠加原理'的 AI 动态课程"
```

Agent 会根据 Skill 指引自动调用对应的工具和工作流程，生成可直接上传到 mod_micp 的 ZIP 包。

### 制作 MICP 课件的标准流程

如果你想自己制作一个 MICP 课件，核心只有三步：

**① 编写 `index.html`**

```html
<!-- 初始化 SDK -->
<script src="assets/micp.js"></script>

<!-- 互动元素 -->
<button onclick="window.MICP.sendEvent('interaction', {
  interactionid: 'q1_choice',
  response: 'a',
  outcome: 'selected',
  sequence: 1
})">选择 A</button>

<!-- 提交按钮 -->
<button id="submit-attempt" onclick="window.MICP.submit({ raw: { actions: actions } })">
  完成
</button>
```

**② 配置 `micp-scoring.json`**

```json
{
  "rules": [
    {
      "id": "q1_choice",
      "label": "第一题",
      "check": { "event": "interaction", "scoring": { "correct": "a" } }
    }
  ],
  "scoring": { "strategy": "all_or_nothing" }
}
```

**③ 打包上传**

```
my-lesson.zip
├── index.html
├── micp-scoring.json
└── assets/
    └── micp.js      ← 必须，可从 .skills/micp-html-authoring/references/assets/ 获取
```

详细规范见 `.skills/micp-html-authoring/SKILL.md`。

---

## 扩展性

评分引擎采用可插拔设计，替换 `classes/local/scoring_service.php` 中的 `$this->evaluator` 即可切换评分策略：

- 内置：`AllOrNothingEvaluator`（全有或全无）、`ProportionalEvaluator`（按比例）
- 未来可扩展：AI 评分器、Python 脚本评分器

---

## 开源许可

GPLv3 — 与 Moodle 本身采用相同许可。

---

## 更新日志

详见 [CHANGELOG.md](./CHANGELOG.md)。

# mod_micp — AI 驱动的 Moodle 互动课件

[**English**](./README.md)

> **传统方式：** 教师花几小时手动制作互动练习。
> **mod_micp 方式：** 告诉 AI 你要教什么主题，马上拿到一套完整的互动课件。

mod_micp 只做一件事：用 AI 生成可自动评分的互动 HTML 课件，在 Moodle 里直接打分。

---

## 核心工作流

```
你 → "制作一个关于[主题]的课件" → AI 生成 ZIP 包
                                               ↓
                                    上传到 mod_micp
                                               ↓
                    学生互动 → 服务器评分 → 成绩写入 gradebook
```

**AI 负责创作。插件负责推送和评分。**

---

## 现在就开始

### 第一步：安装插件

```bash
cp -r mod/micp /path/to/your/moodle/mod/
```
访问 **网站管理 → 通知** 完成安装。依赖 **Moodle 5.x**，PHP 8.1+。

### 第二步：用 AI Skill 生成你自己的课件

本仓库附带了 **`micp-html-authoring`** — 一个 AI Agent Skill，知道如何构建 mod_micp 课件包。
当前内置 Skill 位于仓库根目录的 [`skill/SKILL.md`](skill/SKILL.md)。

**OpenCode Agent 触发词：**
```
"Create a MICP lesson about [你教的主题]"
```

AI 会自动生成：
- `index.html` — 互动课件页面
- `micp-scoring.json` — 服务器评分规则
- `assets/` — 打包好的 JS 运行时

ZIP 打包 → 上传到 mod_micp → 学生获得一套自动评分的互动课件。

当前附带的 Skill 还支持：
- **默认中性反馈** —— 除非明确要求，否则不会立刻告诉学生对错
- **主观题人工批改** —— 通过 `gradingmode: "manual"` 标记需要教师复核的题目

详细文档：[`skill/SKILL.md`](skill/SKILL.md)

---

## 你得到什么

| | 传统方式 | mod_micp + AI Skill |
|---|---|---|
| 制作一个课件 | 数小时 | ~30 秒（AI 生成） |
| 评分 | 手动批改 | 自动 → gradebook |
| 课件类型 | 固定模板 | 任意 HTML 互动设计 |
| 维护 | 逐个学生反馈 | 服务端评分 |

对于“客观题 + 主观题”的混合课件，mod_micp 还支持 **混合评分**：
- 客观题立即自动评分
- 主观题进入 **待教师批改**
- 教师在活动报告中批改并发布最终成绩

---

## 工作方式

```
AI 生成课件包             插件承载与评分
      ↓                        ↓
[index.html] + [micp-scoring.json] → 学生打开活动
                                     ↓
                                学生互动
                                MICP.sendEvent() → 服务器
                                     ↓
                                学生点击“提交”
                                MICP.submit() → 评分
                                     ↓
                                grade_update() → Moodle 成绩册
```

- **客户端 SDK**（`window.MICP`）：负责发送事件和提交，不在前端算分
- **服务端评分**：读取 `micp-scoring.json`，返回成绩
- **成绩册写入**：通过 Moodle 官方 `grade_update()` API

教师端报告支持：
- 每个 interaction / 成绩点独立成列
- 按小组筛选结果
- 对主观题进行人工批改

---

## 为什么做这个

教师不应该是模板工人，而应该是课程设计师。

mod_micp 分离了关注点：
- **你**决定学生应该经历什么、学到什么
- **AI**构建互动页面
- **插件**可靠地、大规模地推送和评分

---

## 开源许可

GPLv3

---

## 技术文档

<details>
<summary>点击展开 — 完整技术细节</summary>

### 课件包结构

```
my-lesson.zip
├── index.html          # 互动 HTML（AI 生成）
├── micp-scoring.json   # 评分规则
└── assets/
    └── micp.js         # 必需的运行时
```

### `micp-scoring.json`（由 AI 生成）

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

不提供此文件时：**有任意一条互动记录得 100 分，否则 0 分**。

### 客户端 SDK

```javascript
MICP.init();                                           // 页面加载时自动调用
MICP.sendEvent('interaction', { interactionid: '...', response: '...', outcome: '...' });
MICP.submit({ raw: { actions: actions } });            // 点击提交按钮时
const ctx = MICP.getContext();                         // { cmid, userid, sesskey, ... }
```

### 评分策略

- `all_or_nothing` — 全部规则满足 = 100 分，否则 0 分
- `proportional` — 按规则权重给部分分

### 混合人工批改

`micp-scoring.json` 可以把某个 interaction 标记成教师复核：

```json
{
  "id": "reflection_text",
  "label": "简短反思",
  "type": "text",
  "weight": 20,
  "gradingmode": "manual",
  "scoring": {
    "requireNonEmpty": true
  }
}
```

行为如下：
- 客观题提交后立即自动评分
- 主观题会让提交状态变成 **待批改**
- 教师进入 report 后可以逐项打分并发布最终成绩

### 成绩报告

活动 report 现在支持：
- 每个 interaction 单独成列，不再挤在一个 breakdown 里
- 显式的小组下拉筛选
- 对需要人工批改的提交显示 Review 入口

### 权限定义

| 权限 | 默认角色 | 说明 |
|---|---|---|
| `mod/micp:addinstance` | 教师 | 创建/编辑活动 |
| `mod/micp:view` | 学生 | 查看和互动 |
| `mod/micp:submit` | 学生 | 提交并获得成绩 |
| `mod/micp:viewreports` | 教师 | 查看成绩报告 |

### 仓库结构

```
.
├── mod/micp/              # Moodle 活动插件本体
├── skill/                 # 内置的 micp-html-authoring Skill
│   ├── SKILL.md
│   └── references/        # 模板、交互模式与运行时资源
├── sample/                # 示例/生成中的课件工作目录
├── README.md
└── README_zh.md
```

### 插件架构

```
mod/micp/
├── lib.php                 # 评分引擎、gradebook 封装
├── view.php                # 活动页面（iframe 容器）
├── styles.css              # 活动承载页样式
├── templates/activity.mustache  # 活动承载页模板
├── file.php                # 插件文件访问（课件资源服务）
├── report.php              # 参与者成绩报告
├── review.php              # 人工批改流程
├── micp.js                 # 客户端 SDK
├── db/install.xml          # 数据表：micp_events, micp_submissions
├── db/services.php         # Moodle AJAX 接口
├── classes/external/       # AJAX 入口
├── classes/local/
│   └── ...                 # 评分、仓储与提交服务
└── lang/en/micp.php
```

</details>

## 更新日志

详见 [CHANGELOG.md](./CHANGELOG.md)。

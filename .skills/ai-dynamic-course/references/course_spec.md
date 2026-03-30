# AI 动态课程（AI Dynamic Course）规范

本规范用于生成“AI 动态课程”：Moodle 作为课程入口与测评闭环，MagicSchool 作为主教学对话引擎，H5P 仅用于关键点互动穿插，课后测验使用 Moodle Cloze。

## 核心原则

1) **证明码只在 MagicSchool 对话最后发放**
- 证明码不得出现在学生可见的 Moodle 页面、H5P 内容、课堂讲义中。
- 证明码必须在 MagicSchool 后台课程内容里可见（给教师配置用），并由 Agent 在对话结束时发给学生。

2) **H5P 只做关键互动，不做课后测验**
- H5P 内容用于“讲解中的关键点互动/展示”，由 Agent 给链接。
- 课后测验统一使用 Moodle 的 Cloze 题目（单选/多选/数值优先）。

3) **Moodle 负责课后测验与记录成绩**
- Moodle 只负责课后测验（Cloze）并自动批改记录成绩。
- 不在 Moodle 内提交/验证完成证明码。

4) **一课一包（目录规范）**
- 每节课一个目录，目录内包含所有可复制粘贴到 Moodle/MagicSchool 的文本文件，以及可选 H5P 包。

---

## 命名约束（目录与文件名必须一致）

### 课目录命名

每节课一个目录，命名必须使用下面模式（与现有教材一致）：

- 目录名：`{NN} 第{N}课 {课名}`
  - `NN`：两位序号（01–30）
  - `N`：中文“第N课”的 N（不补零）
  - `{课名}`：课程标题（建议不含特殊字符）

示例：
- `15 第15课 调试与断言`

### 课内文件命名

- 课堂讲义/备课稿（不含证明码）：`第{N}课 {课名}.md`
- Moodle 学生入口页（HTML 但保存为 txt）：`Moodle_学生入口页.txt`（学生可见，必须极简：只写“做什么/点哪里/做完做什么”，不要解释证明码、不要讲代码细节）
- README（合并：资源索引 + 教师搭建说明，所有指导性文字都放这里）：`README.md`
- MagicSchool 后台课程内容（含证明码）：`MagicSchool_课程内容_L{NN}.md`
- 课后测验题干（Cloze 多题）：`Moodle_Cloze_课后测验_L{NN}.txt`
- 代码资料：`src/`（可选）
- H5P 课件（可选，且只允许 1 个课件，不允许小测）：`L{NN}_{课名}_课件.h5p`

文件名规范化规则（避免 Windows/麒麟/Moodle 上传问题）：
- `{课名}` 中的空格建议去掉或改为下划线 `_`
- 避免字符：`/\\:*?"<>|`

---

## 目录结构（每课）

建议每课目录如下（文件名固定，便于批量生产和检索）：

- `README.md`（教师搭建说明 + 资源索引）
- `第{N}课 {课名}.md`（课堂讲义/备课稿：不含证明码）
- `Moodle_学生入口页.txt`（HTML 内容但以 .txt 保存，方便复制）
- `MagicSchool_课程内容_L{NN}.md`（课程内容级别 Markdown：包含证明码、H5P 链接占位）
- `Moodle_Cloze_课后测验_L{NN}.txt`（多题：课后测验题库题干）
- `src/`（可选：给学生的代码资料包）
- `L{NN}_{课名}_课件.h5p`（可选：仅 1 个 Course Presentation）

---

## Moodle 侧活动清单（每课）

所有教师操作说明与注意事项统一写在课目录的 `README.md` 中（不再单独维护教师说明文件）。

1) Page：学生入口页
- 内容来自：`Moodle_学生入口页.txt`
- 固定版式（推荐，学生入口极简）：

```html
<h2>第{N}课：{课名}</h2>

<p>请先进入 MagicSchool 完成本课学习，然后回到 Moodle 完成课后测验。</p>

<p><a href="https://MAGICSCHOOL_AGENT_LINK_TODO" target="_blank" rel="noopener"><strong>进入第{N}课 MagicSchool</strong></a></p>

<p><strong>学完后要做：</strong>进入“第{N}课-课后测验（Cloze）”。</p>
```

- 禁止：任何关于“证明码”的说明；任何代码细节/技巧（这些都放在 MagicSchool 内部）

2) Quiz：课后测验（Cloze）
- 题干来自：`Moodle_Cloze_课后测验_L{NN}.txt`

4) H5P Activity（可选，仅课件）
- 上传：`L{NN}_{课名}_课件.h5p`
- 复制活动 URL 回填到 MagicSchool 课程内容的 `【H5P_CP_URL_TODO】`

---

## MagicSchool 侧内容规范（每课）

文件：`MagicSchool_课程内容_L{NN}.md`

必须包含：
- 课程目标
- 学习流程（Step A/B/C...）
- 关键知识点
- 学生操作指引（Windows/麒麟）
- H5P 链接占位：`【H5P_CP_URL_TODO】`
- 分层标准（青铜/白银/黄金）
- 三档完成证明码（32 位大写字母+数字）

不得包含：
- 在 Moodle 内提交/验证证明码的任何设计或题干

---

## 证明码策略（A：全班同码）

- 每课生成 3 个码（青铜/白银/黄金）。
- MagicSchool 对话最后发放其中一个。
- Moodle 不验证证明码；证明码只用于 MagicSchool 内部激励与记录。

注意：A 策略天然可分享，更多用于“进度记录”，不是强防作弊。

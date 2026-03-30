# 中文版《声音如何变成数据》课程资源包

## 概述

本资源包是英文原版《From Air Vibrations to Audio Files》的中文翻译版本。

## 课程结构

- **主题**: 声音如何从模拟信号转换为可存储和编辑的数字数据
- **6 个渐进式步骤**:
  1. 模拟声音是连续的 — 揭示连续波形概念
  2. 采样将平滑的波形转换为快照 — 采样密度概念
  3. 量化将每个采样四舍五入到等级 — 比特深度概念
  4. 这些量化等级以二进制形式存储 — 二进制编码
  5. 数字处理操作存储的数字 — 增益与平滑滤波
  6. 综合完整的故事 — 写作综合

## 交互标识符 (interactionid)

所有交互标识符与英文版完全一致，确保评分配置兼容：

| interactionid | 描述 | 类型 | 分值权重 |
|---|---|---|---|
| analog_reveal | 揭示模拟信号概念 | completion | 10 |
| sampling_rate_choice | 采样频率选择 | choice | 15 |
| quantization_bits_choice | 量化比特数选择 | choice | 15 |
| binary_code_choice | 二进制编码选择 | choice | 15 |
| processing_filter_choice | 数字处理滤波选择 | choice | 15 |
| final_synthesis_reflection | 最终综合反思 | text | 30 |

## 翻译说明

- 所有用户可见文本已翻译为中文
- 技术术语保留英文（binary, sampling, quantization 等）
- CSS 样式保持不变
- JavaScript 代码逻辑保持不变
- MICP SDK 调用保持不变

## 文件结构

```
audio-digitization-micp-zh/
├── index.html          # 中文版互动课程
├── micp-scoring.json   # 评分配置（与英文版相同）
├── assets/
│   └── micp.js         # MICP JavaScript SDK
└── authoring-notes.md  # 本文件
```

## 使用方法

1. 将整个文件夹打包为 ZIP 文件
2. 在 Moodle 中上传到 mod_micp 活动
3. 学生完成各检查点互动
4. 教师可在成绩报告中查看每位学生的交互详情

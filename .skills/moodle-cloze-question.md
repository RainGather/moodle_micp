---
name: moodle-cloze
description: "Comprehensive guide for generating, parsing, and validating Moodle Embedded Answers (Cloze) syntax. Covers all sub-question types (Multichoice, Short Answer, Numerical), scoring weights, feedback mechanisms, and special character escaping rules. moodle格式常指代Moodle Cloze (Embedded Answers) Syntax"
license: Creative Commons / GPL (Based on Moodle Documentation)
---

# Moodle Cloze (Embedded Answers) Syntax

## Overview

A user may ask you to generate Moodle question strings, debug existing Cloze code, or parse the structure of an embedded answer. A Cloze question consists of a passage of text with various sub-questions (Multiple Choice, Short Answer, Numerical) embedded directly within it using a specific syntax enclosed in braces `{}`.

## Syntax Component Tree

### Basic Structure
Use **"General Syntax Pattern"** section below.

### Choosing Question Types
- **Text Input (Exact or fuzzy match)**
  Use `SHORTANSWER` variations (`SA`, `SAC`)

- **Numeric Input (Ranges/Tolerances)**
  Use `NUMERICAL` variations (`NM`)

- **Selection (Dropdowns, Radio buttons, Checkboxes)**
  Use `MULTICHOICE` variations (`MC`, `MCV`, `MCH`, `MR`)

### Advanced Features
- **Scoring & Feedback**
  Use **"Scoring and Feedback Syntax"** section

- **Media & Equations**
  Use **"Rich Content & Escaping"** section

## General Syntax Pattern

The structure of each cloze sub-question is identical:

```text
{weight:TYPE:Answer_Options}
```

### Key Components
* **`{ }`**: Start and end of the sub-question (AltGr+7 / AltGr+0).
* **`weight`**: (Optional) A positive integer (e.g., `1`, `2`) defining the grade weight. Defaults to 1 if omitted.
* **`TYPE`**: The question type identifier (case-insensitive).
* **`:`**: Separators between weight, type, and answer options.
* **`Answer_Options`**: The string containing answers, grades, and feedback.

## Question Type Reference

Select the appropriate code based on the desired UI and behavior.

### Short Answer (Text Input)
* `SHORTANSWER` or `SA` or `MW`: Case is unimportant.
* `SHORTANSWER_C` or `SAC` or `MWC`: Case must match exactly.

### Numerical (Number Input)
* `NUMERICAL` or `NM`: Numerical answers, supports tolerances/ranges.

### Multiple Choice (Selection)
* **Dropdown Menu**:
  * `MULTICHOICE` or `MC` (Standard)
  * `MULTICHOICE_S` or `MCS` (Shuffled answers)
* **Vertical Radio Buttons**:
  * `MULTICHOICE_V` or `MCV`
  * `MULTICHOICE_VS` or `MCVS` (Shuffled)
* **Horizontal Radio Buttons**:
  * `MULTICHOICE_H` or `MCH`
  * `MULTICHOICE_HS` or `MCHS` (Shuffled)

### Multiple Response (Checkboxes)
* `MULTIRESPONSE` or `MR`: Vertical checkboxes.
* `MULTIRESPONSE_H` or `MRH`: Horizontal checkboxes.
* Shuffled versions: `MRS`, `MRHS`.

## Scoring and Feedback Syntax

Within the `Answer_Options` section, use the following markers to define logic.

### Answer Definition
* **`~`**: Separator between answer options.
* **`=`**: Marks a correct answer (100% credit).
* **`%n%`**: Allocation of partial points or penalty (e.g., `%50%` for half credit, `%-50%` for negative points).
* **`*`**: Wildcard to catch all other answers (typically used for "wrong answer" feedback in Short Answer questions).

### Feedback
* **`#`**: Marks the beginning of a feedback message.
* **Display**: Appears in a popup/hover after checking the answer.
* **Syntax**: `Answer#Feedback message`.

**Example:**
```text
{1:MULTICHOICE:=Correct Answer#Good job!~Wrong Answer#Try again}
```

## Numerical Question Specifics

Numerical questions require specific syntax to handle floating point numbers and tolerances.

### Interval Syntax
To accept a range of numbers `x ± y`, use the syntax `Answer:Tolerance`.

* **Format**: `CorrectValue:Tolerance`
* **Example**: `23.8:0.1` accepts any value between **23.7** and **23.9**.

### Formatting Rules
* **Scientific Notation**: Supported (e.g., `2.34E+1`).
* **Decimal Separators**: Both `.` and `,` are generally accepted depending on locale, but standard format uses `.`.

**Example:**
```text
{2:NUMERICAL:=23.8:0.1#Correct range~%50%23.8:2#Close but not precise}
```

## Rich Content & Escaping

**CRITICAL**: When embedding special characters or HTML, strict rules apply to prevent breaking the Cloze parser.

### Character Escaping
If the correct answer or feedback text contains any of the following characters, they must be escaped with a backslash `\`:
* `}` (End bracket)
* `#` (Feedback marker)
* `~` (Answer separator)
* `/` (Forward slash)
* `"` (Double quote)

**Note**: The initial `{` should generally **not** be escaped to allow TeX/LaTeX expressions to function.

### HTML and Images
* **Dropdowns (`MC`, `MCS`)**: Cannot contain images or complex HTML (relies on `<option>` tag).
* **Radio/Checkboxes (`MCV`, `MR`, etc.)**: Can contain images (`<img>` tags) and LaTeX equations.
* **Equations**: Use `\( ... \)` for inline math. Do not use `\[ ... \]` inside Cloze syntax.
* **Cleanup**: Ensure no hidden HTML tags (like `<span>`) exist inside the Cloze code itself (e.g., `{:SA:=<span>answer</span>}`).

## Troubleshooting Common Errors

### "All or Nothing" Behavior
To enforce "All or nothing" scoring (prevent partial credit for multiple sub-questions):
1. Use negative points (e.g., `~%-100%*`) to cancel out positive points on wrong attempts.
2. OR: Use the specific "Adaptive mode (all or nothing)" quiz behavior setting in Moodle.

### Line Breaks
**DO NOT** place line breaks inside the `{ ... }` syntax. The entire Cloze code for a sub-question must be on a single line.

### False Positives (Numerical)
Be aware that `1/2` might be interpreted as a date or text in some contexts. Use decimal notation or explicit `NUMERICAL` type to ensure correct parsing.

## Examples

### Complete Cloze Passage
```text
Match the following cities with the correct state:
* San Francisco: {1:MULTICHOICE:=California#OK~Arizona#Wrong}
* Tucson: {1:MULTICHOICE:California#Wrong~%100%Arizona#OK}

The capital of France is {1:SHORTANSWER:%100%Paris#Correct!~%50%Marseille#Partial credit~*#Wrong answer}.

Calculate 20 + 3.8 (error 0.1 allowed): {2:NUMERICAL:=23.8:0.1#Correct!~%50%23.8:2#Close}.
```

### Shuffle + Vertical Layout
```text
Which of these are fruits?
{1:MCVS:=Apple#Correct~Carrot#Vegetable~=Banana#Correct}
```

## Dependencies

No external libraries are required to write the syntax, but the following are standard Moodle plugins mentioned in the docs for *creation* tools:

- **TinyMCE Cloze Editor**: Available for Moodle 4.1+.
- **Excel Cloze Generator**: External tool for generating syntax.

## 注意

输出含有基础题目格式风格的HTML格式。

针对填空题需特别注意，因为很多填空题存在一些同义词、语气助词等的误判，因此除非答案非常非常明确切清晰只有一个词汇，也不会被学生误解填错，此时才选择填空题。否则的话只用单选(包含了判断)、多选或者数字题。

当使用选择题类型时，都用答案随机打散的类型（即MULTICHOICE_S或MCS）

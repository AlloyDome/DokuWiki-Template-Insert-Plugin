# Version History · 版本记录
## 0.4.1 (2023-3-7)
*Mar 7, 2023 · 2023 年 3 月 7 日*

* Fix a bug of section range mapping table exposure in XHTML (e.g. when the wiki text ends with a Listblock)

---

* 修复章节编辑范围对照表可能会被原样输出到 XHTML 的 bug（例如，当 wiki 代码以一个列表（Listblock）结束时会出现这个问题）

## 0.4.0 (2023-3-5)
*Mar 5, 2023 · 2023 年 3 月 5 日*

* Rewrite the parser

---

* 重写解析器

## 0.3.0 (2021-11-30), 0.3.1 (2021-11-30)
*Nov 30, 2021 · 2021 年 11 月 30 日*

* Introduce several parser functions

---

* 新增几个解析器函数

## 0.2.0 (2021-7-6)
*July 6, 2021 · 2021 年 7 月 6 日*

* Introduce text range correction function of section edit button
* Adjust the parsing mechanism of the argument placeholders `{{{...}}}` inside the double curly braces for file inserting syntax `{{...}}` (ie `{{{{{...}}}}}`) 

---

* 加入章节编辑按钮文本范围修正功能
* 调整了参数占位符 `{{{...}}}` 位于插入文件的双花括号 `{{...}}` 内部（即 `{{{{{...}}}}}`）时的识别机制

## 0.1.1 (2021-5-4)
*May 4, 2021 · 2021 年 5 月 4 日*

* Improve the handler of template names

---

* 完善模板名处理机制

## 0.1.0 (2021-4-29)
*Apr 29, 2021 · 2021 年 4 月 29 日*

* Rewrite the code for template syntax

---

* 重写模板语法代码

## 0.0.2 (2021-3-3)
*Mar 3, 2021 · 2021 年 3 月 3 日*

* Backslach escaping problem solved

---

* 解决了反斜杠字符发生转义的问题

## 0.0.1 (2021-1-5)
*Jan 5, 2021 · 2021 年 1 月 5 日*

The initial release including new functions below:
* Supporting arguments with default values
* Recursion check
* Switch statement

---

初始版本，包括以下新功能：
* 支持含默认值的参数
* 递归检查
* Switch 语句
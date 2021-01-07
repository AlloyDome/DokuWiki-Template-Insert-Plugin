*简体中文*

*2020 年 1 月 7 日*

# DokuWiki TemPLaTe Insert 插件语法说明
这篇文档会介绍 DokuWiki TemPLaTe insert 插件（下文简称 “TPLT 插件”）的相关语法。

## 概述
### 简介
TPLT 插件的语法与 MediaWiki 中模板的语法比较相似，这是因为 TPLT 插件是基于 Vitalie Ciubotaru 所制作的 [wst 插件](https://www.dokuwiki.org/plugin:wst)（Wikimedia Style Templates，wst）修改而来，在语法上基本保留了原插件的语法。

### 什么是 “模板”？
“模板” 是一类用于调用的页面，其本质与普通页面无异，用户可以通过调用模板，将其内容插入到其他的页面当中。此外，模板内还可以设置若干参数，用户可以输入参数的值来达到修改模板内部分内容的目的。

请注意，在 DokuWiki 中，“模板” 一词通常是 “主题” 的同义词，但这里的 “模板” 一词没有 “主题” 或 “样式” 的意思。

## 模板的创建与调用
### 存放模板的命名空间
TPLT 插件所调用的模板一般情况下会统一放置在一个命名空间内，默认的命名空间是 “`template`”。

为叙述方便，下文会将 “`template`” 这个命名空间视作存放模板的命名空间。

### 创建模板
创建模板的方法与创建普通页面的方法无异。

例如想创建一个名叫 “`example`” 的模板，只需在 “`template`” 命名空间当中创建一个叫 “`example`” 的页面即可。换句话说，即创建 “`template:example`” 页面。

### 调用模板
调用模板最基本的语法是：`{{tplt>模板名}}`。

例如，“`example`” 模板内有如下内容：
```
Hello world.
```
然后我们在下面一段文字中调用它：
```
The template says: "{{tplt>example}}"
```
输出的结果是
> The template says: "Hello world."

请注意，如果此模板存放于专门存放模板的命名空间中，则模板名中可以不写存放模板的命名空间。
例如，想调用存放在 “`template`” 命名空间当中的 “`example`” 模板时：
* `{{tplt>example}}`——可以正确地调用
* `{{tplt>template:example}}`——不能正确地调用，这会定位到 “`template:template:example`” 页面

## 参数设置
在模板中，可以设置一些参数，以使模板内的内容产生变化。用户在调用模板时，给各参数赋予不同的参数值，模板内相应的参数所在的位置也会被参数值所替代。

### 参数
参数使用三个花括号包围：`{{{参数名}}}`。

参数名建议用字母和数字的组合，例如 “`{{{1}}}`” “`{{{title}}}`” “`{{{argu1}}}`” 等等。

### 含有参数的调用
含有参数调用的语法是：`{{{tplt>模板名|参数名1=参数值1|参数名2=参数值2|...}}}`。

如果某些参数值没有指明参数名，我们暂缺称其为 “无名参数”，就像下面的样子：
```
{{{tplt>example|A|B|C|...}}}
```
那么参数名会被指定为该参数值的次序，也就是给它们 “编号”，相当于：
```
{{{tplt>example|1=A|2=B|3=C|...}}}
```
请注意，这种对于无名参数的编号方法与 MediaWiki 中的不同。

例如，“`example`” 模板内有如下内容：
```
Hello {{{1}}}.
```
然后我们在下面一段文字中调用它：
```
The template says: "{{tplt>example|1=my friend}}"
```
输出的结果是
> The template says: "Hello my friend."

如果此时参数 “`1`” 没有规定参数值，那么会输出警告：
> The template says: "Hello 警告：缺少参数."

### 参数的默认值
含默认值的参数语法为：`{{{参数名=默认值}}}`。
对于含默认值的参数，在调用模板时如果不规定其参数值，那么会输出其默认参数。

例如，“`example`” 模板内有如下内容：
```
Hello {{{1=world}}}.
```
然后我们在下面一段文字中调用它：
```
指定参数值：{{tplt>example|1=my friend}}

不指定参数值：{{tplt>example}}
```
输出的结果是
> 指定参数值：Hello my friend.
>
> 不指定参数值：Hello world.

### 参数值中特殊字符的处理
由于 “`|`” 起到分隔参数的作用，第一个 “`=`” 起到分隔参数名和参数值的作用，因此在参数值中无法直接使用这两个字符。

对于 “`|`”，如确实需要在参数值中使用（比如创建一个表格），请使用 “`{{!}}`” 替代。

对于 “`=`”，如确实需要在没有指明参数名参数值中使用，请在参数值开头加一个 “`=`”。例如想要让 “`{{{tplt>example|1+1=2|...}}}`” 第一个参数的参数值是 “`1+1=2`”，那么可以写成：“`{{{tplt>example|=1+1=2|...}}}`”。

## 开关语句
**请注意，TLPT 插件的开关语句功能目前是试验性功能，请谨慎使用。**

开关语句是一种根据某个参数的参数值来选择输出结果的功能，其语法是：`{{{#switch>参数名=默认输出值|参数值1:输出值1|参数值2:输出值2|...}}}`。

例如，“`example`” 模板内有如下内容：
```
{{{#switch>color=other|1:red|2:yellow|3:green|4:blue}}}
```
然后我们在其他页面调用它：
```
{{tplt>example|color=1}}

{{tplt>example|color=2}}

{{tplt>example|color=3}}

{{tplt>example|color=4}}

{{tplt>example|color=5}}
```
输出的结果是
> red
>
> yellow
>
> green
> 
> blue
>
> other

在某些情况下，可能希望多个参数值对应一个输出值，在开关语句中，如果一个参数值后面没有写英文冒号 “`:`”，那么就认为与下一个参数值共用一个输出值。

例如，“`example`” 模板内有如下内容：
```
{{{#switch>color=other|1|2:red|3|4:blue}}}
```
然后我们在其他页面调用它：
```
{{tplt>example|color=1}}

{{tplt>example|color=2}}

{{tplt>example|color=3}}

{{tplt>example|color=4}}
```
输出的结果是
> red
>
> red
>
> blue
> 
> blue

## noinclude 与 includeonly
“`<noinclude>`” 与 “`<includeonly>`” 标签是用在模板页面的两个标签。被 “`<noinclude>`” 包围的内容只在模板页显示，而被 “`<includeonly>`” 包围的内容只在被调用的地方显示。

因此，建议 “`<noinclude>`” 用来存放对模板的说明，而 “`<includeonly>`” 用来存放模板本身的内容，并起到在模板页隐藏模板内容的作用。

例如，“`example`” 模板内有如下内容：
```
<noinclude>
这是对模板的说明。
</noinclude>

<includeonly>
这是模板本身的内容。
</includeonly>
```
“`example`” 模板本身的页面显示如下：
> 这是对模板的说明。

然后我们在其他页面调用它：
```
{{tplt>example}}
```
输出的结果是：
> 这是模板本身的内容。

## 模板应用举例
### 产生有颜色的文本
模板 “`red`”：
```
<html><span style="color:red"></html>{{{1}}}<html></span></html>
```
调用：
```
{{tplt>red|这是红色的字。}}
```
效果：
> <span style="color:red">这是红色的字。</span>

### 给汉字注拼音
模板 “`ruby`”：
```
<html><ruby><rb></html>
{{{1}}}
<html></rb><rp>（</rp><rt></html>
{{{2}}}
<html></rt><rp>）</rp></ruby></html>
```
调用：
```
{{tplt>ruby|我|wǒ}}{{tplt>ruby|们|men}}
```
效果：
> <ruby><rb>我</rb><rp>（</rp><rt>wǒ</rt><rp>）</rp></ruby><ruby><rb>们</rb><rp>（</rp><rt>men</rt><rp>）</rp></ruby>

当然这里只列举了模板的两种应用，实际上可能有更多的应用。

## 关于缓存
TPLT 插件目前尚未引入任何的缓存处理机制，因此当一个模板内容改变时，引用该模板的页面的内容却不会改变，除非修改这个页面以重新生成缓存。关于这个问题，目前折中的办法是尽量少去修改模板的内容。

## 目前已知的问题
* 目前插件在处理反斜杠 “`\`” 时可能会连同其后面的几个字符发生转义，产生不正确的参数值。因此，请谨慎使用反斜杠。
<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                   Moss Anti-Plagiarism for Moodle                     //
//         https://github.com/hit-moodle/moodle-plagiarism_moss          //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Anti-Plagiarism by Moss
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allresults'] = '所有结果';
$string['basefile'] = '框架文件';
$string['basefile_help'] = 'Moss通常会报告所有成对匹配的代码。 提供框架文件后，出现在框架文件中的代码会在匹配结果中被忽略。比如，教师为作业提供的基础代码就是一种典型的框架文件。您可以同时提供多个框架文件。框架文件能改善评判结果，但没有它，也未必不能获得有价值的信息。';
$string['clicktoviewresults'] = '点击此处查看结果。';
$string['configrequired'] = '配置{$a}（必填）：';
$string['configoptional'] = '配置{$a}（可选）：';
$string['confirm'] = '确认';
$string['confirm_help'] = '此用户已被确认为抄袭者吗？

* 已确认为抄袭 - 是的，他/她是个抄袭者
* 未确认 - 不，他/她不是抄袭者
* 无图标 - 此用户没有选修此课

教师可以点击图标更改状态。每次点击都会向对应的用户发送通知消息。';
$string['confirmed'] = '已确认为抄袭';
$string['confirmedresults'] = '{$a->fullname}在<strong>{$a->total}</strong>项活动中有已确认的抄袭记录。';
$string['confirmmessage'] = '您确定一定以及肯定这是抄袭吗？';
$string['cygwinpath'] = 'Cygwin的安装目录';
$string['defaultlanguage'] = '缺省语言';
$string['disclosurehasmeasured']  ='已于{$a->timemeasured}对所有上传的文件进行了抄袭检测。';
$string['disclosurenevermeasured']  ='所有上传于此的文件都将被进行抄袭检测。';
$string['err_cygwinpath']  ='错误的Cygwin路径，或Cygwin中未安装perl';
$string['filepatterns'] = '文件名通配符';
$string['filepatterns_help'] = 'Glob格式。例如：\*.c，hello.\*，a?c.java。用空格分隔多个通配符。留空表示禁用此条配置。';
$string['language'] = '编程语言';
$string['matchedlines'] = '相似行数';
$string['matchedusers'] = '相似用户';
$string['messageprovider:moss_updates'] = 'Moss反抄袭通知';
$string['messageconfirmedhtml'] = '<p>您在“{$a->coursename}”中的“{$a->modulename}”里提交的文件被确认为“<em>抄袭</em>”。</p><p>访问<a href="{$a->link}">{$a->link}</a>了解更多细节。</p>';
$string['messageconfirmedtext'] = '您在“{$a->coursename}”中的“{$a->modulename}”里提交的文件被确认为“抄袭”。
访问 {$a->link} 了解更多细节。';
$string['messageunconfirmedhtml'] = '<p>您在“{$a->coursename}”中的“{$a->modulename}”里提交的文件被确认为“<em>不是</em>”抄袭。</p><p>访问<a href="{$a->link}">{$a->link}</a>了解更多细节。</p>';
$string['messageunconfirmedtext'] = '您在课程“{$a->coursename}”中的活动“{$a->modulename}”里提交的文件被确认为“不是”抄袭。
访问 {$a->link} 了解更多细节。';
$string['moss'] = 'Moss反抄袭';
$string['moss_help'] = '<a href="http://theory.stanford.edu/~aiken/moss/">Moss</a> (Measure Of Software Similarity) 是一个自动检测源代码等纯文本文件的相似度的系统。

注意，只有在moss启用时提交的文件才会被检测。';
$string['moss:confirm'] = '确认抄袭行为';
$string['moss:viewallresults'] = '查看所有人的结果';
$string['moss:viewdiff'] = '查看成对比较视图';
$string['moss:viewunconfirmed'] = '查看未确认的结果';
$string['mossexplain'] = '<a href="https://github.com/hit-moodle/moodle-plagiarism_moss">Moss反抄袭插件</a>由<a href="http://www.hit.edu.cn/">哈尔滨工业大学</a>开发。反抄袭引擎使用<a href="http://theory.stanford.edu/~aiken/moss/">Moss</a>。';
$string['mossenabled'] ='启用Moss';
$string['mossuserid'] ='Moss账号';
$string['mossuserid_help'] ='向<a href="mailto:moss@moss.stanford.edu">moss@moss.stanford.edu</a>发送一封邮件就能获得Moss账号。邮件正文必须是<strong>纯文本</strong>（没有任何html标记），且完全符合下面的格式：

    registeruser
    mail username@domain

如果注册成功，会收到一封带有perl脚本的邮件。脚本中有一行和下面代码很相似的代码：

    $userid=1234567890;

其中的数字就是您能的Moss账号。';
$string['nocmresults'] = '此活动没有抄袭记录';
$string['nouserresults'] = '没有与{$a}有关的抄袭记录';
$string['percentage'] = '相似率';
$string['personalresults'] = '个人结果';
$string['pluginname'] = 'Moss反抄袭';
$string['resultlinktitle'] = '有至多{$a->percentage}%（{$a->linesmatched}行）的内容与其他{$a->count}名用户相似';
$string['savedconfigsuccess'] = '配置保存成功';
$string['sensitivity'] = '灵敏度';
$string['sensitivity_help'] = '灵敏度参数设定了一段代码被忽略前可以出现的次数。一段出现在很多程序中的代码可能是合理的共享，而不是抄袭。当灵敏度被设为N，在超过N个程序中都出现的代码段会被看做是框架文件的一部分，而不会在结果中被报告。设为2，moss将报告只出现在两个程序中的相似代码段。如果想找到多个非常相似的作业，（例如，在程序设计课程的第一次作业中），那么使用3或者4，就可以发现3人或4人成组抄袭。设为1000000（或任何很大的数），moss会报告所有发现的匹配，无论它们出现的频率有多高。 这个选项对大型作业很有用，但最好同时还能提供一个包含所有合法共享的代码的框架文件。';
$string['tag'] = '标签';
$string['tag_help'] = '使用相同标签的不同活动会被一起检测抄袭。标签可以非常方便地用来防止在课程之间的抄袭行为。';
$string['timetomeasure']  ='检测开始时间';
$string['timetomeasure_help']  ='此时间应在所有被检测文件都已提交之后。';
$string['timetomeasure_help']  ='设置检测抄袭的时间。如果不设置，会在活动结束时间之后开始检测。

检测只在设定的时间，针对当时已提交的文件，执行一次。如果想再次检测，请重新设置此时间。';
$string['unconfirmed'] = '未确认';
$string['unsupportedmodule']  ='Moss不支持此模块。';
$string['updating'] = '更新中...';


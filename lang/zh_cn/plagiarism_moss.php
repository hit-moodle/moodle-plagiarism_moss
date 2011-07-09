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

$string['configrequired'] = '配置{$a}（必填）：';
$string['configoptional'] = '配置{$a}（可选）：';
$string['filepatterns'] = '文件名通配符';
$string['filepatterns_help'] = 'Glob格式。例如：\*.c，hello.\*，a?c.java。用空格分隔多个通配符。留空表示禁用此条配置。';
$string['moss'] = 'Moss反抄袭';
$string['moss_help'] = '<a href="http://theory.stanford.edu/~aiken/moss/">Moss</a> (Measure Of Software Similarity) 是一个程序相似性自动检测系统。';
$string['mossexplain'] = '详细信息请访问: ';
$string['mossenabled'] ='启用Moss';
$string['mossuserid'] ='Moss账号';
$string['mossuserid_help'] ='向<a href="mailto:moss@moss.stanford.edu">moss@moss.stanford.edu</a>发送一封邮件就能获得Moss账号。邮件正文必须是<strong>纯文本</strong>（没有任何html标记），且完全符合下面的格式：

    registeruser
    mail username@domain

如果注册成功，会收到一封带有perl脚本的邮件。脚本中有一行和下面代码很相似的代码：

    $userid=1234567890;

其中的数字就是您能的Moss账号。';
$string['resultlinktitle'] = '此文件有至多{$a->percentage}%（{$a->linesmatched}行）的内容与其他{$a->count}名用户相似';
$string['savedconfigsuccess'] = '配置保存成功';
$string['studentdisclosure'] = '显示给学生的声明';
$string['studentdisclosure_help'] = '这些文字会在文件上传页面显示给所有学生。';
$string['studentdisclosuredefault']  ='所有上传的文件将通过Moss反抄袭引擎进行反抄袭检测。';
$string['tag'] = '标签';
$string['tag_help'] = '使用相同标签的不同活动会被一起检测抄袭。标签可以非常方便地用来防止在课程之间的抄袭行为。';
$string['timetomeasure']  ='检测开始时间';
$string['timetomeasure_help']  ='此时间应在所有被检测文件都已提交之后。';
$string['unsupportedmodule']  ='Moss不支持此模块。';

//general setting page
$string['general_settings'] = '插件综合配置';

//error log page
$string['error_log'] = '插件错误日志';
$string['error_date'] = '错误时间';
$string['error_type'] = '错误类型';
$string['error_description'] = '详情';
$string['error_solution'] = '提示';
$string['error_status'] = '状态';
$string['error_test'] = '测试';
$string['test'] = '测试';
$string['unsolved'] = '未解决';
$string['solved'] = '已解决';

//backup page
$string['plugin_backup'] = '插件备份';

//specific setting form
$string['activatemoss'] = '启用反抄袭';
$string['activateentry'] = '启用条目';
$string['language'] = '编程语言';
$string['sensitivity'] = '灵敏度';
$string['sensitivity_help'] = '灵敏度参数设定了一段代码被忽略前可以出现的次数。一段出现在很多程序中的代码可能是合理的共享，而不是抄袭。当灵敏度被设为N，在超过N个程序中都出现的代码段会被看做是框架文件的一部分，而不会在结果中被报告。设为2，moss将只报告出现在两个程序中的相似代码段。如果想找到多个非常相似的作业，（例如，在程序设计课程的第一次作业中），那么使用3或者4，就可以发现3人或4人成组抄袭。设为1000000（或任何很大的数），moss会报告所有发现的匹配，无论它们出现的频率有多高。 这个选项对大型作业很有用，同时最好还能提供一个包含所有合法共享的代码的框架文件。缺省值是20。';
$string['basefile'] = '框架文件';
$string['basefile_help'] = 'Moss通常会报告所有成对匹配的代码。 提供框架文件后，出现在框架文件中的代码会在匹配结果中被忽略。比如，教师为作业提供的基础代码就是一种典型的框架文件。您可以同时提供多个框架文件。框架文件能改善评判结果，但没有它，也未必不能获得有价值的信息。';

//view_all page
$string['view_all_title'] = '反抄袭结果记录页面';
$string['view_all_heading'] = '记录页面';
$string['plugin_name'] = '反抄袭';
$string['results'] = '结果';
$string['view_all'] = '查看记录';
$string['view_all_filter'] = '反抄袭记录过滤';
$string['entry_type'] = '选择记录类型';
$string['entry_type_help'] = 'TODO';
$string['entry_type_all'] = '所有记录';
$string['entry_type_confirmed'] = '已确认记录';
$string['entry_type_unconfirmed'] = '未确认记录';
$string['entry_type_cross'] = '跨课程抄袭记录';
$string['student_from_other_course'] = '其它课程学生';
$string['student_name'] = '学生姓名';
$string['student_name_help'] = 'TODO';
$string['student_name_western'] = '西方名 例如 "Peter Pan"';
$string['student_name_eastern'] = '东方名 例如 "张三"';
$string['rank_range'] = '雷同度范围';
$string['rank_range_help'] = 'TODO';
$string['percentage_range'] = '相似代码百分比范围';
$string['percentage_range_help'] = 'TODO';
$string['lines_range'] = '相似代码行数范围';
$string['lines_range_help'] = 'TODO';
$string['not_include'] = '不包括起始结束';
$string['undo'] = '撤销';
$string['redo'] = '重做';
$string['rank'] = '雷同度';
$string['student_name'] = '学生姓名';
$string['match_percent'] = '相似代码百分比';
$string['lines_match'] = '相似代码行数';
$string['code_detail'] = '代码详情';
$string['action'] = '动作';
$string['entry_status'] = '记录状态';
$string['relevant_entry'] = '相关记录';
$string['view_code'] = '查看代码';
$string['confirm'] = '确定抄袭';
$string['unconfirm'] = '取消确定';
$string['unconfirmed'] = '未确定';
$string['confirmed'] = '已确定';
$string['undo_redo_describtion'] = '点击“撤销”按钮来取消上一步操作...';
//hidden label
$string['confirm_prompt'] = '您确定这两个学生有抄袭行为吗？';
$string['unconfirm_prompt'] = '您确定这两个学生没有抄袭吗？';
$string['nothing_to_undo'] = '没有东西可以撤销！';
$string['nothing_to_redo'] = '没有东西可以重做！';
$string['parse_xml_exception'] = '在解析服务器返回的xml时发生错误！';
$string['request_rejected'] = '请求被服务器拒绝，请刷新页面后重试。';

//statistics page
$string['statistics_title'] = '反抄袭结果统计页面';
$string['statistics_heading'] = '统计界面';
$string['statistics'] = '统计';
$string['expand'] = '展开所有';
$string['contract'] = '缩回默认';
$string['expand_contract_describtion'] = '点击“展开所有”按钮来查看所有学生记录...';
$string['summary'] = '合计';
$string['assignment'] = '作业';

//view code page
$string['view_code_title'] = '查看代码详情页面';
$string['view_code_heading'] = '查看代码';
$string['close_window'] = '关闭窗口';

//student page
$string['student_page_title'] = '学生结果页面';
$string['student_page_heading'] = '反抄袭结果页面';
$string['appeal'] = '申述';

//relevant page
$string['relevant_title'] = '反抄袭相关条目页面';
$string['relevant_heading'] = '相关条目页面';
$string['relevant_type_filter'] = '相关记录过滤';
$string['all_relevant'] = '所有相关记录';
$string['complete_subgraph'] = '所有完全子图';
$string['relevant_type'] = '选择相关记录类型';
$string['relevant_type_help'] = 'TODO';


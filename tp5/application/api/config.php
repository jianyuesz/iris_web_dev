<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/23
 * Time: 17:15
 */
return [
    // 默认输出类型
    'default_return_type' => 'json',

    // 视图输出字符串内容替换
    'view_replace_str'       => [
        '__UPLOAD__' => SCRIPT_DIR  .'/api/upload',
    ],
];
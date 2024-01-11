<?php
/**
 * +----------------------------------------------------------------------
 * 应用入口
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：惊蛰 <1765321014@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

return [
    // 是否开启连接数监控
    'is_monitor' => true,
    //超时时间
    'time_out' => 10,
    // 连接池数量
    'pool_num' => 0,
    //memcache 支持单机和集群  数组中一个数据为单机 多个为集群
    'host'=>[
        '127.0.0.1:11211',
        '127.0.0.1:11212',
    ],
    // 空闲连接池检测间隔时间(S)
    'monitor_time' => 1200,
    // 空闲连接回收时间(S)
    'spare_time' => 600,
    // 连接最长保持时间(0永久)，不建议修改
    'connectTimeoutMS' => 0,
    // 单条命令的最长执行时间(毫秒)
    'socketTimeoutMS' => 5000,

    // 打开包长检测特性
    'open_length_check'     => true,
    // 长度值的类型
    'package_length_type'   => 'N',
    // 第N个字节是包长度的值
    'package_length_offset' => 0,
    // 第几个字节开始计算长度
    'package_body_offset'   => 4,
    // 协议最大长度
    'package_max_length'    => 2000000,

];
USE trans_db;
SET NAMES utf8;


DROP TABLE IF EXISTS `trans_task`;
CREATE TABLE `trans_task` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID值，主键自增',
    `meta` TEXT NULL COMMENT 'JSON方式存储的任务信息（界面选择需要传输的文件）',
    `state` CHAR(50) NOT NULL DEFAULT '' COMMENT '任务task当前的运行状态，如：TASK_WAITING(任务等待扫描)|TASK_SCANNING(任务处于扫描中)|TASK_SCANNED(任务扫描结束)|TASK_FINISHED(任务传输完成，结束)',
    `created_at` DATETIME NULL COMMENT '创建时间',
    `updated_at` DATETIME NULL COMMENT '修改时间',
    PRIMARY KEY (`id`)
)
    COLLATE='utf8_unicode_ci'
    ENGINE=InnoDB
    COMMENT '界面添加的需要传输的文件任务表'
;



DROP TABLE IF EXISTS `trans_list`;
CREATE TABLE `trans_list` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID值，主键自增',
    `task_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '相关联的task',
    `record` TEXT NOT NULL COMMENT '一条传输记录',
    `size` INT(50) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小',
    `state` CHAR(50) NOT NULL DEFAULT '' COMMENT '传输当前的运行状态，如：WAITING(等待传输)|HANDING(传输中)|HANDED(传输完成)|OFFLINE(离线，传输该记录数据的进程掉线)',
    `keep_alive` char(15) NOT NULL DEFAULT '' COMMENT '时间戳：心跳机制，若HANDING状态下，大于5s未更新，则传输该记录的线程掉线',
     # 或者客户端采用文件锁（文件名为taskid_id）的形式取得独占
     # 若存在HANDING状态，且线程掉线，则主进程需新起传输线程，该新起传输进程对文件上锁，成功上锁则传输，不成功则获取下一个HANDING记录

    `created_at` DATETIME NULL COMMENT '创建时间',
    `updated_at` DATETIME NULL COMMENT '修改时间',
    PRIMARY KEY (`id`)
)
    COLLATE='utf8_unicode_ci'
    ENGINE=InnoDB
    COMMENT '扫描出来的传输文件列表'
;
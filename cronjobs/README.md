Cronjob目录
==========

统一放置各种需要定时执行的脚本

需要添加以下行到Crontab:
* * * * * /usr/bin/haojing [path]/JobManager.php

文件说明：  
JobManager.php  
crontab每分钟调用其一次，便利cronjob目录，执行一次所有文件名以Job.php结尾的文件。  
如果文件有内部错误，不会被执行，会直接忽略。

SampleJob.php  
继承CronJob类的一个Sample

*Job.php  
每个文件应该都是可以单独通过 haojing xxJob.php直接执行的。  
推荐继承CronJob类，以获得单机锁，log，onMinute等方法。写法参见SampleJob  
当然你也可以写任何你喜欢的代码。但是注意：##你的代码每分钟都会被调用一次！##


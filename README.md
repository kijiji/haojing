haojing
=======

镐京,新的都城，新的开始!  


说明
----

目录层次:

>vhosts  => apache配置文件目录（包含rewrite规则）  
>htdocs  => 网站代码目录  
>cronjobs  => 定时脚本代码目录  
>cli.php  => 命令行工具  
>>config   => 配置文件目录  
>>graph    => 数据访问层
>>lib    => 镐京的核心逻辑

访问
----
镐京兼容朝歌的URL，如：/u/12345，或 /ershouqiche/  
镐京的JSON format graph endpoint: /g/，比如：http://haojing.baixing.com/g/u12345  
或 http://haojing.baixing.com/g/ershouqiche/ad?area=m30  

开发建议
----
如何修改和提交代码：  
推荐每个人都fork一份代码，然后在自己的repo里面做分支开发。  
当开发完毕需要上线的时候，先提交pull request到项目主干，同时指派给别人做review code。  
code review没通过的不准合并进主干上线。  


关于命令行工具 cli.php：
为了方便大家写脚本或者测试代码，镐京提供了命令行脚本
大家在写脚本代码的时候不用考虑类的自动加载问题
默认设置：chmod +x /path/cli.php && ln -s /path/cli.php /usr/bin/haojing

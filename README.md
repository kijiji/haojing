haojing
=======

镐京,新的都城，新的开始!  


说明
----

目录层次:

>vhosts  => apache配置文件目录（包含rewrite规则）  
>htdocs  => 代码目录  
>>config   => 配置文件目录  
>>graph    => 数据访问层


如何初始化代码：

推荐直接用Github的客户端将项目(或者自己fork的项目)clone到本地，他会自动获取所有关联的submudule。  
or 命令行：
git clone --recursive git://github.com/baixing/haojing.git  


如何修改和提交代码：  
推荐每个人都fork一份代码，然后在自己的repo里面做分支开发。  
当开发完毕需要上线的时候，先提交pull request到项目主干，同时指派给别人做review code。  
code review没通过的不准合并进主干上线。  
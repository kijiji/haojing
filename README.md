haojing
=======

镐京,新的都城，新的开始!  


说明
----

目录层次:

>vhosts  => apache配置文件目录（包含rewrite规则）  
>htdocs  => 代码目录  
>>config   => 配置文件目录  
>>graph    => 数据访问层，submodule，link to baixing/haojin_graph


如何初始化代码：

推荐直接用Github的客户端将项目(或者自己fork的项目)clone到本地，他会自动加载所有关联的submudule。  


关于submodule:

graph folder是以submodule的形式引入项目的。  
要更新一个submodule的代码，请用 cd graph && git pull origin master   
关于submodule的相关概念和操作，推荐看看：http://josephj.com/entry.php?id=342  



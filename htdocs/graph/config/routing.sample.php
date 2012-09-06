<?php
//yubing@baixing.com

return [
	'Data' => [
		'id_prefix' => 'd',
		'storage' => [
			'type' => 'MysqlStorage',
			'db' => 'db_storage_name',
			'table' => 'sample_table',
			'columns' => [
				'id' => 'id_col_name',
				'name' => 'name_col_name',
				'content' => 'content_col_name',
				'userId' => 'uid_col_name',
				'createdTime' => 'created_time_col_name',
				'modifiedTime' => 'modified_time_col_name',
				'attributeData' => 'attri_col_name',
				'images'	=>	'images_col_name',
			],
		],
		'ref' => [
			'images' => [
				'type'	=>	'Image',
				'col'	=>	'images',
				'id_delimiter'	=>	' ',
			],
		],
	],
	'Ip' => [
		'id_prefix' => 'ip:',
		'storage' => [
			'type' => 'IpStorage',
		],
	],
	'Image' => [
		'id_prefix' => 'img:',
		'storage' => [
			'type' => 'ImageStorage',
		],
	],
];

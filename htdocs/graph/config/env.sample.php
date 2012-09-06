<?php
//yubing@baixing.com

return	[
	'mysql' => [
		'storage_name' => [
			'read' => [
				'host'		=>	'db_ip',
				'user'		=>	'db_user',
				'password'	=>	'db_passwd',
				'database'	=>	'db_name',
			],
		],
	],
	'searcher' => [
		'read'	=>	'http://localhost:9200/',
		'write'	=>	'http://localhost:9200/',
	],
	'mongo'	=> [
		'storage_name'	=> [
			'read' => [
				'server' => 'mongodb://localhost:27017',
				'option' => [ 'timeout' => 15 ],
			],
		],
	],
];

<?php

/**
 * Test: Nette\DI\Config\Adapters\NeonAdapter
 */

declare(strict_types=1);

use Mallgroup\DI\Config\Adapters\EnvironmentAdapter;
use Nette\DI\Config;
use Nette\DI\Definitions\Statement;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

define('TEMP_FILE', getTempDir() . '/cfg.env');

Mallgroup\setenv('SERVICE_ARRAY', 'one|two');
Mallgroup\setenv('SERVICE_ARRAY_INT', '1|2');

$config = new Config\Loader();
$config->addAdapter('env', EnvironmentAdapter::class);


$cfg = '
service_user: ::string(default: secret_user)
service_password: ::string(secret_password, true)
service_port: ::int(1234)
service_nonstring: ::nonstring(1234)
service_active: ::bool(\'false\')
service_array: ::array(|)
service_array_int: ::array(cast: int)
';
$data = $config->load(Tester\FileMock::create($cfg, 'env'));
Assert::equal([
					  'parameters' => [
							  'env' =>
									  [
											  'service_user' => 'secret_user',
											  'service_password' => new Statement(
													  'Mallgroup\Environment::string',
													  [
															  'name' => 'SERVICE_PASSWORD',
															  'default' => 'secret_password'
													  ]
											  ),
											  'service_port' => 1234,
											  'service_nonstring' => '1234',
											  'service_active' => false,
											  'service_array' => ['one', 'two'],
											  'service_array_int' => [1, 2],
									  ]
					  ]
			  ], $data);


$config->save($data, TEMP_FILE);
Assert::match(
		<<<'EOD'
	# generated by Nette

	service_user: secret_user
	service_password: ::string(hidden: true, default: secret_password)
	service_port: 1234
	service_nonstring: "1234"
	service_active: false
	service_array:
		- one
		- two

	service_array_int:
		- 1
		- 2
	EOD
		,
		file_get_contents(TEMP_FILE)
);

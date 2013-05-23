//	Include library
require_once 'pop3php.php';

//	Create a filter rule
$mailFilterRule = new pop3php_FilterRule
(
	array
	(
		'header'            => 'Subject',
		'include'			=> 'foo'
		'exclude'           => 'bar',
	)
);

//	Create a filter
$mailFilter = new pop3php_Filter
(
	array
	(
		'rules'             =>  array
								(
									$mailFilterRule,
								),
	)
);

//	Create a client
$mailClient = new pop3php_Client
(
	array
	(
		'host'              => 'host',
		'port'              => '110',
		'user'              => 'user',
		'password'          => 'password',
		'connectionTimeout' => 10,
		'filter'            => $mailFilter,
	)

);

try
{

	//	Establish connection
	$mailClient->cmdConnect();
	$mailClient->cmdLogin();
	$mailClient->cmdPassword();

	//	Get all letters from the server
	$letters = $mailClient->getAllLetters();

	//	Iterate over filtered letters
	foreach ( $letters['success'] as $letter )
	{

		// Do some stuff with each letter

	}

	//  Disconnect
	$mailClient->cmdDisconnect();

}

//	Somthing gone wrong
catch(Exception $exception)
{

	// Show the error
	echo $exception->getMessage();

}
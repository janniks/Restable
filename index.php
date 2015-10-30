<?php

require_once 'Restable/Restable.php';

function what_a_function() {
	echo 'such fun!';
}

$api = new Restable();

$api->bad_path = function() {
	echo 'new bad path';
};

$api->get('/test/:id', function($id) use ($api) {
	$api->json(array(
		'id' => $id,
	));
}, array(
	'before' => 'what_a_function',
));

$api->start();

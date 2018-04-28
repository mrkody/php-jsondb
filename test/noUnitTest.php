<?php
declare(strict_types = 1);
/**
 * File: noUnitTest.php;
 * Author: nazbav;
 * Date: 19.04.2018;
 * Time: 0:46;
 */
include_once '../vendor/autoload.php';

use nazbav\JsonDb\JsonDb;

$json_db = new JsonDb();
$json_db->from('users.json');

$json_db->insert(
    [
        'name' => 'Вася',
        'state' => 'Россия',
        'age' => 'стока не живут!'
    ]
);

$rows_data = $json_db->orderBy('name', JsonDb::ASC_SORT)->get();
echo 's1----------------------', PHP_EOL;
foreach ($rows_data as $row_dara) {
    print_r($row_dara);
    echo "<br>";
}
echo 's1----------------------', PHP_EOL, 's2----------------------', PHP_EOL;
$users = $json_db->select('*')
    ->from('users.json')
    ->get();
print_r($users);
echo 's2----------------------', PHP_EOL, 's3----------------------', PHP_EOL;
$users = $json_db->select('name, state')
    ->from('users.json')
    ->get();
print_r($users);
echo 's3----------------------', PHP_EOL, 's4----------------------', PHP_EOL;
$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася'])
    ->get();
print_r($users);
echo 's4----------------------', PHP_EOL, 's5----------------------', PHP_EOL;
$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася', 'state' => 'Россия'])
    ->get();
print_r($users);
echo 's5----------------------', PHP_EOL, 's6----------------------', PHP_EOL;
$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася', 'state' => 'Россия'], 'AND')
    ->get();
print_r($users);
echo 's6----------------------', PHP_EOL, 's7----------------------', PHP_EOL;
$users = $json_db->select('name, state')
    ->from('users.json')
    ->where(['name' => 'Вася'])
    ->orderBy('age');
print_r($users);
echo 's7----------------------';
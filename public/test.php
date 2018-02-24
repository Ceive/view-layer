<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

use Ceive\View\Layer\Manager;

include '../vendor/autoload.php';



$manager = new Manager(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views');

$lay = $manager->createLaySequence([
	'main',
    'user',
    'profile'
]);

echo $lay->render();

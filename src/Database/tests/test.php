<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use PhailSafe\TestSuite;
use Avalon\Database\ConnectionManager;

use Avalon\Tests\Schema;
use Avalon\Tests\Models\User;

ConnectionManager::create([
    'driver' => 'pdo_sqlite',
    'memory' => true
]);

Schema::create();

TestSuite::tests(function($t){
    $t->group("Model Tests", function($group){
        $group->test("Create model", function($test){
            $user = User::create([
                'username' => "tester"
            ]);

            $test->assertInstanceOf('Avalon\Tests\Models\User', $user);
        });

        $group->test("Save model", function($test){
            $user = new User([
                'username' => "another_tester"
            ]);

            $test->assertTrue($user->save());
        });

        $group->test("Finder user", function($test){
            User::insert(['id' => 301, 'username' => time()]);

            $user = User::find(301);

            $test->assertEqual(301, $user->id);
            $test->assertFalse(User::find(404));
        });
    });
});

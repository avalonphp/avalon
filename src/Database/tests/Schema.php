<?php

namespace Avalon\Tests;

use Doctrine\DBAL\Schema\Schema as DbSchema;
use Avalon\Database\ConnectionManager;

class Schema
{
    public static function create()
    {
        $conn = ConnectionManager::getConnection();

        $schema = new DbSchema;

        $users = $schema->createTable("users");
        $users->addColumn("id", "integer", ["unsigned" => true]);
        $users->addColumn("username", "string", ["length" => 32]);
        $users->setPrimaryKey(["id"]);
        $users->addUniqueIndex(["username"]);

        foreach ($schema->toSql($conn->getDatabasePlatform()) as $query) {
            $conn->query($query);
        }
    }
}

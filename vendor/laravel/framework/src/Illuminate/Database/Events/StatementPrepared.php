<?php

namespace Illuminate\Database\Events;

class StatementPrepared
{
    /**
     * The database connection instance.
     *
     * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * The PDO statement.
     *
     * PDO声明
     *
     * @var \PDOStatement
     */
    public $statement;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \PDOStatement  $statement
     * @return void
     */
    public function __construct($connection, $statement)
    {
        $this->statement = $statement;
        $this->connection = $connection;
    }
}

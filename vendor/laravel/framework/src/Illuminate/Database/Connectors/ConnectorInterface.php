<?php

namespace Illuminate\Database\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * 建立数据库连接
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config);
}

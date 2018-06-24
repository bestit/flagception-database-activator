<?php

namespace Flagception\Database\Activator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Flagception\Activator\FeatureActivatorInterface;
use Flagception\Model\Context;

/**
 * Activator for database flags
 *
 * @author Michel Chowanski <michel.chowanski@bestit-online.de>
 * @package Flagception\Database\Activator
 */
class DatabaseActivator implements FeatureActivatorInterface
{
    /**
     * @var array|null
     */
    private $dsn;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Connection|null
     */
    private $connection;

    /**
     * DatabaseActivator constructor.
     *
     * @param Connection|array $clientOrDsn
     * @param array $options
     */
    public function __construct($clientOrDsn, array $options)
    {
        if ($clientOrDsn instanceof Connection) {
            $this->connection = $clientOrDsn;
        } else {
            $this->dsn = $clientOrDsn;
        }

        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive($name, Context $context)
    {
    }

    public function setup()
    {
        $schema = new Schema();
        $myTable = $schema->createTable($this->options['db_table']);
        $myTable->addColumn($this->options['db_column_id'], 'integer', ['unsigned' => true]);
        $myTable->addColumn($this->options['db_column_feature'], 'string', ['length' => 255]);
        $myTable->addColumn($this->options['db_column_state'], 'boolean');
        $myTable->addColumn($this->options['db_column_expression'], 'string', ['length' => 255]);
        $myTable->setPrimaryKey(['id']);

        $platform = $this->getConnection()->getDatabasePlatform();
        $queries = $schema->toSql($platform);

        foreach ($queries as $query) {
            $this->getConnection()->executeQuery($query);
        }
    }

    private function getConnection(): Connection
    {
        // Initiate connection if not exist
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection($this->dsn);
        }

        return $this->connection;
    }
}

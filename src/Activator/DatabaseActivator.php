<?php

namespace Flagception\Database\Activator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Flagception\Activator\FeatureActivatorInterface;
use Flagception\Model\Context;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Activator for database flags
 *
 * @author Michel Chowanski <michel.chowanski@bestit-online.de>
 * @package Flagception\Database\Activator
 */
class DatabaseActivator implements FeatureActivatorInterface
{
    /**
     * DSN as array or null if not set
     *
     * @var array<string>|null
     */
    private $dsn;

    /**
     * Table options
     *
     * @var array<string>
     */
    private $options;

    /**
     * Active connection or null if no connection exists
     *
     * @var Connection|null
     */
    private $connection;

    /**
     * Does table exists
     *
     * @var bool
     */
    private $tablesExist = false;

    /**
     * DatabaseActivator constructor.
     *
     * @param Connection|array<string> $clientOrDsn
     * @param array<string> $options
     */
    public function __construct($clientOrDsn, array $options = [])
    {
        if ($clientOrDsn instanceof Connection) {
            $this->connection = $clientOrDsn;
        } else {
            $this->dsn = $clientOrDsn;
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'db_table' => 'flagception_features',
            'db_column_feature' => 'feature',
            'db_column_state' => 'state'
        ]);

        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     *
     * @throws DBALException|DBALDriverException
     */
    public function isActive($name, Context $context): bool
    {
        $this->setup();

        // $result contains the response from state (true / false) or false if no feature found
        $result = $this->getConnection()->fetchOne(
            sprintf(
                'SELECT %s FROM %s WHERE %s = :feature_name',
                $this->options['db_column_state'],
                $this->options['db_table'],
                $this->options['db_column_feature'],
            ),
            ['feature_name' => $name]
        );

        return is_bool($result) ? $result : filter_var($result, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Create feature table
     *
     * @return void
     *
     * @throws DBALException
     */
    private function setup(): void
    {
        $manager = $this->createSchemaManager();
        if ($this->tablesExist === true || $manager->tablesExist([$this->options['db_table']]) === true) {
            $this->tablesExist = true;

            return;
        }

        $schema = new Schema();
        $table = $schema->createTable($this->options['db_table']);

        $table->addColumn($this->options['db_column_feature'], 'string', ['length' => 255]);
        $table->addColumn($this->options['db_column_state'], 'boolean');

        $table->setPrimaryKey([$this->options['db_column_feature']]);

        $platform = $this->getConnection()->getDatabasePlatform();
        $queries = $schema->toSql($platform);

        foreach ($queries as $query) {
            $this->getConnection()->executeStatement($query);
        }

        $this->tablesExist = true;
    }

    /**
     * Get connection or create a new connection
     *
     * @return Connection
     *
     * @throws DBALException
     */
    public function getConnection(): Connection
    {
        // Initiate connection if not exist
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection($this->dsn);
        }

        return $this->connection;
    }

    /**
     * Fetches the schema manager from the DBAL connection.
     *
     * @throws DBALException
     */
    private function createSchemaManager(): ?AbstractSchemaManager
    {
        $connection = $this->getConnection();

        // BC for DBAL 2
        return method_exists($connection, 'createSchemaManager')
            ? $connection->createSchemaManager()
            : $connection->getSchemaManager();
    }
}

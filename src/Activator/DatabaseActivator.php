<?php

namespace Flagception\Database\Activator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
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
     * @var array|null
     */
    private $dsn;

    /**
     * Table options
     *
     * @var array
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
     * @param Connection|array $clientOrDsn
     * @param array $options
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
    public function getName()
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     *
     * @throws DBALException
     */
    public function isActive($name, Context $context)
    {
        $this->setup();

        $builder = $this->getConnection()->createQueryBuilder();

        // $result contains the response from state (true / false) or false if no feature found
        $result = $builder
            ->select(
                $this->options['db_column_state']
            )
            ->from($this->options['db_table'])
            ->where(sprintf('%s = :feature', $this->options['db_column_feature']))
            ->setParameter('feature', $name)
            ->execute()
            ->fetchColumn();

        return is_bool($result) ? $result : filter_var($result, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Create feature table
     *
     * @return void
     *
     * @throws DBALException
     */
    private function setup()
    {
        $manager = $this->getConnection()->getSchemaManager();
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
            $this->getConnection()->executeQuery($query);
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
    public function getConnection()
    {
        // Initiate connection if not exist
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection($this->dsn);
        }

        return $this->connection;
    }
}

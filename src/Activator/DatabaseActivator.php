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
            'db_column_id' => 'id',
            'db_column_feature' => 'feature',
            'db_column_state' => 'state'
        ]);
        $this->mapping = $resolver->resolve($options);

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
     *
     * @throws DBALException
     */
    public function isActive($name, Context $context)
    {
        $builder = $this->getConnection()->createQueryBuilder();

        /**
         * $result contains the response from state (true / false) or false if no feature found
         */
        $result = $builder
            ->select(
                $this->options['db_column_state']
            )
            ->from($this->options['db_table'])
            ->where(sprintf('%s = :feature', $this->options['db_table']))
            ->setParameter('feature', $name)
            ->execute()
            ->fetchColumn();

        return $result;
    }

    /**
     * Create feature table
     *
     * @return void
     *
     * @throws DBALException
     */
    public function setup()
    {
        $manager = $this->getConnection()->getSchemaManager();
        if ($manager->tablesExist([$this->options['db_table']]) === true) {
            return;
        }

        $schema = new Schema();
        $table = $schema->createTable($this->options['db_table']);

        $table->addColumn($this->options['db_column_id'], 'integer', ['unsigned' => true]);
        $table->addColumn($this->options['db_column_feature'], 'string', ['length' => 255]);
        $table->addColumn($this->options['db_column_state'], 'boolean');

        $table->setPrimaryKey([$this->options['db_column_id']]);
        $table->addIndex([$this->options['db_column_feature']]);

        $platform = $this->getConnection()->getDatabasePlatform();
        $queries = $schema->toSql($platform);

        foreach ($queries as $query) {
            $this->getConnection()->executeQuery($query);
        }
    }

    /**
     * Get connection or create a new connection
     *
     * @return Connection
     *
     * @throws DBALException
     */
    private function getConnection()
    {
        // Initiate connection if not exist
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection($this->dsn);
        }

        return $this->connection;
    }
}

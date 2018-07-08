<?php

namespace Flagception\Database\Tests\Activator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Flagception\Activator\FeatureActivatorInterface;
use Flagception\Database\Activator\DatabaseActivator;
use Flagception\Model\Context;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests the database activator
 *
 * @author Michel Chowanski <chowanski@bestit-online.de>
 * @package Flagception\Tests\Activator
 */
class DatabaseActivatorTest extends TestCase
{
    /**
     * Test implement interface
     *
     * @return void
     */
    public function testImplementInterface()
    {
        static::assertInstanceOf(FeatureActivatorInterface::class, new DatabaseActivator(
            $this->createMock(Connection::class)
        ));
    }

    /**
     * Test name
     *
     * @return void
     */
    public function testName()
    {
        $activator = new DatabaseActivator($this->createMock(Connection::class));
        static::assertEquals('database', $activator->getName());
    }

    /**
     * Test connection by credentials
     *
     * @return void
     *
     * @throws DBALException
     */
    public function testSetConnectByCredentials()
    {
        $activator = new DatabaseActivator([
            'dbname' => 'mydb',
            'user' => 'user',
            'password' => 'secret',
            'host' => 'localhost',
            'driver' => 'pdo_mysql'
        ]);

        static::assertInstanceOf(Connection::class, $activator->getConnection());
    }

    /**
     * Test connection by uri
     *
     * @return void
     *
     * @throws DBALException
     */
    public function testSetConnectByUri()
    {
        $activator = new DatabaseActivator([
            'url' => 'mysql://user:secret@localhost/mydb'
        ]);

        static::assertInstanceOf(Connection::class, $activator->getConnection());
    }

    /**
     * Test connection by pdo
     *
     * @return void
     *
     * @throws DBALException
     */
    public function testSetConnectByPdo()
    {
        $activator = new DatabaseActivator([
            'pdo' => $pdo = $this->createMock(PDO::class)
        ]);

        $pdo
            ->expects(static::once())
            ->method('getAttribute')
            ->with(PDO::ATTR_DRIVER_NAME)
            ->willReturn('mysql');

        static::assertInstanceOf(Connection::class, $activator->getConnection());
    }

    /**
     * Test connection by dbal instance
     *
     * @return void
     *
     * @throws DBALException
     */
    public function testSetConnectByDbalInstance()
    {
        $activator = new DatabaseActivator(
            $dbal = $this->createMock(Connection::class)
        );

        static::assertSame($dbal, $activator->getConnection());
    }

    /**
     * Test setup with default options
     *
     * @return void
     *
     * @throws DBALException
     */
    public function testSetupDefaultOptions()
    {
        $activator = new DatabaseActivator([
                'url' => 'sqlite:///:memory:'
            ]
        );

        $activator->setup();

        $this->runIntegration(
            $activator->getConnection(),
            'flagception_features',
            'feature',
            'state'
        );
    }

    /**
     * Test setup with default options
     *
     * @return void
     *
     * @throws DBALException
     */
    public function testSetupWithOptions()
    {
        $activator = new DatabaseActivator(
            [
                'url' => 'sqlite:///:memory:'
            ],
            [
                'db_table' => 'my_feature_table',
                'db_column_feature' => 'foo_feature_name',
                'db_column_state' => 'foo_is_active'
            ]
        );

        $activator->setup();

        $this->runIntegration(
            $activator->getConnection(),
            'my_feature_table',
            'foo_feature_name',
            'foo_is_active'
        );
    }

    /**
     * Test active states
     *
     * @return void
     *
     * @throws DBALException
     */
    public function testActiveStates()
    {
        $activator = new DatabaseActivator([
                'url' => 'sqlite:///:memory:'
            ]
        );

        $activator->setup();

        $this->runIntegration(
            $activator->getConnection(),
            'flagception_features',
            'feature',
            'state'
        );

        static::assertTrue($activator->isActive('abc', new Context()));
        static::assertFalse($activator->isActive('xyz', new Context()));
    }

    /**
     * Run integration test
     *
     * @param Connection $connection
     * @param string $tableName
     * @param string $featureColumn
     * @param string $stateColumn
     *
     * @return void
     *
     * @throws DBALException
     */
    private function runIntegration(Connection $connection, $tableName, $featureColumn, $stateColumn)
    {
        $connection->insert($tableName, [
            $featureColumn => 'abc',
            $stateColumn => true
        ]);

        $connection->insert($tableName, [
            $featureColumn => 'xyz',
            $stateColumn => false
        ]);

        $result = $connection->query("SELECT $featureColumn, $stateColumn FROM $tableName")->fetchAll();

        static::assertEquals([
            [
                $featureColumn => 'abc',
                $stateColumn => true
            ],
            [
                $featureColumn => 'xyz',
                $stateColumn => false
            ]
        ], $result);
    }
}

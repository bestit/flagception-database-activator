<?php

namespace Flagception\Database\Tests\Activator;

use Doctrine\DBAL\Connection;
use Flagception\Activator\FeatureActivatorInterface;
use Flagception\Database\Activator\DatabaseActivator;
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
        $activator = new DatabaseActivator( $this->createMock(Connection::class));
        static::assertEquals('database', $activator->getName());
    }
}

<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: Apache-2.0
 *
 * The OpenSearch Contributors require contributions made to
 * this file be licensed under the Apache-2.0 license or a
 * compatible open source license.
 *
 * Modifications Copyright OpenSearch Contributors. See
 * GitHub history for details.
 */

namespace OpenSearch\Tests\ConnectionPool;

use OpenSearch;
use OpenSearch\ConnectionPool\Selectors\RoundRobinSelector;
use OpenSearch\ConnectionPool\StaticConnectionPool;
use OpenSearch\Connections\Connection;
use OpenSearch\Connections\ConnectionFactory;
use Mockery as m;

/**
 * Class StaticConnectionPoolTest
 *
 * @subpackage Tests/StaticConnectionPoolTest
 */
class StaticConnectionPoolTest extends \PHPUnit\Framework\TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testAddOneHostThenGetConnection()
    {
        $mockConnection = m::mock(Connection::class)
            ->shouldReceive('ping')
            ->andReturn(true)
            ->getMock()
            ->shouldReceive('isAlive')
            ->andReturn(true)
            ->getMock()
            ->shouldReceive('markDead')->once()->getMock();

        /**
 * @var \OpenSearch\Connections\Connection[]&\Mockery\MockInterface[] $connections
*/
        $connections = [$mockConnection];

        $selector = m::mock(RoundRobinSelector::class)
            ->shouldReceive('select')
            ->andReturn($connections[0])
            ->getMock();

        $connectionFactory = m::mock(ConnectionFactory::class);

        $connectionPoolParams = [
            'randomizeHosts' => false,
        ];
        $connectionPool = new StaticConnectionPool($connections, $selector, $connectionFactory, $connectionPoolParams);

        $retConnection = $connectionPool->nextConnection();

        $this->assertSame($mockConnection, $retConnection);
    }

    public function testAddMultipleHostsThenGetFirst()
    {
        $connections = [];

        foreach (range(1, 10) as $index) {
            $mockConnection = m::mock(Connection::class)
                ->shouldReceive('ping')
                ->andReturn(true)
                ->getMock()
                ->shouldReceive('isAlive')
                ->andReturn(true)
                ->getMock()
                ->shouldReceive('markDead')->once()->getMock();

            $connections[] = $mockConnection;
        }

        $selector = m::mock(RoundRobinSelector::class)
            ->shouldReceive('select')
            ->andReturn($connections[0])
            ->getMock();

        $connectionFactory = m::mock(ConnectionFactory::class);

        $connectionPoolParams = [
            'randomizeHosts' => false,
        ];
        $connectionPool = new StaticConnectionPool($connections, $selector, $connectionFactory, $connectionPoolParams);

        $retConnection = $connectionPool->nextConnection();

        $this->assertSame($connections[0], $retConnection);
    }

    public function testAllHostsFailPing()
    {
        $connections = [];

        foreach (range(1, 10) as $index) {
            $mockConnection = m::mock(Connection::class)
                ->shouldReceive('ping')
                ->andReturn(false)
                ->getMock()
                ->shouldReceive('isAlive')
                ->andReturn(false)
                ->getMock()
                ->shouldReceive('markDead')->once()->getMock()
                ->shouldReceive('getPingFailures')->andReturn(0)->once()->getMock()
                ->shouldReceive('getLastPing')->andReturn(time())->once()->getMock();

            $connections[] = $mockConnection;
        }

        $selector = m::mock(RoundRobinSelector::class)
            ->shouldReceive('select')
            ->andReturnValues($connections)
            ->getMock();

        $connectionFactory = m::mock(ConnectionFactory::class);

        $connectionPoolParams = [
            'randomizeHosts' => false,
        ];
        $connectionPool = new StaticConnectionPool($connections, $selector, $connectionFactory, $connectionPoolParams);

        $this->expectException(\OpenSearch\Common\Exceptions\NoNodesAvailableException::class);
        $this->expectExceptionMessage('No alive nodes found in your cluster');

        $connectionPool->nextConnection();
    }

    public function testAllExceptLastHostFailPingRevivesInSkip()
    {
        $connections = [];

        foreach (range(1, 9) as $index) {
            $mockConnection = m::mock(Connection::class)
                ->shouldReceive('ping')
                ->andReturn(false)
                ->getMock()
                ->shouldReceive('isAlive')
                ->andReturn(false)
                ->getMock()
                ->shouldReceive('markDead')->once()->getMock()
                ->shouldReceive('getPingFailures')->andReturn(0)->once()->getMock()
                ->shouldReceive('getLastPing')->andReturn(time())->once()->getMock();

            $connections[] = $mockConnection;
        }

        $goodConnection = m::mock(Connection::class)
            ->shouldReceive('ping')->once()
            ->andReturn(true)
            ->getMock()
            ->shouldReceive('isAlive')->once()
            ->andReturn(false)
            ->getMock()
            ->shouldReceive('markDead')->once()->getMock()
            ->shouldReceive('getPingFailures')->andReturn(0)->once()->getMock()
            ->shouldReceive('getLastPing')->andReturn(time())->once()->getMock();

        $connections[] = $goodConnection;

        $selector = m::mock(RoundRobinSelector::class)
            ->shouldReceive('select')
            ->andReturnValues($connections)
            ->getMock();

        $connectionFactory = m::mock(ConnectionFactory::class);

        $connectionPoolParams = [
            'randomizeHosts' => false,
        ];
        $connectionPool = new StaticConnectionPool($connections, $selector, $connectionFactory, $connectionPoolParams);

        $ret = $connectionPool->nextConnection();
        $this->assertSame($goodConnection, $ret);
    }

    public function testAllExceptLastHostFailPingRevivesPreSkip()
    {
        $connections = [];

        foreach (range(1, 9) as $index) {
            $mockConnection = m::mock(Connection::class)
                ->shouldReceive('ping')
                ->andReturn(false)
                ->getMock()
                ->shouldReceive('isAlive')
                ->andReturn(false)
                ->getMock()
                ->shouldReceive('markDead')->once()->getMock()
                ->shouldReceive('getPingFailures')->andReturn(0)->once()->getMock()
                ->shouldReceive('getLastPing')->andReturn(time())->once()->getMock();

            $connections[] = $mockConnection;
        }

        $goodConnection = m::mock(Connection::class)
            ->shouldReceive('ping')->once()
            ->andReturn(true)
            ->getMock()
            ->shouldReceive('isAlive')->once()
            ->andReturn(false)
            ->getMock()
            ->shouldReceive('markDead')->once()->getMock()
            ->shouldReceive('getPingFailures')->andReturn(0)->once()->getMock()
            ->shouldReceive('getLastPing')->andReturn(time()-10000)->once()->getMock();

        $connections[] = $goodConnection;

        $selector = m::mock(RoundRobinSelector::class)
            ->shouldReceive('select')
            ->andReturnValues($connections)
            ->getMock();

        $connectionFactory = m::mock(ConnectionFactory::class);

        $connectionPoolParams = [
            'randomizeHosts' => false,
        ];
        $connectionPool = new StaticConnectionPool($connections, $selector, $connectionFactory, $connectionPoolParams);

        $ret = $connectionPool->nextConnection();
        $this->assertSame($goodConnection, $ret);
    }

    public function testCustomConnectionPoolIT()
    {
        $clientBuilder = \OpenSearch\ClientBuilder::create();
        $clientBuilder->setHosts(['localhost:1']);
        $client = $clientBuilder
            ->setRetries(0)
            ->setConnectionPool(StaticConnectionPool::class, [])
            ->build();

        $this->expectException(OpenSearch\Common\Exceptions\NoNodesAvailableException::class);
        $this->expectExceptionMessage('No alive nodes found in your cluster');

        $client->search([]);
    }
}

<?php

namespace Graphp\Graph\Tests;

use Graphp\Graph\Graph;
use Graphp\Graph\Edge;
use Graphp\Graph\Tests\Attribute\AbstractAttributeAwareTest;

abstract class EdgeBaseTest extends AbstractAttributeAwareTest
{
    protected $graph;
    protected $v1;
    protected $v2;

    /**
     * @var Edge
     */
    protected $edge;

    abstract protected function createEdgeUndirected();

    /**
     * @return Edge
     */
    abstract protected function createEdgeLoop();

    public function setUp()
    {
        $this->graph = new Graph();
        $this->v1 = $this->graph->createVertex(1);
        $this->v2 = $this->graph->createVertex(2);

        $this->edge = $this->createEdgeUndirected();
    }

    public function testEdgeVertices()
    {
        $this->assertEquals(array($this->v1, $this->v2), $this->edge->getVertices()->getVector());
        $this->assertEquals(array(1, 2), $this->edge->getVertices()->getIds());

        $this->assertSame($this->graph, $this->edge->getGraph());
    }

    public function testEdgeStartVertex()
    {
        $this->assertTrue($this->edge->hasVertexStart($this->v1));
        $this->assertTrue($this->edge->hasVertexTarget($this->v2));

        $v3 = $this->graph->createVertex(3);

        $this->assertFalse($this->edge->hasVertexStart($v3));
        $this->assertFalse($this->edge->hasVertexTarget($v3));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEdgeFromInvalid()
    {
        $v3 = $this->graph->createVertex(3);
        $this->edge->getVertexFromTo($v3);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEdgeToInvalid()
    {
        $v3 = $this->graph->createVertex(3);
        $this->edge->getVertexToFrom($v3);
    }

    public function testLoop()
    {
        $edge = $this->createEdgeLoop();

        $this->assertTrue($edge->isLoop());
        $this->assertEquals(array($this->v1, $this->v1), $edge->getVertices()->getVector());
        $this->assertSame($this->v1, $edge->getVertexFromTo($this->v1));
        $this->assertSame($this->v1, $edge->getVertexToFrom($this->v1));
    }

    public function testRemoveWithLoop()
    {
        $edge = $this->createEdgeLoop();

        $this->assertEquals(array($this->edge, $edge), $this->graph->getEdges()->getVector());

        $edge->destroy();

        $this->assertEquals(array($this->edge), $this->graph->getEdges()->getVector());
        $this->assertEquals(array($this->v1, $this->v2), $this->graph->getVertices()->getVector());
    }

    protected function createAttributeAware()
    {
        return $this->createEdgeUndirected();
    }
}

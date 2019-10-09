<?php

namespace Graphp\Graph\Tests;

use Graphp\Graph\Exception\OverflowException;
use Graphp\Graph\Exception\InvalidArgumentException;
use Graphp\Graph\Graph;
use Graphp\Graph\Tests\Attribute\AbstractAttributeAwareTest;

class GraphTest extends AbstractAttributeAwareTest
{
    public function setup()
    {
        $this->graph = new Graph();
    }

    public function testVertexClone()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex(123);
        $vertex->setAttribute('balance', 29);
        $vertex->setAttribute('group', 4);

        $newgraph = new Graph();
        $newvertex = $newgraph->createVertexClone($vertex);

        $this->assertVertexEquals($vertex, $newvertex);
    }

    /**
     * test to make sure vertex can not be cloned into same graph (due to duplicate id)
     *
     * @expectedException RuntimeException
     */
    public function testInvalidVertexClone()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex(123);
        $graph->createVertexClone($vertex);
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testGetVertexNonexistant()
    {
        $graph = new Graph();
        $graph->getVertex('non-existant');
    }

    public function testGraphCloneNative()
    {
        $graph = new Graph();
        $graph->setAttribute('color', 'grey');
        $v = $graph->createVertex(123)->setAttribute('color', 'blue');
        $graph->createEdgeDirected($v, $v)->setAttribute('color', 'red');

        $newgraph = clone $graph;

        $this->assertGraphEquals($graph, $newgraph);

        $graphClonedTwice = clone $newgraph;

        $this->assertGraphEquals($graph, $graphClonedTwice);

        $this->assertNotSame($graph->getEdges(), $newgraph->getEdges());
        $this->assertNotSame($graph->getVertices(), $newgraph->getVertices());
    }

    /**
     * check to make sure we can actually create vertices with automatic IDs
     */
    public function testCanCreateVertex()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex();
        $this->assertInstanceOf('\Graphp\Graph\Vertex', $vertex);
    }

    /**
     * check to make sure we can actually create vertices with automatic IDs
     */
    public function testCanCreateVertexId()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex(11);
        $this->assertInstanceOf('\Graphp\Graph\Vertex', $vertex);
        $this->assertEquals(11, $vertex->getId());
    }

    /**
     * fail to create two vertices with same ID
     * @expectedException OverflowException
     */
    public function testFailDuplicateVertex()
    {
        $graph = new Graph();
        $graph->createVertex(33);
        $graph->createVertex(33);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateVertexWithInvalidIdThrows()
    {
        $graph = new Graph();
        $graph->createVertex(array('invalid'));
    }

    public function testCreateDuplicateReturn()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);

        $v1again = $graph->createVertex(1, true);

        $this->assertSame($v1, $v1again);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateEdgeUndirectedWithVerticesFromOtherGraphThrows()
    {
        // 1, 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);

        $graph2 = new Graph();
        $graph2->createEdgeUndirected($v1, $v2);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateEdgeDirectedWithVerticesFromOtherGraphThrows()
    {
        // 1, 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);

        $graph2 = new Graph();
        $graph2->createEdgeDirected($v1, $v2);
    }

    public function testHasVertex()
    {
        $graph = new Graph();
        $graph->createVertex(1);
        $graph->createVertex('string');

        // check integer IDs
        $this->assertFalse($graph->hasVertex(2));
        $this->assertTrue($graph->hasVertex(1));

        // check string IDs
        $this->assertFalse($graph->hasVertex('non-existant'));
        $this->assertTrue($graph->hasVertex('string'));

        // integer IDs can also be checked as string IDs
        $this->assertTrue($graph->hasVertex('1'));
    }

    public function testCreateMultigraph()
    {
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $graph->createEdgeUndirected($v1, $v2);
        $graph->createEdgeUndirected($v1, $v2);

        $this->assertEquals(2, count($graph->getEdges()));
        $this->assertEquals(2, count($v1->getEdges()));

        $this->assertEquals(array(2, 2), $v1->getVerticesEdge()->getIds());
    }

    public function testCreateMixedGraph()
    {
        // v1 -- v2 -> v3
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $v3 = $graph->createVertex(3);

        $graph->createEdgeUndirected($v1, $v2);
        $graph->createEdgeDirected($v2, $v3);

        $this->assertEquals(2, count($graph->getEdges()));

        $this->assertEquals(2, count($v2->getEdges()));
        $this->assertEquals(2, count($v2->getEdgesOut()));
        $this->assertEquals(1, count($v2->getEdgesIn()));

        $this->assertEquals(array(1, 3), $v2->getVerticesEdgeTo()->getIds());
        $this->assertEquals(array(1), $v2->getVerticesEdgeFrom()->getIds());
    }

    public function testCreateVerticesNone()
    {
        $graph = new Graph();

        $this->assertEquals(array(), $graph->createVertices(0)->getVector());
        $this->assertEquals(array(), $graph->createVertices(array())->getVector());

        $this->assertEquals(0, count($graph->getVertices()));
    }

    /**
     * expect to fail for invalid number of vertices
     * @expectedException InvalidArgumentException
     * @dataProvider createVerticesFailProvider
     */
    public function testCreateVerticesFail($number)
    {
        $graph = new Graph();
        $graph->createVertices($number);
    }

    public static function createVerticesFailProvider()
    {
        return array(
            array(-1),
            array("10"),
            array(0.5),
            array(null),
            array(array(1, 1))
        );
    }

    public function testCreateVerticesOkay()
    {
        $graph = new Graph();

        $vertices = $graph->createVertices(2);
        $this->assertCount(2, $vertices);
        $this->assertEquals(array(0, 1), $graph->getVertices()->getIds());

        $vertices = $graph->createVertices(array(7, 9));
        $this->assertCount(2, $vertices);
        $this->assertEquals(array(0, 1, 7, 9), $graph->getVertices()->getIds());

        $vertices = $graph->createVertices(3);
        $this->assertCount(3, $vertices);
        $this->assertEquals(array(0, 1, 7, 9, 10, 11, 12), $graph->getVertices()->getIds());
    }

    public function testCreateVerticesAtomic()
    {
        $graph = new Graph();

        // create vertices 10-19 (inclusive)
        $vertices = $graph->createVertices(range(10, 19));
        $this->assertCount(10, $vertices);

        try {
            $graph->createVertices(array(9, 19, 20));
            $this->fail('Should be unable to create vertices because of duplicate IDs');
        }
        catch (OverflowException $ignoreExpected) {
            $this->assertEquals(10, count($graph->getVertices()));
        }

        try {
            $graph->createVertices(array(20, 21, 21));
            $this->fail('Should be unable to create vertices because of duplicate IDs');
        }
        catch (InvalidArgumentException $ignoreExpected) {
            $this->assertEquals(10, count($graph->getVertices()));
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateVerticesContainsInvalid()
    {
        $graph = new Graph();
        $graph->createVertices(array(1, 2, array(), 3));
    }

    public function testCloneInvertedUndirectedIsAlmostOriginal()
    {
        // 1 -- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $edge = $graph->createEdgeUndirected($v1, $v2);

        $edgeInverted = $graph->createEdgeCloneInverted($edge);

        $this->assertInstanceOf(get_class($edge), $edgeInverted);

        $this->assertEquals($edge->getVertices()->getVector(), array_reverse($edgeInverted->getVertices()->getVector()));
    }

    public function testCloneDoubleInvertedDirectedEdgeIsOriginal()
    {
        // 1 -> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $edge = $graph->createEdgeDirected($v1, $v2);

        $edgeInverted = $graph->createEdgeCloneInverted($edge);

        $this->assertInstanceOf(get_class($edge), $edgeInverted);

        $edgeInvertedAgain = $graph->createEdgeCloneInverted($edgeInverted);

        $this->assertEdgeEquals($edge, $edgeInvertedAgain);
    }

    public function testRemoveEdge()
    {
        // 1 -- 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $edge = $graph->createEdgeUndirected($v1, $v2);

        $this->assertEquals(array($edge), $graph->getEdges()->getVector());

        $edge->destroy();
        //$graph->removeEdge($edge);

        $this->assertEquals(array(), $graph->getEdges()->getVector());

        return $graph;
    }

    /**
     * @param Graph $graph
     * @expectedException InvalidArgumentException
     * @depends testRemoveEdge
     */
    public function testRemoveEdgeInvalid(Graph $graph)
    {
        $edge = $graph->createEdgeUndirected($graph->getVertex(1), $graph->getVertex(2));

        $edge->destroy();
        $edge->destroy();
    }

    public function testRemoveVertex()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex(1);

        $this->assertEquals(array(1 => $vertex), $graph->getVertices()->getMap());

        $vertex->destroy();

        $this->assertEquals(array(), $graph->getVertices()->getVector());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRemoveVertexInvalid()
    {
        $graph = new Graph();
        $vertex = $graph->createVertex(1);

        $vertex->destroy();
        $vertex->destroy();
    }

    public function testGetEdgesClones()
    {
        // 1 -> 2 -> 1
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $graph->createEdgeDirected($v1, $v2);
        $e2 = $graph->createEdgeDirected($v2, $v1);

        $graphClone = clone $graph;

        $this->assertEdgeEquals($e1, $graphClone->getEdgeClone($e1));
        $this->assertEdgeEquals($e2, $graphClone->getEdgeCloneInverted($e1));
    }

    /**
     * @expectedException OverflowException
     */
    public function testEdgesFailParallel()
    {
        // 1 -> 2, 1 -> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $graph->createEdgeDirected($v1, $v2);
        $graph->createEdgeDirected($v1, $v2);

        // which one to return? e1? e2?
        $graph->getEdgeClone($e1);
    }

    /**
     * @expectedException UnderflowException
     */
    public function testEdgesFailEdgeless()
    {
        // 1 -> 2
        $graph = new Graph();
        $v1 = $graph->createVertex(1);
        $v2 = $graph->createVertex(2);
        $e1 = $graph->createEdgeDirected($v1, $v2);

        $graphCloneEdgeless = clone $graph;
        foreach ($graphCloneEdgeless->getEdges() as $edge) {
            $edge->destroy();
        }

        // nothing to return
        $graphCloneEdgeless->getEdgeClone($e1);
    }

    protected function createAttributeAware()
    {
        return new Graph();
    }
}

<?php

namespace Graphp\Algorithms\MinimumCostFlow;

use Graphp\Algorithms\DetectNegativeCycle;
use Graphp\Algorithms\MaxFlow\EdmondsKarp as MaxFlowEdmondsKarp;
use Graphp\Algorithms\ResidualGraph;
use Graphp\Graph\Exception\UnderflowException;
use Graphp\Graph\Exception\UnexpectedValueException;
use Graphp\Graph\Set\Edges;

class CycleCanceling extends Base
{
    public function createGraph()
    {
        $this->checkBalance();

        // create resulting graph with supersource and supersink
        $resultGraph = $this->graph->createGraphClone();

        $superSource = $resultGraph->createVertex();
        $superSink   = $resultGraph->createVertex();

        $sumBalance = 0;

        // connect supersource s* and supersink t* with all "normal" sources and sinks
        foreach ($resultGraph->getVertices() as $vertex) {
            $balance = $vertex->getBalance();

            if ($balance > 0) {
                // positive balance => source capacity
                $resultGraph->createEdgeDirected($superSource, $vertex)->setCapacity($balance);

                $sumBalance += $balance;
            } elseif ($balance < 0) {
                // negative balance => sink capacity (positive)
                $resultGraph->createEdgeDirected($vertex, $superSink)->setCapacity(-$balance);
            }
        }

        // calculate (s*, t*)-flow
        $algMaxFlow = new MaxFlowEdmondsKarp($superSource, $superSink);
        $flowMax = $algMaxFlow->getFlowMax();

        if ($flowMax !== $sumBalance) {
            throw new UnexpectedValueException('Network does not support required flow of ' . $sumBalance . ' (maximum possible flow limited to ' . $flowMax . ')');
        }

        $resultGraph = $algMaxFlow->createGraph();

        while (true) {
            // create residual graph
            $algRG = new ResidualGraph($resultGraph);
            $residualGraph = $algRG->createGraph();

            // get negative cycle
            $alg = new DetectNegativeCycle($residualGraph);
            try {
                $clonedEdges = $alg->getCycleNegative()->getEdges();
            } catch (UnderflowException $ignore) {
                // no negative cycle found => end algorithm
                break;
            }

            // calculate maximal possible flow = minimum capacity remaining for all edges
            $newFlow = $clonedEdges->getEdgeOrder(Edges::ORDER_CAPACITY_REMAINING)->getCapacityRemaining();

            // set flow on original graph
            assert($newFlow !== null);
            $this->addFlow($resultGraph, $clonedEdges, $newFlow);
        }

        // destroy temporary supersource and supersink again
        $resultGraph->getVertex($superSink->getId())->destroy();
        $resultGraph->getVertex($superSource->getId())->destroy();

        return $resultGraph;
    }
}

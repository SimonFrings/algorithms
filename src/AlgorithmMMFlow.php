<?php
class AlgorithmMMFlow extends AlgorithmMM {

    public function createGraph() {
        // create resulting graph with supersource and supersink
        $resultGraph = $this->graph->createGraphClone();
         
        // All capacities to 1 (according to algorithm)
        foreach ($resultGraph->getEdges() as $edge){
            $edge->setCapacity(1);
        }

        $maxMatchingValue = $resultGraph->getNumberOfEdges();

        $superSource = $resultGraph->createVertex()->setLayout('label','s*');
        $superSink   = $resultGraph->createVertex()->setLayout('label','t*');
         
        // connect supersource s* to set A and supersink t* to set B
        foreach($resultGraph->getVertices() as $vertex){
            $layout = $vertex->getLayout();
            $belongingSet = $layout['label'];

            $result = strcmp($belongingSet, "\"A\"");
            if(strcmp($belongingSet, "\"A\"") == 0){ // source
                $superSource->createEdgeTo($vertex)->setCapacity($maxMatchingValue);
            } else if(strcmp($belongingSet, "\"B\"") == 0){ // sink
                $vertex->createEdgeTo($superSink)->setCapacity($maxMatchingValue);
            } else if(strcmp($belongingSet, "\"s*\"") == 0 || strcmp($belongingSet, "\"t*\"") == 0){
                // ignore supersource and supertarget
            }
            else {
                throw new Exception('Unknown set: ' + $belongingSet);
            }
        }

        visualize($resultGraph);
         
        // calculate (s*,t*)-flow
        //$algMaxFlow = new AlgorithmMaxFlowEdmondsKarp($superSource,$superSink);
        //$flow = $algMaxFlow->getFlowMax();
    }
}
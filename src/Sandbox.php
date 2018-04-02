<?php

namespace Iamluc\Script;

class Sandbox
{
    private $variables = [];

    public function eval(Node\CallNode $main)
    {
        foreach ($main->getNodes() as $node) {
            $this->process($node);
        }
    }

    public function getVariables()
    {
        return $this->variables;
    }

    private function process(Node\Node $node)
    {
        if ($node instanceof Node\ScalarNode) {
            return $node->getValue();
        }

        if ($node instanceof Node\AssignNode) {
            return $this->processAssignNode($node);
        }

        if ($node instanceof Node\ConditionalNode) {
            return $this->processConditionalNode($node);
        }

        throw new \LogicException(sprintf('Unable to process node of type %s', get_class($node)));
    }

    private function processConditionalNode(Node\ConditionalNode $node)
    {
        $value = $this->evaluateNode($node->getCondition());
        $toProcess = $value ? $node->getIf() : $node->getElse();

        return $this->process($toProcess);
    }

    private function processAssignNode(Node\AssignNode $node)
    {
        return $this->variables[$node->getVariableName()] = $this->process($node->getValue());
    }

    private function evaluateNode(Node\Node $node)
    {
        if ($node instanceof Node\ScalarNode) {
            return $node->getValue();
        }

        if ($node instanceof Node\VariableNode) {
            return $this->variables[$node->getVariable()] ?? null;
        }

        throw new \LogicException(sprintf('Cannot evaluate node of type "%s"', get_class($node)));
    }
}

<?php

namespace Iamluc\Script;

class Parser
{
    public function parse(string $script)
    {
        $stream = new TokenStream(new Lexer($script));

        $nodes = [];
        while (!$stream->nextIsEOF()) {
            $nodes[] = $this->parseStatement($stream);
        }

        return new Node\BlockNode($nodes);
    }

    private function parseStatement(TokenStream $stream): Node\Node
    {
        $token = $stream->peek();

        if ($token->isEOF()) {
            throw new \LogicException('Unexptected end of file.');
        }

        if ($token->isScalar()) {
            return new Node\ScalarNode($token->getScalarValue());
        }

        if ($token->is(Token::T_NAME) && $stream->nextIs(Token::T_ASSIGN)) {
            $stream->peek(); // consume "="

            return $this->parseAssign($stream, $token);
        }

        if ($token->is(Token::T_IF)) {
            return $this->parseIf($stream);
        }

        throw new \LogicException('Unable to parse.');
    }

    private function parseIf(TokenStream $stream, $end = true)
    {
        $condition = $this->parseCondition($stream);

        $token = $stream->peek();
        if (!$token->is(Token::T_THEN)) {
            throw new \LogicException('Expected "then".');
        }

        $if = $this->parseStatement($stream);

        while ($stream->nextIs([Token::T_ELSE, Token::T_ELSEIF])) {
            $token = $stream->peek(); // consume else/elseif

            if ($token->is(Token::T_ELSE)) {
                $else = $this->parseStatement($stream);
            } else {
                $else = $this->parseIf($stream, false);
            }

            $if = new Node\ConditionalNode($condition, $if, $else);
        }

        if ($end) {
            $token = $stream->peek();
            if (!$token->is(Token::T_END)) {
                throw new \LogicException('Expected "end".');
            }
        }

        return $if;
    }

    private function parseCondition(TokenStream $stream): Node\Node
    {
        while ($stream->nextIsScalar() || $stream->nextIsVariable()) {

            // FIXME: we handle only:
            // - scalar
            // - variable
            // - scalar operator scalar

            $left = $stream->peek(); // consume scalar / variable

            if ($stream->nextIs(Token::T_THEN)) {
                return $this->convertToNode($left);
            }

            $operator = $stream->peek();
            if (!$operator->isOperator()) {
                throw new \LogicException('Expected an operator.');
            }

            $right = $stream->peek();
            if (!$right->isScalar() && !$right->isVariable()) {
                throw new \LogicException(sprintf('Expected a scalar or a variable. Got "%s"', $right->getType()));
            }

            return new Node\ComparisonNode($this->convertToNode($left), $operator->getValue(), $this->convertToNode($right));
        }

        throw new \LogicException('Invalid condition.');
    }

    private function parseAssign(TokenStream $stream, Token $variable): Node\AssignNode
    {
        return new Node\AssignNode($variable->getValue(), $this->parseStatement($stream));
    }

    private function convertToNode(Token $token): Node\Node
    {
        if ($token->isScalar()) {
            return new Node\ScalarNode($token->getScalarValue());
        }

        if ($token->isVariable()) {
            return new Node\VariableNode($token->getValue());
        }

        throw new \LogicException(sprintf('Cannot convert token of type "%s" to node.', $token->getType()));
    }
}

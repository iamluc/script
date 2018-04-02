<?php

namespace Iamluc\Script;

class Parser
{
    public function parse(string $script)
    {
        $stream = new Lexer($script);

        $nodes = [];
        while ($token = $stream->next(false)) {
            if (Token::T_EOF === $token->getType()) {
                break;
            }

            $nodes[] = $this->parseExpression($stream);
        }

        return new Node\CallNode('__main', $nodes);
    }

    private function parseExpression(Lexer $stream): Node\Node
    {
        $token = $stream->next();
        $next = $stream->next(false);
//        dump("----", $token, $next);

        if (Token::T_EOF === $token->getType()) {
            throw new \LogicException('Unexptected end of file.');
        }

        if ($token->isScalar()) {
            return new Node\ScalarNode($token->getScalarValue());
        }

        if (Token::T_NAME === $token->getType() && Token::T_ASSIGN === $next->getType()) {
            $stream->next(); // consume "="
            return $this->parseAssign($stream, $token);
        }

        if (Token::T_IF === $token->getType()) {
            return $this->parseIf($stream, $token);
        }

        throw new \LogicException('Unable to parse.');
    }

    private function parseIf(Lexer $stream)
    {
        $condition = $this->parseCondition($stream);

        $token = $stream->next();
        if (Token::T_THEN !== $token->getType()) {
            throw new \LogicException('Expected "then".');
        }

        $if = $this->parseExpression($stream);

        $token = $stream->next();
        if (Token::T_ELSE === $token->getType()) {
            $else = $this->parseExpression($stream);
        } else {
            $else = new Node\NoOperationNode();
        }

        $token = $stream->next();
        if (Token::T_END !== $token->getType()) {
//            dump($token);
            throw new \LogicException('Expected "end".');
        }

        return new Node\ConditionalNode($condition, $if, $else);
    }

    private function parseCondition(Lexer $stream): Node\Node
    {
        $token = $stream->next();

        if ($token->isScalar()) {
            return new Node\ScalarNode($token->getScalarValue());
        }

        if (Token::T_NAME === $token->getType()) {
            return new Node\VariableNode($token->getValue());
        }

        throw new \LogicException('Invalid condition.');
    }

    private function parseAssign(Lexer $stream, Token $variable): Node\AssignNode
    {
        return new Node\AssignNode($variable->getValue(), $this->parseExpression($stream));
    }
}

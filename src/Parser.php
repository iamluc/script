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

            $nodes[] = $this->parseStatement($stream);
        }

        return new Node\CallNode('__main', $nodes);
    }

    private function parseStatement(Lexer $stream): Node\Node
    {
        $token = $stream->next();
        $next = $stream->next(false);

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
            return $this->parseIf($stream);
        }

        throw new \LogicException('Unable to parse.');
    }

    private function parseIf(Lexer $stream, $end = true)
    {
        $condition = $this->parseCondition($stream);

        $token = $stream->next();
        if (Token::T_THEN !== $token->getType()) {
            throw new \LogicException('Expected "then".');
        }

        $if = $this->parseStatement($stream);

        while (($token = $stream->next(false)) && $token->is([Token::T_ELSE, Token::T_ELSEIF])) {
            $stream->next();

            if (Token::T_ELSE === $token->getType()) {
                $else = $this->parseStatement($stream);
            } elseif (Token::T_ELSEIF === $token->getType()) {
                $else = $this->parseIf($stream, false);
            } else {
                $else = new Node\NoOperationNode();
            }

            $if = new Node\ConditionalNode($condition, $if, $else);
        }

        if ($end) {
            $token = $stream->next();
            if (Token::T_END !== $token->getType()) {
                dump($token);
                throw new \LogicException('Expected "end".');
            }
        }

        return $if;
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
        return new Node\AssignNode($variable->getValue(), $this->parseStatement($stream));
    }
}

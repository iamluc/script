<?php

namespace Iamluc\Script;

class Parser
{
    public function parse(string $script)
    {
        $stream = new TokenStream(new Lexer($script));

        $nodes = [];
        while ($token = $stream->next(false)) {
            if ($token->isEOF()) {
                break;
            }

            $nodes[] = $this->parseStatement($stream);
        }

        return new Node\CallNode('__main', $nodes);
    }

    private function parseStatement(TokenStream $stream): Node\Node
    {
        $token = $stream->next();
        $next = $stream->next(false);

        if ($token->isEOF()) {
            throw new \LogicException('Unexptected end of file.');
        }

        if ($token->isScalar()) {
            return new Node\ScalarNode($token->getScalarValue());
        }

        if ($token->is(Token::T_NAME) && $next->is(Token::T_ASSIGN)) {
            $stream->next(); // consume "="
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

        $token = $stream->next();
        if (!$token->is(Token::T_THEN)) {
            throw new \LogicException('Expected "then".');
        }

        $if = $this->parseStatement($stream);

        while (($token = $stream->next(false)) && $token->is([Token::T_ELSE, Token::T_ELSEIF])) {
            $stream->next(); // consume else/elseif

            if ($token->is(Token::T_ELSE)) {
                $else = $this->parseStatement($stream);
            } else {
                $else = $this->parseIf($stream, false);
            }

            $if = new Node\ConditionalNode($condition, $if, $else);
        }

        if ($end) {
            $token = $stream->next();
            if (!$token->is(Token::T_END)) {
                dump($token);
                throw new \LogicException('Expected "end".');
            }
        }

        return $if;
    }

    private function parseCondition(TokenStream $stream): Node\Node
    {
        $token = $stream->next();

        if ($token->isScalar()) {
            return new Node\ScalarNode($token->getScalarValue());
        }

        if ($token->is(Token::T_NAME)) {
            return new Node\VariableNode($token->getValue());
        }

        throw new \LogicException('Invalid condition.');
    }

    private function parseAssign(TokenStream $stream, Token $variable): Node\AssignNode
    {
        return new Node\AssignNode($variable->getValue(), $this->parseStatement($stream));
    }
}

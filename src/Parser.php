<?php

namespace Iamluc\Script;

use Iamluc\Script\Node\BlockNode;
use Iamluc\Script\Node\NoOperationNode;
use Iamluc\Script\Node\ScalarNode;

class Parser
{
    /**
     * @see https://www.lua.org/pil/3.5.html
     */
    private $precedenceMap = [
        Token::T_AND => 10,
        Token::T_OR => 10,

        Token::T_EQUAL => 20,
        Token::T_NOT_EQUAL => 20,
        Token::T_LESS_THAN => 20,
        Token::T_GREATER_THAN => 20,
        Token::T_LESS_EQUAL => 20,
        Token::T_GREATER_EQUAL => 20,

        Token::T_DOUBLE_DOT => 30,

        Token::T_PLUS => 40,
        Token::T_MINUS => 40,

        Token::T_STAR => 50,
        Token::T_SLASH => 50,

        // TODO: not / - (unary) => 60
        // TODO: ^ => 70
    ];

    /** @var TokenStream */
    private $stream;

    public function parse(string $script)
    {
        $lexer = new Lexer($script);
        $this->stream = new TokenStream($lexer);

        return $this->parseBlock( Token::T_EOF);
    }

    private function parseBlock($end): Node\BlockNode
    {
        $labels = [];
        $nodes = [];
        while (!$this->stream->nextIs($end)) {
            $node = $this->parseStatement();
            $nodes[] = $node;

            if ($node instanceof Node\LabelNode) {
                $labels[] = $node->getName();
            }
        }

        return new Node\BlockNode($nodes, $labels);
    }

    private function parseStatement(): Node\Node
    {
        $token = $this->stream->peek();

        if ($token->is(Token::T_FUNCTION)) {
            return $this->parseNamedFunction();
        }

        if ($token->is(Token::T_IF)) {
            return $this->parseIf();
        }

        if ($token->is(Token::T_WHILE)) {
            return $this->parseWhile();
        }

        if ($token->is(Token::T_REPEAT)) {
            return $this->parseRepeat();
        }

        if ($token->is(Token::T_FOR)) {
            return $this->parseFor();
        }

        if ($token->is(Token::T_DO)) {
            return $this->parseDo();
        }

        if ($token->is(Token::T_RETURN)) {
            return $this->parseReturn();
        }

        if ($token->is(Token::T_BREAK)) {
            return $this->parseBreak();
        }

        if ($token->is(Token::T_LABEL)) {
            return $this->parseLabel($token);
        }

        if ($token->is(Token::T_GOTO)) {
            return $this->parseGoto();
        }

        // Local
        if ($token->is(Token::T_LOCAL)) {
            return $this->parseLocal();
        }

        // Function call
        if ($token->isVariable() && $this->stream->nextIs(Token::T_LEFT_PAREN)) {
            return $this->parseCall($token);
        }

        // Regular assignment (not local)
        if ($token->isVariable() && $this->stream->nextIs([Token::T_ASSIGN, Token::T_COMMA])) {
            return $this->parseAssign($token);
        }

        throw new \LogicException(sprintf('Invalid statement stating with "%s"', $token->getType()));
    }

    private function parseExpression($precedence = 0)
    {
        $token = $this->stream->peek();

        switch ($token->getType()) {
            case Token::T_LEFT_PAREN:
                $left = $this->parseExpression();
                $this->stream->expect((Token::T_RIGHT_PAREN));
                break;

            case Token::T_MINUS: // negative
                $left = new Node\UnaryNode($this->parseExpression(100), '-');
                break;

            case Token::T_PLUS: // positive, ignore
                $token = $this->stream->peek();
                // no break

            default:
                if (!$token->isScalar() && !$token->isVariable()) {
                    throw new \LogicException(sprintf('Expected a scalar, a variable or a function call as start of the expression. Got "%s"', $token->getType()));
                }

                // Function call
                if ($token->isVariable() && $this->stream->nextIs(Token::T_LEFT_PAREN)) {
                    $left = $this->parseCall($token);
                } else {
                    $left = $this->convertToNode($token);
                }
        }

        while (($next = $this->stream->next())
            && isset($this->precedenceMap[$next->getType()])
            && ($this->precedenceMap[$next->getType()] > $precedence || 0 === $precedence)
        ) {
            $operation = $this->stream->peek();
            $right = $this->parseExpression($this->precedenceMap[$operation->getType()]);

            $left = new Node\BinaryNode($left, $operation->getValue(), $right);
        }

        return $left;
    }

    private function parseWhile()
    {
        $condition = $this->parseExpression();
        $this->stream->expect(Token::T_DO);

        $body = $this->parseBlock(Token::T_END);
        $this->stream->expect(Token::T_END);

        return new Node\WhileNode($condition, $body);
    }

    private function parseRepeat()
    {
        $body = $this->parseBlock(Token::T_UNTIL);
        $this->stream->expect(Token::T_UNTIL);

        $condition = $this->parseExpression();

        return new Node\RepeatNode($condition, $body);
    }

    private function parseFor()
    {
        $var = $this->stream->expect(Token::T_NAME);
        $type = $this->stream->expect([Token::T_ASSIGN, Token::T_IN, Token::T_COMMA]);

        if ($type->is(Token::T_ASSIGN)) {
            $intial = $this->parseExpression();
            $this->stream->expect(Token::T_COMMA);
            $limit = $this->parseExpression();

            $step = null;
            if ($this->stream->nextIs(Token::T_COMMA)) {
                $this->stream->expect(Token::T_COMMA);
                $step = $this->parseExpression();
            }
        } else {
            // FIXME: support "key, value" format

            $expr = $this->parseExpression();
        }

        $this->stream->expect(Token::T_DO);

        $body = $this->parseBlock(Token::T_END);
        $this->stream->expect(Token::T_END);

        if ($type->is(Token::T_ASSIGN)) {
            return new Node\ForNode($var->getValue(), $body, $intial, $limit, $step ?: new Node\ScalarNode(1));
        }

        return new Node\ForeachNode($var->getValue(), $expr);
    }

    private function parseDo()
    {
        $body = $this->parseBlock(Token::T_END);
        $this->stream->expect(Token::T_END);

        return $body;
    }

    private function parseIf($end = true)
    {
        $condition = $this->parseExpression();
        $this->stream->expect(Token::T_THEN);

        $if = $this->parseBlock([Token::T_ELSE, Token::T_ELSEIF, Token::T_END]);

        while ($this->stream->nextIs([Token::T_ELSE, Token::T_ELSEIF])) {
            $token = $this->stream->expect([Token::T_ELSE, Token::T_ELSEIF]); // consume else/elseif

            if ($token->is(Token::T_ELSE)) {
                $else = $this->parseBlock(Token::T_END);
            } else {
                $else = $this->parseIf(false);
            }

            $if = new Node\ConditionalNode($condition, $if, $else);
        }

        // if alone
        if ($if instanceof BlockNode) {
            $if = new Node\ConditionalNode($condition, $if, new NoOperationNode());
        }

        if ($end) {
            $this->stream->expect(Token::T_END);
        }

        return $if;
    }

    private function parseCall(Token $name): Node\CallNode
    {
        $this->stream->expect(Token::T_LEFT_PAREN);
        $args = [];
        while (!$this->stream->nextIs(Token::T_RIGHT_PAREN)) {
            $args[] = $this->parseExpression();

            $next = $this->stream->next();
            if ($next->is(Token::T_RIGHT_PAREN)) {
                break;
            }
            $this->stream->expect(Token::T_COMMA);
        }
        $this->stream->expect(Token::T_RIGHT_PAREN);

        return new Node\CallNode($name->getValue(), $args);
    }

    private function parseFunction(): Node\FunctionNode
    {
        $this->stream->expect(Token::T_LEFT_PAREN);
        $args = [];
        while (!$this->stream->nextIs(Token::T_RIGHT_PAREN)) {
            $args[] = $this->stream->expect(Token::T_NAME)->getValue();

            $next = $this->stream->next();
            if ($next->is(Token::T_RIGHT_PAREN)) {
                break;
            }
            $this->stream->expect(Token::T_COMMA);
        }
        $this->stream->expect(Token::T_RIGHT_PAREN);

        $block = $this->parseBlock(Token::T_END);
        $this->stream->expect(Token::T_END);

        return new Node\FunctionNode($args, $block);
    }

    private function parseNamedFunction(): Node\AssignNode
    {
        $name = $this->stream->expect(Token::T_NAME);

        return new Node\AssignNode([$name->getValue() => $this->parseFunction()], false);
    }

    private function parseReturn(): Node\ReturnNode
    {
        return new Node\ReturnNode($this->parseExpression());
    }

    private function parseBreak(): Node\BreakNode
    {
        return new Node\BreakNode();
    }

    private function parseLabel(Token $label): Node\LabelNode
    {
        return new Node\LabelNode($label->getValue());
    }

    private function parseGoto(): Node\GotoNode
    {
        $target = $this->stream->expect(Token::T_NAME);

        return new Node\GotoNode($target->getValue());
    }

    private function parseAssign(Token $first, $local = false): Node\AssignNode
    {
        $vars = [$first->getValue()];
        while ($this->stream->nextIs(Token::T_COMMA)) {
            $this->stream->expect(Token::T_COMMA);
            $vars[] = $this->stream->expect(Token::T_NAME)->getValue();
        }

        $this->stream->expect(Token::T_ASSIGN);

        $values = [$this->readAssignValue()];
        while ($this->stream->nextIs(Token::T_COMMA)) {
            $this->stream->expect(Token::T_COMMA);
            $values[] = $this->readAssignValue();
        }

        if (\count($values) > \count($vars)) {
            $values = \array_slice($values, 0, \count($vars));
        } elseif (\count($values) < \count($vars)) {
            $values = array_pad($values, \count($vars), new ScalarNode(null));
        }

        return new Node\AssignNode(array_combine($vars, $values), $local);
    }

    private function readAssignValue()
    {
        if ($this->stream->nextIs(Token::T_FUNCTION)) {
            $this->stream->expect(Token::T_FUNCTION); // Consume function

            return $this->parseFunction();
        }

        return $this->parseExpression();
    }

    private function parseLocal()
    {
        $type = $this->stream->expect([Token::T_NAME, Token::T_FUNCTION]);

        if ($type->is(Token::T_NAME)) {
            return $this->parseAssign($type, true);
        }

        $name = $this->stream->expect(Token::T_NAME);

        return new Node\AssignNode([$name->getValue() => $this->parseFunction()], true);
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

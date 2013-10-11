<?php

namespace Handlebars;

class Parser {
    /**
     * Process array of tokens and convert them into parse tree
     *
     * @param array $tokens Set of
     *
     * @return array Token parse tree
     */
    public function parse(array $tokens = array())
    {
        return $this->_buildTree(new \ArrayIterator($tokens));
    }

    /**
     * Helper method for recursively building a parse tree.
     *
     * @param ArrayIterator $tokens Stream of  tokens
     *
     * @return array Token parse tree
     *
     * @throws LogicException when nesting errors or mismatched section tags are encountered.
     */
    private function _buildTree(\ArrayIterator $tokens)
    {
        $stack = array();

        do {
            $token = $tokens->current();
            $tokens->next();

            if ($token === null) {
                continue;
            } else {
                switch ($token[Lexer::TYPE]) {
                case Lexer::T_END_SECTION:
                    $newNodes = array ();
                    $continue = true;
                    do {
                        $result = array_pop($stack);
                        if ($result === null) {
                            throw new LogicException('Unexpected closing tag: /'. $token[Lexer::NAME]);
                        }

                        if (!array_key_exists(Lexer::NODES, $result)
                            && isset($result[Lexer::NAME])
                            && $result[Lexer::NAME] == $token[Lexer::NAME]
                        ) {
                            $result[Lexer::NODES] = $newNodes;
                            $result[Lexer::END]   = $token[Lexer::INDEX];
                            array_push($stack, $result);
                            break 2;
                        } else {
                            array_unshift($newNodes, $result);
                        }
                    } while (true);
                    break;
                default:
                    array_push($stack, $token);
                }
            }

        } while ($tokens->valid());

        return $stack;

    }
}
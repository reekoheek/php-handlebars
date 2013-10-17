<?php

namespace Handlebars;

class Compiler {
    /**
     * @var Engine
     */
    protected $handlebars;


    protected $tree = array();

    protected $source = '';

    /**
     * @var array Run stack
     */
    private $_stack = array();

    /**
     * Handlebars template constructor
     *
     * @param Engine $engine handlebar engine
     * @param array             $tree   Parsed tree
     * @param string            $source Handlebars source
     */
    public function __construct(Engine $engine, $tree, $source)
    {
        $this->handlebars = $engine;
        $this->tree = $tree;
        $this->source = $source;
        array_push($this->_stack, array (0, $this->getTree(), false));
    }

    /**
     * Get current tree
     *
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Get current source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get current engine associated with this object
     *
     * @return Engine
     */
    public function getEngine()
    {
        return $this->handlebars;
    }

    /**
     * set stop token for render and discard method
     *
     * @param string $token token to set as stop token or false to remove
     *
     * @return void
     */

    public function setStopToken($token)
    {
        $topStack = array_pop($this->_stack);
        $topStack[2] = $token;
        array_push($this->_stack, $topStack);
    }

    /**
     * get current stop token
     *
     * @return string|false
     */

    public function getStopToken()
    {
        $topStack = end($this->_stack);
        return $topStack[2];
    }
    /**
     * Render top tree
     *
     * @param mixed $context current context
     *
     * @return string
     */
    public function compile($context = array())
    {
        if (!$context instanceof Context) {
            $context = new Context($context);
        }
        $topTree = end($this->_stack); //This method (render) never pop a value from stack
        list($index ,$tree, $stop) = $topTree;

        $buffer = '';
        while (array_key_exists($index, $tree)) {
            $current = $tree[$index];
            $index++;
            //if the section is exactly like waitFor
            if (is_string($stop)
                && $current[Lexer::TYPE] == Lexer::T_ESCAPED
                && $current[Lexer::NAME] === $stop
            ) {
                break;
            }

            switch ($current[Lexer::TYPE]) {
            case Lexer::T_SECTION :
                $newStack = isset($current[Lexer::NODES]) ? $current[Lexer::NODES] : array();
                array_push($this->_stack, array(0, $newStack, false));
                $buffer .= $this->_section($context, $current);
                array_pop($this->_stack);
                break;
            case Lexer::T_INVERTED :
                $newStack = isset($current[Lexer::NODES]) ? $current[Lexer::NODES] : array();
                array_push($this->_stack, array(0, $newStack, false));
                $buffer .= $this->_inverted($context, $current);
                array_pop($this->_stack);
                break;
            case Lexer::T_COMMENT :
                $buffer .= '';
                break;
            case Lexer::T_PARTIAL:
            case Lexer::T_PARTIAL_2:
                $buffer .= $this->_partial($context, $current);
                break;
            case Lexer::T_UNESCAPED:
            case Lexer::T_UNESCAPED_2:
                $buffer .= $this->_variables($context, $current, false);
                break;
            case Lexer::T_ESCAPED:
                $buffer .= $this->_variables($context, $current, true);
                break;
            case Lexer::T_TEXT:
                $buffer .= $current[Lexer::VALUE];
                break;
            default:
                throw new RuntimeException('Invalid node type : ' . json_encode($current));
            }
        }
        if ($stop) {
            //Ok break here, the helper should be aware of this.
            $newStack = array_pop($this->_stack);
            $newStack[0] = $index;
            $newStack[2] = false; //No stop token from now on
            array_push($this->_stack, $newStack);
        }
        return $buffer;
    }

    /**
     * Discard top tree
     *
     * @param mixed $context current context
     *
     * @return string
     */
    public function discard($context)
    {
        if (!$context instanceof Context) {
            $context = new Context($context);
        }
        $topTree = end($this->_stack); //This method never pop a value from stack
        list($index ,$tree, $stop) = $topTree;
        while (array_key_exists($index, $tree)) {
            $current = $tree[$index];
            $index++;
            //if the section is exactly like waitFor
            if (is_string($stop)
                && $current[Lexer::TYPE] == Lexer::T_ESCAPED
                && $current[Lexer::NAME] === $stop
            ) {
                break;
            }
        }
        if ($stop) {
            //Ok break here, the helper should be aware of this.
            $newStack = array_pop($this->_stack);
            $newStack[0] = $index;
            $newStack[2] = false;
            array_push($this->_stack, $newStack);
        }
        return '';
    }

    /**
     * Process section nodes
     *
     * @param Context $context current context
     * @param array              $current section node data
     *
     * @return string the result
     */
    private function _section(Context $context, $current)
    {
        $helpers = $this->handlebars->getHelpers();
        $sectionName = $current[Lexer::NAME];

        if ($helpers->has($sectionName)) {
            if (isset($current[Lexer::END])) {
                $source = substr(
                    $this->getSource(),
                    $current[Lexer::INDEX],
                    $current[Lexer::END] - $current[Lexer::INDEX]
                );
            } else {
                $source = '';
            }
            $params = array(
                $this,  //First argument is this template
                $context, //Second is current context
                $current[Lexer::ARGS],  //Arguments
                $source
                );
            $return = call_user_func_array($helpers->$sectionName, $params);
            // if ($return instanceof String) {
            //     return $this->handlebars->loadString($return)->render($context);
            // } else {
                return $return;
            // }
        } elseif (trim($current[Lexer::ARGS]) == '') {
            //Fallback for mustache style each/with/for just if there is no argument at all.
            try {
                $sectionVar = $context->get($sectionName, true);
            } catch (InvalidArgumentException $e) {
                throw new \RuntimeException($sectionName . ' is not registered as a helper');
            }
            $buffer = '';
            if (is_array($sectionVar) || $sectionVar instanceof Traversable) {
                foreach ($sectionVar as $d) {
                    $context->push($d);
                    $buffer .= $this->render($context);
                    $context->pop();
                }
            } elseif (is_object($sectionVar)) {
                //Act like with
                $context->push($sectionVar);
                $buffer = $this->render($context);
                $context->pop();
            } elseif ($sectionVar) {
                $buffer = $this->render($context);
            }
            return $buffer;
        } else {
            throw new \RuntimeException($sectionName . ' is not registered as a helper');
        }
    }

    /**
     * Process inverted section
     *
     * @param Context $context current context
     * @param array              $current section node data
     *
     * @return string the result
     */
    private function _inverted(Context $context, $current)
    {
        $sectionName = $current[Lexer::NAME];
        $data = $context->get($sectionName);
        if (!$data) {
            return $this->render($context);
        } else {
            //No need to disacard here, since itshas no else
            return '';
        }
    }

    /**
     * Process partial section
     *
     * @param Context $context current context
     * @param array              $current section node data
     *
     * @return string the result
     */
    private function _partial($context, $current)
    {
        $partial = $this->handlebars->loadPartial($current[Lexer::NAME]);

        if ( $current[Lexer::ARGS] ) {
            $context = $context->get($current[Lexer::ARGS]);
        }

        return $partial->render($context);
    }

    /**
     * Process partial section
     *
     * @param Context $context current context
     * @param array              $current section node data
     * @param boolean            $escaped escape result or not
     *
     * @return string the result
     */
    private function _variables($context, $current, $escaped) {

        $helpers = $this->handlebars->getHelpers();

        if (empty($current[Lexer::ARGS])) {
            $name = (is_array($current)) ? $current[Lexer::NAME] : $current;
            $value = $context->get($name);
            if ($escaped) {
                $args = $this->handlebars->getEscapeArgs();
                array_unshift($args, $value);
                $value = call_user_func_array($this->handlebars->getEscape(), array_values($args));
            }
            return $value;
        } elseif ($helpers->has($current[Lexer::NAME])) {
            $fn = $helpers->get($current[Lexer::NAME]);

            $preparedArgs = array();
            $args = explode(' ', $current[Lexer::ARGS]);
            foreach ($args as $arg) {
                if ($arg === '') continue;
                $preparedArgs[] = $this->_variables($context, $arg, false);
            }
            return $fn->call($preparedArgs);
        }

    }
}
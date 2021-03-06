<?php

namespace Handlebars;

class Lexer {

    // Finite state machine states
    const IN_TEXT     = 0;
    const IN_TAG_TYPE = 1;
    const IN_TAG      = 2;

    // Token types
    const T_SECTION      = '#';
    const T_INVERTED     = '^';
    const T_END_SECTION  = '/';
    const T_COMMENT      = '!';
    const T_PARTIAL      = '>'; //Maybe remove this partials and replace them with helpers
    const T_PARTIAL_2    = '<';
    const T_DELIM_CHANGE = '=';
    const T_ESCAPED      = '_v';
    const T_UNESCAPED    = '{';
    const T_UNESCAPED_2  = '&';
    const T_TEXT         = '_t';

    // Valid token types
    private static $_tagTypes = array(
        self::T_SECTION      => true,
        self::T_INVERTED     => true,
        self::T_END_SECTION  => true,
        self::T_COMMENT      => true,
        self::T_PARTIAL      => true,
        self::T_PARTIAL_2    => true,
        self::T_DELIM_CHANGE => true,
        self::T_ESCAPED      => true,
        self::T_UNESCAPED    => true,
        self::T_UNESCAPED_2  => true,
    );

    // Interpolated tags
    private static $_interpolatedTags = array(
        self::T_ESCAPED      => true,
        self::T_UNESCAPED    => true,
        self::T_UNESCAPED_2  => true,
    );

    // Token properties
    const TYPE   = 'type';
    const NAME   = 'name';
    const OTAG   = 'otag';
    const CTAG   = 'ctag';
    const INDEX  = 'index';
    const END    = 'end';
    const INDENT = 'indent';
    const NODES  = 'nodes';
    const VALUE  = 'value';
    const ARGS   = 'args';

    protected $state;
    protected $tagType;
    protected $tag;
    protected $buffer;
    protected $tokens;
    protected $seenTag;
    protected $lineStart;
    protected $otag;
    protected $ctag;

    /**
     * Scan and tokenize template source.
     *
     * @param string $text       Mustache template source to tokenize
     * @param string $delimiters Optionally, pass initial opening and closing delimiters (default: null)
     *
     * @return array Set of Mustache tokens
     */
    public function tokenize($text, $delimiters = null) {
        // if ($text instanceof String) {
        //     $text = $text->getString();
        // }
        $this->reset();

        if ($delimiters = trim($delimiters)) {
            list($otag, $ctag) = explode(' ', $delimiters);
            $this->otag = $otag;
            $this->ctag = $ctag;
        }

        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            switch ($this->state) {
            case self::IN_TEXT:
                if ($this->tagChange($this->otag, $text, $i)) {
                    $i--;
                    $this->flushBuffer();
                    $this->state = self::IN_TAG_TYPE;
                } else {
                    if ($text[$i] == "\n") {
                        $this->filterLine();
                    } else {
                        $this->buffer .= $text[$i];
                    }
                }
                break;

            case self::IN_TAG_TYPE:

                $i += strlen($this->otag) - 1;
                if (isset(self::$_tagTypes[$text[$i + 1]])) {
                    $tag = $text[$i + 1];
                    $this->tagType = $tag;
                } else {
                    $tag = null;
                    $this->tagType = self::T_ESCAPED;
                }

                if ($this->tagType === self::T_DELIM_CHANGE) {
                    $i = $this->changeDelimiters($text, $i);
                    $this->state = self::IN_TEXT;
                } else {
                    if ($tag !== null) {
                        $i++;
                    }
                    $this->state = self::IN_TAG;
                }
                $this->seenTag = $i;
                break;

            default:
                if ($this->tagChange($this->ctag, $text, $i)) {
                    // Sections (Helpers) can accept parameters
                    // Same thing for Partials (little known fact)
                    if ($this->tagType != self::T_COMMENT) {
                    // if (
                    //     ($this->tagType == self::T_SECTION)
                    //     || ($this->tagType == self::T_PARTIAL)
                    //     || ($this->tagType == self::T_PARTIAL_2)
                    // ) {
                        $newBuffer = explode(' ', trim($this->buffer), 2);
                        $args = '';
                        if (count($newBuffer) == 2) {
                            $args = $newBuffer[1];
                        }
                        $this->buffer = $newBuffer[0];
                    // }
                    }
                    $t = array(
                        self::TYPE  => $this->tagType,
                        self::NAME  => trim($this->buffer),
                        self::OTAG  => $this->otag,
                        self::CTAG  => $this->ctag,
                        self::INDEX => ($this->tagType == self::T_END_SECTION) ? $this->seenTag - strlen($this->otag) : $i + strlen($this->ctag),
                        );
                    if (isset($args)) {
                        $t[self::ARGS] = $args;
                    }
                    $this->tokens[] = $t;
                    unset($t);
                    unset($args);
                    $this->buffer = '';
                    $i += strlen($this->ctag) - 1;
                    $this->state = self::IN_TEXT;
                    if ($this->tagType == self::T_UNESCAPED) {
                        if ($this->ctag == '}}') {
                            $i++;
                        } else {
                            // Clean up `{{{ tripleStache }}}` style tokens.
                            $lastName = $this->tokens[count($this->tokens) - 1][self::NAME];
                            if (substr($lastName, -1) === '}') {
                                $this->tokens[count($this->tokens) - 1][self::NAME] = trim(substr($lastName, 0, -1));
                            }
                        }
                    }
                } else {
                    $this->buffer .= $text[$i];
                }
                break;
            }
        }

        $this->filterLine(true);
        // var_dump($this->tokens);
        return $this->tokens;
    }

    /**
     * Helper function to reset tokenizer internal state.
     *
     * @return void
     */
    protected function reset()
    {
        $this->state     = self::IN_TEXT;
        $this->tagType   = null;
        $this->tag       = null;
        $this->buffer    = '';
        $this->tokens    = array();
        $this->seenTag   = false;
        $this->lineStart = 0;
        $this->otag      = '{{';
        $this->ctag      = '}}';
    }

    /**
     * Flush the current buffer to a token.
     *
     * @return void
     */
    protected function flushBuffer()
    {
        if (!empty($this->buffer)) {
            $this->tokens[] = array(self::TYPE  => self::T_TEXT, self::VALUE => $this->buffer);
            $this->buffer   = '';
        }
    }

    /**
     * Test whether the current line is entirely made up of whitespace.
     *
     * @return boolean True if the current line is all whitespace
     */
    protected function lineIsWhitespace()
    {
        $tokensCount = count($this->tokens);
        for ($j = $this->lineStart; $j < $tokensCount; $j++) {
            $token = $this->tokens[$j];
            if (isset(self::$_tagTypes[$token[self::TYPE]])) {
                if (isset(self::$_interpolatedTags[$token[self::TYPE]])) {
                    return false;
                }
            } elseif ($token[self::TYPE] == self::T_TEXT) {
                if (preg_match('/\S/', $token[self::VALUE])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Filter out whitespace-only lines and store indent levels for partials.
     *
     * @param bool $noNewLine Suppress the newline? (default: false)
     *
     * @return void
     */
    protected function filterLine($noNewLine = false)
    {
        $this->flushBuffer();
        if ($this->seenTag && $this->lineIsWhitespace()) {
            $tokensCount = count($this->tokens);
            for ($j = $this->lineStart; $j < $tokensCount; $j++) {
                if ($this->tokens[$j][self::TYPE] == self::T_TEXT) {
                    if (isset($this->tokens[$j + 1]) && $this->tokens[$j + 1][self::TYPE] == self::T_PARTIAL) {
                        $this->tokens[$j + 1][self::INDENT] = $this->tokens[$j][self::VALUE];
                    }

                    $this->tokens[$j] = null;
                }
            }
        } elseif (!$noNewLine) {
            $this->tokens[] = array(self::TYPE => self::T_TEXT, self::VALUE => "\n");
        }

        $this->seenTag   = false;
        $this->lineStart = count($this->tokens);
    }

    /**
     * Change the current Mustache delimiters. Set new `otag` and `ctag` values.
     *
     * @param string $text  Mustache template source
     * @param int    $index Current tokenizer index
     *
     * @return int New index value
     */
    protected function changeDelimiters($text, $index)
    {
        $startIndex = strpos($text, '=', $index) + 1;
        $close      = '='.$this->ctag;
        $closeIndex = strpos($text, $close, $index);

        list($otag, $ctag) = explode(' ', trim(substr($text, $startIndex, $closeIndex - $startIndex)));
        $this->otag = $otag;
        $this->ctag = $ctag;

        return $closeIndex + strlen($close) - 1;
    }

    /**
     * Test whether it's time to change tags.
     *
     * @param string $tag   Current tag name
     * @param string $text  Mustache template source
     * @param int    $index Current tokenizer index
     *
     * @return boolean True if this is a closing section tag
     */
    protected function tagChange($tag, $text, $index)
    {
        return substr($text, $index, strlen($tag)) === $tag;
    }

}
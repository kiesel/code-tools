<?php namespace net\xp_forge\token;

class TokenSequenceIterator extends \lang\Object implements \util\XPIterator {
  private $sequence= null;
  private $pos= 0;

  public function __construct(TokenSequence $sequence) {
    $this->sequence= $sequence;
  }

  public function hasNext() {
    return $this->pos < $this->sequence->length();
  }

  public function next() {
    return $this->sequence->at($this->pos++);
  }
}
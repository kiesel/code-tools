<?php namespace net\xp_forge\token;

class TokenSequence extends \lang\Object {
  private $tokens;


  public function __construct() {
    $this->tokens= array();
  }

  public static function fromString($code) {
    $seq= new self();
    foreach (token_get_all($code) as $index => $token) {
      $seq->append(new Token($token));
    }

    return $seq;
  }

  public function append(Token $token) {
    $this->tokens[]= $token;
  }

  public function at($pos) {
    if (!isset($this->tokens[$pos])) {
      throw new \lang\ElementNotFoundException('Sequence contains no element at position '.$pos);
    }
    return $this->tokens[$pos];
  }

  public function length() {
    return sizeof($this->tokens);
  }

  public function iterator() {
    return new TokenSequenceIterator($this);
  }

  public function equals($cmp) {
    if (!$cmp instanceof self) return false;
    if (!$cmp->length() == $this->length()) return false;

    for ($i= 0; $i < $this->length(); $i++) {
      if (!$this->at($i)->equals($cmp->at($i))) return false;
    }

    return true;
  }
}
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
}
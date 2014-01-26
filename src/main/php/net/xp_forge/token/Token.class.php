<?php namespace net\xp_forge\token;

class Token extends \lang\Object {
  private $type     = null;
  private $literal  = null;
  private $line     = -1;

  public function __construct($t) {
    if (is_string($t)) {
      $this->type= T_STRING;
      $this->literal= $t;
      return;
    } else if (is_array($t)) {
      $this->type= $t[0];
      $this->literal= $t[1];
      $this->line= $t[2];
    } else {
      throw new \lang\IllegalArgumentException('Invalid token input.');
    }
  }

  public function type() {
    return $this->type;
  }

  public function literal() {
    return $this->literal;
  }

  public function line() {
    return $this->line;
  }

  public function is($type) {
    if (is_string($type)) {
      return $this->is(T_STRING) && $type === $this->literal();
    }

    return $type == $this->type();
  }

  public function isStatementSeparator() {
    return
      $this->is(T_WHITESPACE) ||
      $this->is(T_DOUBLE_COLON) ||
      ($this->is(T_STRING) && (in_array($this->literal(), [',', '(', ')', ';'])))
    ;
  }

  public function toString() {
    $s= $this->getClassName().' {'.token_name($this->type()).' ['.$this->type().'], line '.$this->line().' - '.\xp::stringOf($this->literal())."}\n";
    return $s;
  }

  public function equals($cmp) {
    return 
      $cmp instanceof self &&
      $this->type() == $cmp->type() &&
      $this->literal() == $cmp->literal()
    ;
  }
}
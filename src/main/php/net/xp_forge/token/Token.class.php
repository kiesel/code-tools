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
    return $type == $this->type();
  }
}
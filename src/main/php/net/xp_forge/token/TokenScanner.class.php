<?php namespace net\xp_forge\token;

class TokenScanner extends \lang\Object {
  private $callbacks= [];
  private $exit= [];
  private $iterator= null;

  public function __construct(\util\XPIterator $iterator) {
    $this->iterator= $iterator;
  }

  public function when($case, \Closure $callback) {
    foreach ((array)$case as $c) {
      $this->callbacks[$c]= $callback;
    }

    return $this;
  }

  public function quitOn($case) {
    foreach ((array)$case as $c) {
      $this->exit[$c]= true;
    }

    return $this;
  }

  public function run() {
    while ($this->iterator->hasNext()) {
      $token= $this->iterator->next();

      if (isset($this->exit[$token->type()])) {
        return;
      }

      if (isset($this->callbacks[$token->type()])) {
        $cb= $this->callbacks[$token->type()];
        $cb($token, $this->iterator);
      }
    }
  }
}
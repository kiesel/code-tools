<?php namespace net\xp_forge\token;

class TokenTest extends \unittest\TestCase {

  #[@test, @expect('lang.IllegalArgumentException')]
  public function create() {
    new Token(null);
  }

  #[@test]
  public function createByString() {
    new Token('Hello');
  }

  #[@test]
  public function createByArray() {
    new Token([T_NAMESPACE, "namespace", 1]);
  }
}
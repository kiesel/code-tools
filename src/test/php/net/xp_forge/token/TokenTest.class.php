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

  #[@test]
  public function tokenEquals() {
    $a= new Token([1, "foo", 1]);
    $b= new Token([1, "foo", 1]);

    $this->assertTrue($a->equals($b));
  }

  #[@test]
  public function token_not_equals_on_type() {
    $a= new Token([2, "foo", 1]);
    $b= new Token([1, "foo", 1]);

    $this->assertFalse($a->equals($b));
  }

  #[@test]
  public function token_not_equals_on_literal() {
    $a= new Token([1, "", 1]);
    $b= new Token([1, "foo", 1]);

    $this->assertFalse($a->equals($b));
  }

  #[@test]
  public function token_not_equals_on_line() {
    $a= new Token([1, "foo", 1]);
    $b= new Token([1, "foo", 2]);

    $this->assertFalse($a->equals($b));
  }
}
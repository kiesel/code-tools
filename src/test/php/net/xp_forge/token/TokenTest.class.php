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
  public function is_with_same_type_returns_true() {
    $this->assertTrue(create(new Token([T_NAMESPACE, "", 0]))->is(T_NAMESPACE));
  }

  #[@test]
  public function is_with_differing_type_returns_false() {
    $this->assertFalse(create(new Token([T_STRING, "", 0]))->is(T_NAMESPACE));
  }

  #[@test]
  public function is_with_string_compares_string_token_and_literal() {
    $this->assertTrue(create(new Token([T_STRING, ";", 0]))->is(";"));
  }

  #[@test]
  public function is_with_string_compares_fails_on_different_type() {
    $this->assertFalse(create(new Token([T_NAMESPACE, ";", 0]))->is(";"));
  }

  #[@test]
  public function is_with_string_compares_fails_on_different_literal() {
    $this->assertFalse(create(new Token([T_STRING, "(", 0]))->is(";"));
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
  public function token_even_equals_on_when_line_differs() {
    $a= new Token([1, "foo", 1]);
    $b= new Token([1, "foo", 2]);

    $this->assertTrue($a->equals($b));
  }
}
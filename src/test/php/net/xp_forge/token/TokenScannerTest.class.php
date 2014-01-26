<?php namespace net\xp_forge\token;

class TokenScannerTest extends \unittest\TestCase {
  private $cut= null;

  public function setUp() {
    $this->cut= new TokenScanner(TokenSequence::fromString('<?php namespace foo;')->iterator());
  }

  #[@test]
  public function scan() {
    $called= false;
    $this->cut->when(T_NAMESPACE, function($token, $iterator) use (&$called) {
      $called= true;
    });
    $this->cut->run();

    $this->assertTrue($called);
  }

  #[@test]
  public function quitOn_applied_before_when() {
    $called= false;
    $this->cut->when(T_NAMESPACE, function($token, $iterator) use (&$called) {
      $called= true;
    });
    $this->cut->quitOn(T_NAMESPACE);
    $this->cut->run();

    $this->assertFalse($called);
  }
}
<?php namespace net\xp_forge\cmd;

use net\xp_forge\cmd\FixUsesStatements;
use net\xp_forge\token\TokenSequence;
use net\xp_forge\token\Token;

class FixUsesStatementsTest extends \unittest\TestCase {
  private $cut = null;

  public function setUp() {
    $this->cut= new FixUsesStatements();
  }

  private function scanImportsOn($code) {
    $this->cut->withSequence(TokenSequence::fromString($code));
    return $this->cut->scanImports();
  }

  #[@test]
  public function detect_uses() {
    $imports= $this->scanImportsOn('<?php uses("foo.bar", "lang.Object"); class BarBaz extends Object {} ');
    $this->assertEquals([
      'bar' => 'foo.bar',
      'Object' => 'lang.Object'
      ], $imports);
  }

  private function scanAllOn($code) {
    $cut= newinstance('net.xp_forge.cmd.FixUsesStatements', [], '{
      protected $ref= [];
      protected function verifyClassRef(\\net\\xp_forge\\token\\Token $ref) {
        $this->ref[$ref->literal()]= true;
      }

      public function refs() {
        return array_keys($this->ref);
      }
    }');

    $cut->withSequence(TokenSequence::fromString($code));
    $cut->scanAll();

    return $cut->refs();
  }

  #[@test]
  public function classes_from_new() {
    $this->assertEquals(['Foo'], $this->scanAllOn('<?php new Foo();'));
  }

  #[@test]
  public function class_from_extends() {
    $this->assertEquals(['Object'], $this->scanAllOn('<?php class SomeClass extends Object {}'));
  }

  #[@test]
  public function class_from_implements() {
    $this->assertEquals(['Object', 'XPIterator'], $this->scanAllOn('<?php class SomeClass extends Object implements XPIterator {}'));
  }

  #[@test]
  public function classes_from_implements_multiple() {
    $this->assertEquals(['Object', 'XPIterator', 'Traceable'], $this->scanAllOn('<?php class SomeClass extends Object implements XPIterator, Traceable {}'));
  }

  #[@test]
  public function classes_from_catch() {
    $this->assertEquals(['Object', 'SQLException'], $this->scanAllOn('<?php class SomeClass extends Object {
      function foo() { try { } catch (SQLException $e) { } }
    '));
  }
}
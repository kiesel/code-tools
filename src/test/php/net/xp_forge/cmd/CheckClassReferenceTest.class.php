<?php namespace net\xp_forge\cmd;

use net\xp_forge\cmd\CheckClassReferences;
use net\xp_forge\token\TokenSequence;
use lang\SystemExit;

class CheckClassReferenceTest extends \unittest\TestCase {

  #[@test]
  public function create() {
    new CheckClassReferences();
  }

  protected function check($source) {
    $c= (new CheckClassReferences())
      ->withSequence(TokenSequence::fromString($source))
    ;

    try {
      $c->run();
    } catch (SystemExit $e) {
      $this->fail('assert_no_errors', $c->errors(), []);
    }
    return $c;
  }

  #[@test]
  public function no_errors() {
    $c= $this->check('<?php namespace foo; class Bar extends \\lang\\Object {}');

    $this->assertEquals(0, sizeof($c->errors()));
  }

  #[@test]
  public function dynamic_classname() {
    $c= $this->check('<?php namespace foo; class CheckedClass extends \\lang\\Object { 
      public function run() {
        new $class();
      }
    }');

    $this->assertEquals(0, sizeof($c->errors()));
  }
}
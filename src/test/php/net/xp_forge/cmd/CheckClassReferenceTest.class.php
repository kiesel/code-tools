<?php namespace net\xp_forge\cmd;

use net\xp_forge\cmd\CheckClassReferences;
use net\xp_forge\token\TokenSequence;
use lang\SystemExit;

class CheckClassReferenceTest extends \unittest\TestCase {

  #[@test]
  public function create() {
    new CheckClassReferences();
  }

  protected function check($source, $classes= []) {
    $c= newinstance('net.xp_forge.cmd.CheckClassReferences', [], '{
      protected $classes= [];

      public function withClass($fqcn) {
        $this->classes[$fqcn]= true;
        return $this;
      }

      protected function classAvailable($fqcn, $className) {
        return isset($this->classes[$fqcn]);
      }
    }')
      ->withSequence(TokenSequence::fromString($source))
    ;

    foreach ($classes as $class) {
      $c->withClass($class);
    }

    try {
      $c->run();
    } catch (SystemExit $e) {
      // $this->fail('assert_no_errors', $c->errors(), []);
    }
    return $c;
  }

  #[@test]
  public function no_errors() {
    $c= $this->check('<?php namespace foo; class Bar extends \\lang\\Object {}', ['lang.Object']);

    $this->assertEquals(0, sizeof($c->errors()));
  }

  #[@test]
  public function dynamic_classname() {
    $c= $this->check('<?php namespace foo; class CheckedClass extends \\lang\\Object { 
      public function run() {
        new $class();
      }
    }', ['lang.Object']);

    $this->assertEquals(0, sizeof($c->errors()));
  }

  #[@test]
  public function detect_missing_class() {
    $c= $this->check('<?php namespace foo\\bar; class CheckedClass extends Bla {} ');
    $this->assertEquals(1, sizeof($c->errors()));
  }

  #[@test]
  public function detect_missing_class_in_parent_namespace() {
    $c= $this->check('<?php namespace foo\\bar; class CheckedClass extends Bla {}', ['foo.Bla']);
    $this->assertEquals(0, sizeof($c->errors()));
  }
}
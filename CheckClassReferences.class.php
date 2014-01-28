<?php

use net\xp_forge\token\Token;
use net\xp_forge\token\TokenSequence;
use net\xp_forge\token\TokenSequenceIterator;
use net\xp_forge\token\SequenceAggregator;
use net\xp_forge\token\FilteredIterator;
use net\xp_forge\token\TokenScanner;

class CheckClassReferences extends \util\cmd\Command {
  private $file = null;

  private $namespace = null;
  private $declares = [];
  private $sequence= null;

  private $errors= [];
  private $warnings= [];

  #[@arg(name= 'file')]
  public function setFile($f) {
    $this->file= $f;
  }

  private function filteredIterator() {
    $iterator= new FilteredIterator($this->sequence->iterator());
    $iterator->addDefaultFilters();

    return $iterator;
  }

  private function readNamespaceAndImports() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;

    $scanner->when(T_NAMESPACE, function($token, $iterator) use ($self) {
        $self->namespace= $iterator->next()->literal();
        $self->out('---> Detected namespace: ', xp::stringOf($this->namespace));
      })
      ->when(T_USE, function($token, $iterator) use ($self) {
        $class= $iterator->next()->literal();

        if ($iterator->next()->is(T_AS)) {
          $alias= $iterator->next()->literal();
          $this->registerImport($class, $alias);
        } else {
          $this->registerImport($class);
        }
      })
      ->quitOn([T_CLASS, T_INTERFACE])
      ->run()
    ;
  }

  private function out() {
    $args= func_get_args();
    call_user_func_array([$this->out, 'writeLine'], $args);
  }

  private function registerImport($class, $alias= null) {
    if (null == $alias) {
      $alias= substr($class, strrpos($class, '\\')+ 1);
    }

    if (isset($this->declares[$alias])) {
      $this->out('E Double alias: "', xp::stringOf($alias));
      return;
    }

    $this->checkClassExists($class, true);
    $this->declares[$alias]= $class;
  }

  private function verifyReferences() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $last= null;

    $scanner
      ->when(T_NEW, function($token, $iterator) use ($self) {
        $class= $iterator->next()->literal();
        $self->checkClassReference($class);
      })
      ->when(T_CATCH, function($token, $iterator) use ($self) {
        $iterator->next(); // Eat '('

        $class= $iterator->next()->literal();
        $self->checkClassReference($class);
      })
      ->when(T_DOUBLE_COLON, function($token, $iterator) use ($self, &$last) {
        $self->checkClassReference($last);
      })
      ->when(T_STRING, function($token, $iterator) use ($self, &$last) {

        // Remember for later use
        $last= $token->literal();
      })
      ->run()
    ;
  }

  private function checkClassReference($className) {

    // Check if some alias matches
    if (isset($this->declares[$className])) {

      // Given there's an alias registered, we may assume the class
      // exists and is loadable
      return true;
    }

    // In any other case, we need to make sure the class does actually
    // exist and is loadable
    $this->checkClassExists($className);
  }

  private function checkClassExists($className, $inImports= false) {

    // If className contains \ but then does not start with \
    // it uses relative namespaces which is discouraged by XP
    if (false !== strpos($className, '\\')) {
      if ('\\' == $className{0} && $inImports) {
        $this->out('W Using absolute reference in use is discouraged: ', xp::stringOf($className));
      } else if ('\\' != $className{0} && !$inImports) {
        $this->out('W Using relative class references like "'.$className.'" is discouraged.');
      }
    }

    // Convert given name to XP name
    $fqcn= strtr(ltrim($className, '\\'), '\\', '.');

    if ('xp' == $fqcn) return true;

    $cl= \lang\ClassLoader::getDefault();
    if (!$cl->providesClass($fqcn)) {
      $this->addError('E Class '.$fqcn.' cannot be loaded.');
    }
  }

  private function addError($string) {
    $this->errors[]= $string;
  }

  /**
   * Run
   * 
   */
  public function run() {
    $aggregator= new SequenceAggregator(
      TokenSequence::fromString(\io\FileUtil::getContents(new io\File($this->file)))
    );
    $this->sequence= $aggregator->emit();

    $this->readNamespaceAndImports();
    $this->verifyReferences();

    $this->out('---> Detected errors:', xp::StringOf($this->errors));
  }
}
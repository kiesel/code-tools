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

  private function scanNamespace() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $scanner->when(T_NAMESPACE, function($token, $iterator) use ($self) {
        $this->namespace= $iterator->next()->literal();
      })
      ->quitOn([T_CLASS, T_INTERFACE, T_USE])
      ->run()
    ;
  }

  private function scanDeclares() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $scanner->when(T_USE, function($token, $iterator) use ($self) {
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
      throw new \lang\IllegalStateException('Alias "'.$alias.'" already taken.');
    }

    $this->checkClassExists($class);
    $this->declares[$alias]= $class;
  }

  private function scanInstantiations() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $scanner->when(T_NEW, function($token, $iterator) use ($self) {
        $class= $iterator->next()->literal();
        $self->checkClassReference($class);
      })
      ->run()
    ;
  }

  private function scanStaticCalls() {
    $class= '';
    $iterator= $this->sequence->iterator();

    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_DOUBLE_COLON)) {
      }
    }
  }

  private function scanCatches() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $scanner->when(T_CATCH, function($token, $iterator) use ($self) {
        $iterator->next(); // Eat '('

        $class= $iterator->next()->literal();
        $self->checkClassReference($class);
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

  private function checkClassExists($className) {
    $this->out->writeLine('---> Checking class ', $className);
    
    // Convert given name to XP name
    $fqcn= strtr(ltrim($className, '\\'), '\\', '.');

    $cl= \lang\ClassLoader::getDefault();
    if (!$cl->providesClass($fqcn)) {
      $this->addError('Class '.$fqcn.' cannot be loaded.');
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
      TokenSequence::fromString(\io\FileUtil::getContents(new \io\File($this->file)))
    );
    $this->sequence= $aggregator->emit();

    $this->scanNamespace();
    $this->scanDeclares();

    $this->out->writeLine('---> Detected namespace: ', xp::stringOf($this->namespace));
    $this->out->writeLine('---> Detected imports: ', xp::stringOf($this->declares));

    $this->scanInstantiations();
    $this->scanStaticCalls();
    $this->scanCatches();

    $this->out->writeLine('---> Detected errors:', xp::StringOf($this->errors));
  }
}
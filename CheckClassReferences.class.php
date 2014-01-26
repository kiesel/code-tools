<?php

use net\xp_forge\token\Token;
use net\xp_forge\token\TokenSequence;
use net\xp_forge\token\TokenSequenceIterator;
use net\xp_forge\token\SequenceAggregator;

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

  private function readIdentifier(TokenSequenceIterator $iterator) {
    $identifier= '';

    while ($iterator->hasNext()) {
      $token= $iterator->next();
      if ($token->is([T_WHITESPACE, '(', ';'])) {
        return $identifier;
      }

      $identifier.= $token->literal(); 
    }

    throw new \lang\IllegalStateException('Unable to read identifier.');
  }

  private function scanNamespace() {
    $iterator= $this->sequence->iterator();
    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_NAMESPACE)) {
        $iterator->next();
        $this->namespace= $this->readIdentifier($iterator);
        return;
      }
    }
  }

  private function scanDeclares() {
    $class= null;
    $as= null;

    $iterator= $this->sequence->iterator();
    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_USE)) {
        if (null != $class) {
          $this->registerImport($class);
        }
        $iterator->next();

        $class= $this->readIdentifier($iterator);
      }

      if ($token->is(T_AS)) {
        $iterator->next();

        $alias= $this->readIdentifier($iterator);

        $this->registerImport($class, $alias);
        $class= $alias= null;
      }

      if ($token->is(T_CLASS) || $token->is(T_INTERFACE)) {
        if (null != $class) {
          $this->registerImport($class);
        }

        return;
      }
    }
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
    $iterator= $this->sequence->iterator();

    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_NEW)) {
        $line= $token->line();

        $iterator->next(); // Eat whitespace

        $class= $this->readIdentifier($iterator);
        $this->checkClassReference($class);
      }
    }
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

    $this->out->writeLine('---> Detected errors:', xp::StringOf($this->errors));

    // var_dump(xp::stringOf($this->sequence));
  }
}
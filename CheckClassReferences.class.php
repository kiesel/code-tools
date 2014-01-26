<?php

use net\xp_forge\token\Token;
use net\xp_forge\token\TokenSequence;
use net\xp_forge\token\TokenSequenceIterator;
use net\xp_forge\token\SequenceAggregator;
use net\xp_forge\token\FilteredIterator;

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

  private function scanNamespace() {
    $iterator= new FilteredIterator($this->sequence->iterator());
    $iterator->addDefaultFilters();

    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_NAMESPACE)) {
        $token= $iterator->next();
        $this->namespace= $token->literal();
        return;
      }
    }
  }

  private function scanDeclares() {
    $iterator= new FilteredIterator($this->sequence->iterator());
    $iterator->addDefaultFilters();

    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_USE)) {
        $class= $iterator->next()->literal();

        if ($iterator->next()->is(T_AS)) {
          $alias= $iterator->next()->literal();
          $this->registerImport($class, $alias);
        } else {
          $this->registerImport($class);
        }

        continue;
      }

      if ($token->is([T_CLASS, T_INTERFACE])) {
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
    $iterator= new FilteredIterator($this->sequence->iterator());
    $iterator->addDefaultFilters();

    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_NEW)) {
        $line= $token->line();

        $class= $iterator->next()->literal();
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

  private function scanCatches() {
    $iterator= new FilteredIterator($this->sequence->iterator());
    $iterator->addDefaultFilters();

    while ($iterator->hasNext()) {
      $token= $iterator->next();

      if ($token->is(T_CATCH)) {
        $iterator->next(); // Eat '('

        $class= $iterator->next()->literal();
        $this->checkClassReference($class);
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

    // var_dump(xp::stringOf($this->sequence));
  }
}
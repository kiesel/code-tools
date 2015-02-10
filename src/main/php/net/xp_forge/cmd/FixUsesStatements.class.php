<?php namespace net\xp_forge\cmd;

use io\File;
use io\FileUtil;
use util\log\Logger;
use util\log\ConsoleAppender;
use net\xp_forge\token\Token;
use net\xp_forge\token\TokenSequence;
use net\xp_forge\token\TokenSequenceIterator;
use net\xp_forge\token\SequenceAggregator;
use net\xp_forge\token\FilteredIterator;
use net\xp_forge\token\TokenScanner;
use lang\ClassLoader;

class FixUsesStatements extends \util\cmd\Command {
  private $cat = null;

  private $sequence= null;
  private $tokenSequence= null;

  private $imports= [];

  public function __construct() {
    $this->cat = Logger::getInstance()->getCategory();
  }

  private function out() {
    $args= func_get_args();
    call_user_func_array([$this->out, 'writeLine'], $args);
  }

  #[@arg(name= 'file')]
  public function setFile($f) {
    $this->out('===> Checking ', $f);
    $this->withSequence(TokenSequence::fromString(FileUtil::getContents(new File($f))));
  }

  public function withSequence(TokenSequence $sequence) {
    $this->tokenSequence= $sequence;
    return $this;
  }

  #[@arg(name= 'verbose')]
  public function setVerbose($v= false) {
    if (null === $v) {
      Logger::getInstance()->getCategory()->withAppender(new ConsoleAppender());
    }
  }

  private function filteredIterator() {
    $iterator= new FilteredIterator($this->sequence->iterator());
    $iterator->addDefaultFilters();

    return $iterator;
  }

  private function registerImport($fqcn) {
    $lname= substr($fqcn, strrpos($fqcn, '.')+ 1);
    $this->imports[$lname]= $fqcn;
  }

  private function scanImports() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $inUses= false;

    $scanner
      ->when(T_STRING, function($token, $iterator) use ($self, &$inUses) {
        if (!$inUses) {
          if ('uses' === $token->literal()) {
            $inUses= true;
          }
          return;
        } else if (')' == $token->literal()) {
          $inUses= false;
        }
      })
      ->when(T_CONSTANT_ENCAPSED_STRING, function($token, $iterator) use ($self, &$inUses) {
        $self->registerImport(trim($token->literal(), '"\''));
      })
      ->quitOn(T_CLASS)
    ;
    $scanner->run();
  }

  private function verifyClassRef($lname) {
    // $this->cat->debug('Checking ref', $lname);
    if (isset($this->imports[$lname])) return;

    $this->cat->warn('Missing import for', $lname);
  }

  private function scanAll() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $inUses= false;

    $scanner
      ->when(T_NEW, function($token, $iterator) use ($self) {
        $self->verifyClassRef($iterator->next()->literal());
      })
    ;
    
    $scanner->run();    
  }

  public function run() {
    $aggregator= new SequenceAggregator($this->tokenSequence);
    $this->sequence= $aggregator->emit();

    $this->scanImports();
    $this->out($this->imports);

    $this->scanAll();
  }
}
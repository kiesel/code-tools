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
use lang\SystemExit;
use lang\ElementNotFoundException;

class FixUsesStatements extends \util\cmd\Command {
  private $cat = null;

  private $file= null;
  private $inplace= false;
  private $sort= false;

  private $sequence= null;
  private $tokenSequence= null;

  private $imports= [];
  private $loadables= [];

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
    $this->file= new File($f);
    $this->withSequence(TokenSequence::fromString(FileUtil::getContents($this->file)));
  }

  #[@arg(name= 'inplace', short= 'i')]
  public function setInplace($i= false) {
    $this->inplace= ($i === null);
  }

  #[@arg(name= 'sort')]
  public function setSort($s= false) {
    $this->sort= ($s === null);
  }

  public function withSequence(TokenSequence $sequence) {
    $this->tokenSequence= $sequence;
    $aggregator= new SequenceAggregator($this->tokenSequence);
    $this->sequence= $aggregator->emit();

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

  public function scanImports() {
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

    return $this->imports;
  }

  private function setupClassTable($root= '') {
    foreach (ClassLoader::getDefault()->packageContents($root) as $package) {
      if ('/' == $package{strlen($package)- 1}) {
        $this->setupClassTable($root.$package);
        continue;
      }

      if ('.class.php' === substr($package, -10)) {
        $this->addLoadable($package, $root);
      }
    }
  }

  private function addLoadable($filename, $path) {

    // Prepend root, remove '.class.php'
    $fqcn= strtr($path.substr($filename, 0, -10), '/', '.');
    $lname= substr($fqcn, strrpos($fqcn, '.')+ 1);

    if (isset($this->loadables[$lname])) {
      $this->cat->warn('Shadowed class ', $fqcn, '@', $path, $filename);
      return;
    }

    // $this->cat->debug('Adding', $lname, 'as', $fqcn);
    $this->loadables[$lname]= $fqcn;
  }

  protected function verifyClassRef(Token $token) {
    $lname= $token->literal();

    // Check for references to NOT check:
    if (false !== strpos($lname, '\\')) return;
    if (in_array($lname, ['xp', 'self', 'parent'])) return;

    // $this->cat->debug('Checking ref', $lname);
    if (isset($this->imports[$lname])) return;

    $this->cat->warn('Missing import for', $lname);

    if (isset($this->loadables[$lname])) {
      $this->out('Suggesting ', $this->loadables[$lname], ' as reference for ', $lname);
      $this->imports[$lname]= $this->loadables[$lname];
    } else {
      $this->cat->error('Unresolved class reference', $token);
      throw new ElementNotFoundException('Unresolved class reference '.\xp::stringOf($token));
    }
  }

  public function scanAll() {
    $scanner= new TokenScanner($this->filteredIterator());
    $self= $this;
    $inUses= false;
    $last= null;

    $scanner
      ->when(T_NEW, function($token, $iterator) use ($self) {
        $self->verifyClassRef($iterator->next());
      })
      ->when(T_EXTENDS, function($token, $iterator) use ($self) {
        $self->verifyClassRef($iterator->next());
      })
      ->when(T_IMPLEMENTS, function($token, $iterator) use ($self) {

        // Currently on "implements", now fetch next token
        $token= $iterator->next();

        while ($token->is(T_STRING) && !$token->is('{')) {

          if (!$token->is(',')) {
            $self->verifyClassRef($token);
          }

          $token= $iterator->next();
          if (!$token instanceof Token) {
            return;
          }
        }
      })
      ->when(T_CATCH, function($token, $iterator) use ($self) {
        // Eat 'catch', eat '('
        $iterator->next();
        $token= $iterator->next();

        $self->verifyClassRef($token);
      })
      ->when(T_STRING, function($token, $iterator) use ($self, &$last) {
        if ($token) {
          $last= $token;
        }
      })
      ->when(T_DOUBLE_COLON, function($token, $iterator) use ($self, &$last) {
        $self->verifyClassRef($last);
      })
    ;
    
    $scanner->run();    
  }

  private function outputRepaired() {
    // Nothing to see here
    if (0 == sizeof($this->imports)) return;
    if ($this->sort) {
      sort($this->imports);
    }

    $self= $this;

    $out= preg_replace_callback('#(^[ \t]+uses\([^\)]+\);).*#mi', function($matches) use ($self) {
      $imports= "  uses(\n";
      foreach ($self->imports as $fqcn) {
        $imports.= '    \''.$fqcn."',\n";
      }

      $imports= substr($imports, 0, -2)."\n  );\n";
      $self->cat->info('Fixed imports:', $imports);
      return $imports;

    }, FileUtil::getContents($this->file));

    if ($this->inplace) {
      FileUtil::setContents($this->file, $out);
    }
  }

  public function run() {
    $this->setupClassTable();

    $this->scanImports();
    $this->out($this->imports);

    try {
      $this->scanAll();
      $this->outputRepaired();
    } catch (SystemExit $e) {
      $this->cat->error($e);
      throw $e;
    }
  }
}
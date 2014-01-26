<?php namespace net\xp_forge\token;

class FilteredIterator extends \lang\Object implements \util\XPIterator {
  private $iterator= null;
  private $filter= [];
  private $next= null;

  public function __construct(TokenSequenceIterator $iterator) {
    $this->iterator= $iterator;
  }

  public function addDefaultFilters() {
    $this->addFilter(T_WHITESPACE);
    $this->addFilter(T_COMMENT);
    $this->addFilter(T_DOC_COMMENT);
  }

  public function addFilter($filter) {
    $this->filter[]= $filter;
  }

  public function hasNext() {
    if (!$this->next instanceof Token) {
      $this->next= $this->next();
    }

    return $this->next instanceof Token;
  }

  public function next() {

    // Deliver tokens pre-looked up by hasNext()
    if ($this->next instanceof Token) {
      $token= $this->next;
      $this->next= null;
      return $token;
    }

    $token= true;
    while ($token) {
      $token= $this->iterator->next();
      if ($token instanceof Token && $token->is($this->filter)) {
        continue;
      }

      return $token;
    }
  }
}
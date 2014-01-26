<?php namespace net\xp_forge\token;

class FilteredIteratorTest extends \unittest\TestCase {

  #[@test]
  public function create() {
    $this->assertInstanceOf(
      'net.xp_forge.token.FilteredIterator',
      new FilteredIterator(TokenSequence::fromString('<?php namespace foo;')->iterator())
    );
  }

  #[@test]
  public function filter_returns_nonfiltered_tokens() {
    $iterator= new FilteredIterator(TokenSequence::fromString('<?php namespace foo;')->iterator());
    $iterator->addFilter(T_WHITESPACE);
    $iterator->addFilter(T_OPEN_TAG);

    $this->assertEquals(
      new Token([T_NAMESPACE, "namespace", 0]),
      $iterator->next()
    );
  }

  #[@test]
  public function hasNext_respects_filter() {
    $iterator= new FilteredIterator(TokenSequence::fromString('<?php namespace foo;')->iterator());
    $iterator->addFilter(T_WHITESPACE);
    $iterator->addFilter(T_OPEN_TAG);
    $iterator->addFilter(T_NAMESPACE);
    $iterator->addFilter(T_STRING);

    $this->assertFalse($iterator->hasNext());
  }
}
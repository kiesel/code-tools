<?php namespace net\xp_forge\token;

class TokenSequenceIteratorTest extends \unittest\TestCase {


  #[@test]
  public function create() {
    $this->assertInstanceOf(
      'net.xp_forge.token.TokenSequenceIterator',
      TokenSequence::fromString('<?php namespace foo;')->iterator()
    );
  }

  #[@test]
  public function iterate() {
    $iterator= TokenSequence::fromString('<?php ')->iterator();

    $this->assertTrue($iterator->hasNext());
    $this->assertEquals(
      new Token([T_OPEN_TAG, '<?php ', 1]),
      $iterator->next()
    );
    $this->assertFalse($iterator->hasNext());
  }

  #[@test]
  public function iterate_proceeds() {
    $iterator= TokenSequence::fromString('<?php namespace')->iterator();

    $this->assertTrue($iterator->hasNext());
    $this->assertEquals(
      new Token([T_OPEN_TAG, '<?php ', 1]),
      $iterator->next()
    );
    $this->assertEquals(
      new Token([T_NAMESPACE, 'namespace', 1]),
      $iterator->next()
    );
    $this->assertFalse($iterator->hasNext());
  }
}
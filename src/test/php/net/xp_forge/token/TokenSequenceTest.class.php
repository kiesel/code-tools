<?php namespace net\xp_forge\token;

class TokenSequenceTest extends \unittest\TestCase {

  #[@test]
  public function fromString() {
    $this->assertInstanceOf(
      'net.xp_forge.token.TokenSequence', 
      TokenSequence::fromString('<?php ?>')
    );
  }
  
  #[@test, @expect('lang.ElementNotFoundException')]
  public function at() {
    $seq= TokenSequence::fromString('<?php namespace foo; ?>');
    $seq->at(45);
  }

  #[@test]
  public function length() {
    $seq= TokenSequence::fromString('<?php namespace foo; ?>');
    $this->assertEquals(7, $seq->length());
  }
  
  #[@test]
  public function containsTokens() {
    $seq= TokenSequence::fromString('<?php namespace foo; ?>');
    $this->assertInstanceOf(
      'net.xp_forge.token.Token', 
      $seq->at(0)
    );
  }



}
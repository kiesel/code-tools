<?php namespace net\xp_forge\token;

class SequenceAggregatorTest extends \unittest\TestCase {

  #[@test]
  public function create() {
    new SequenceAggregator(new TokenSequence());
  }

  #[@test]
  public function namespace_becomes_combined() {
    $agg= new SequenceAggregator(TokenSequence::fromString('<?php namespace foo\\bar\\baz;'));

    $expect= new TokenSequence();
    $expect->append(new Token([T_OPEN_TAG, '<?php ', 1]));
    $expect->append(new Token([T_NAMESPACE, 'namespace', 1]));
    $expect->append(new Token([T_WHITESPACE, ' ', 1]));
    $expect->append(new Token([T_STRING, 'foo\\bar\\baz', 1]));
    $expect->append(new Token([T_STRING, ';', -1]));

    $this->assertEquals(
      $expect,
      $agg->emit()
    );
  }

  #[@test]
  public function static_namespaced_call_becomes_combined() {
    $agg= new SequenceAggregator(TokenSequence::fromString('<?php foo\\bar::method();'));

    $expect= new TokenSequence();
    $expect->append(new Token([T_OPEN_TAG, '<?php ', 1]));
    $expect->append(new Token([T_STRING, 'foo\\bar', 1]));
    $expect->append(new Token([T_DOUBLE_COLON, '::', 1]));
    $expect->append(new Token([T_STRING, 'method', 1]));
    $expect->append(new Token([T_STRING, '(', 1]));
    $expect->append(new Token([T_STRING, ')', 1]));
    $expect->append(new Token([T_STRING, ';', 1]));

    $this->assertEquals(
      $expect,
      $agg->emit()
    );
  }

  #[@test]
  public function string_concatenation_will_be_stripped() {
    $agg= new SequenceAggregator(TokenSequence::fromString('<?php "Hello".\\xp;'));

    $expect= new TokenSequence();
    $expect->append(new Token([T_OPEN_TAG, '<?php ', 1]));
    $expect->append(new Token([T_CONSTANT_ENCAPSED_STRING, '"Hello"', 1]));
    $expect->append(new Token([T_STRING, '.', 1]));
    $expect->append(new Token([T_STRING, '\\xp', 1]));
    $expect->append(new Token([T_STRING, ';', 1]));

    $this->assertEquals($expect, $agg->emit());
  }
}
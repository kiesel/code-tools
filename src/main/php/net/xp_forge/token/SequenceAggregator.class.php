<?php namespace net\xp_forge\token;

class SequenceAggregator extends \lang\Object {

  public function __construct(TokenSequence $sequence) {
    $this->sequence= $sequence;
  }

  public function emit() {
    $seq= new TokenSequence();

    $iterator= $this->sequence->iterator();
    $string= ''; $line= 0;
    while ($iterator->hasNext()) {
      $token= $iterator->next();
      if ($token->line() > 0) { $line= $token->line(); }

      if ($token->is(T_NS_SEPARATOR)) {
        $string.= $token->literal();
        continue;
      } else if ($token->is(T_STRING) && !$token->isStatementSeparator()) {
        $string.= $token->literal();
        continue;
      }

      if (strlen($string)) {
        $seq->append(new Token([T_STRING, $string, $line]));
        $string= '';
      }

      $seq->append($token);
    }

    return $seq;
  }
}
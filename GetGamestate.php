<?php

  $gameName=$_GET["gameName"];
  $playerID=$_GET["playerID"];

  include "ParseGamestate.php";

  echo(implode(" ", $playerHealths) . "<BR>"); //0

  //Player 1
  if($playerID == 1) echo(implode(" ", $p1Hand) . "<BR>"); else echo(count($p1Hand) . "<BR>");
  echo(count($p1Deck) . "<BR>");
  echo(implode(" ", $p1CharEquip) . "<BR>");
  echo(implode(" ", $p1Resources) . "<BR>"); //4
  if($playerID == 1) echo($p1Arsenal . "<BR>");
  echo(implode(" ", $p1Items) . "<BR>");
  echo(implode(" ", $p1Auras) . "<BR>");
  echo(implode(" ", $p1Discard) . "<BR>");
  echo(implode(" ", $p1Pitch) . "<BR>"); //9
  echo(implode(" ", $p1Banish) . "<BR>");
  echo(implode(" ", $p1ClassState) . "<BR>");
  echo(implode(" ", $p1CharacterEffects) . "<BR>");


  //Player 2
  if($playerID == 2) echo(implode(" ", $p2Hand) . "<BR>"); else echo(count($p2Hand) . "<BR>");  //13
  echo(count($p2Deck) . "<BR>");
  echo(implode(" ", $p2CharEquip) . "<BR>");
  echo(implode(" ", $p2Resources) . "<BR>");
  if($playerID == 2) echo($p2Arsenal . "<BR>");
  echo(implode(" ", $p2Items) . "<BR>"); //18
  echo(implode(" ", $p2Auras) . "<BR>");
  echo(implode(" ", $p2Discard) . "<BR>");
  echo(implode(" ", $p2Pitch) . "<BR>");
  echo(implode(" ", $p2Banish) . "<BR>");//23
  echo(implode(" ", $p2ClassState) . "<BR>");
  echo(implode(" ", $p2CharacterEffects) . "<BR>");



  echo($winner . "<BR>");
  echo($currentPlayer . "<BR>");
  echo($currentTurn . "<BR>"); //28
  echo(implode(" ", $turn) . "<BR>");
  echo($actionPoints . "<BR>");
  echo(implode(" ", $combatChain) . "<BR>");
  echo(implode(" ", $combatChainState) . "<BR>");
  echo(implode(" ", $currentTurnEffects) . "<BR>"); //33
  echo(implode(" ", $nextTurnEffects) . "<BR>");
  echo(implode(" ", $decisionQueue) . "<BR>");
  echo($mainPlayer . "<BR>"); //39

?>

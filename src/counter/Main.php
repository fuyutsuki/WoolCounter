<?php
namespace counter;

#Base
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;

#Commands
use pocketmine\command\CommandExecutor;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

#Events
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

#Utils
use pocketmine\utils\TextFormat as Color;
use pocketmine\utils\Config;

#etc
use pocketmine\level\level;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

class Main extends PluginBase implements Listener{
  const NAME = 'WoorlsCounter',
        VERSION = 'v1.0.0';

  private $stage = [],
          $id = "152",//レッドストーンブロック
          $countIDs = ["35:0", "35:7", "35:8"];//白羊毛, 濃い灰色羊毛, 灰色羊毛

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    $this->getLogger()->info(Color::GREEN.self::NAME." ".self::VERSION." が読み込まれました。");
  }

  public function onCommand(CommandSender $s, Command $command, $label, array $args){
    $n = $s->getName();
    if ($label === "e") {
      $this->eraser($s);
    }
    return true;
  }

  public function BlockBreak(BlockBreakEvent $e){
    $i = $e->getItem();
    $id = $i->getID();
    if ((string)$id === $this->id) {
      $p = $e->getPlayer();
      $n = $p->getName();
      if (empty($this->stage[$n]["break"])) {
        $b = $e->getBlock();
        $x = $b->x;
        $y = $b->y;
        $z = $b->z;
        $this->stage[$n]["break"] = [$x, $y, $z];
        $m = Color::AQUA."[ $x, $y, $z ] に始点を設定しました";
        if (isset($this->stage[$n]["place"])) {
          $w = $this->countWools($p);
          if ($w !== false) {
            $m .= " ".Color::GREEN."計 ".$w." Blocks";
          }
        }
        $p->sendMessage($m);
        $e->setCancelled();
      }
    }
  }

  public function BlockPlace(BlockPlaceEvent $e){
    $i = $e->getItem();
    $id = $i->getID();
    if ((string)$id === $this->id) {
      $p = $e->getPlayer();
      $n = $p->getName();
      if (empty($this->stage[$n]["place"])) {
        $b = $e->getBlock();
        $x = $b->x;
        $y = $b->y;
        $z = $b->z;
        $this->stage[$n]["place"] = [$x, $y, $z];
        $m = Color::AQUA."[ $x, $y, $z ] に終点を設定しました";
        if (isset($this->stage[$n]["break"])) {
          $w = $this->countWools($p);
          if ($w !== false) {
            $m .= " ".Color::GREEN."計 ".$w." Blocks";
          }
        }
        $p->sendMessage($m);
        $e->setCancelled();
      }
    }
  }

  /**
   * @param Player | $p
   *
   * @return string | WoolCounts or bool | false
   */
  public function countWools(Player $p){
    $n = $p->getName();
    if (isset($this->stage[$n]["break"]) and
        isset($this->stage[$n]["place"])) {
      $pos = $this->stage[$n];
      $minx = min($pos["break"][0], $pos["place"][0]);//x
      $miny = min($pos["break"][1], $pos["place"][1]);//y
      $minz = min($pos["break"][2], $pos["place"][2]);//z

      $maxx = max($pos["break"][0], $pos["place"][0]);//x
      $maxy = max($pos["break"][1], $pos["place"][1]);//y
      $maxz = max($pos["break"][2], $pos["place"][2]);//z

      $lev = $p->getLevel();

      $ids = [];
      for ($x = $minx; $x <= $maxx; ++$x) {
        for ($y = $miny; $y <= $maxy; ++$y) {
          for ($z = $minz; $z <= $maxz; ++$z) {
            $bpos = new Vector3($x, $y, $z);
            $b = $lev->getBlock($bpos);
            $bid = $b->getID().":".$b->getDamage();
            $ids[] = $bid;
          }
        }
      }
      $count = count($ids);
      $diff = count(array_diff($ids, $this->countIDs));
      $wools = $count - $diff;
      return $wools;
    }else {
      return false;
    }
  }

  public function eraser($p){
    $n = $p->getName();
    if (isset($this->stage[$n])) {
      unset($this->stage[$n]);
      $m = Color::GREEN."始点と終点の指定を解除しました。";
    }else {
      $m = Color::RED."始点か終点、またはどちらも指定されていません。";
    }
    $p->sendMessage($m);
  }

  public function onDisable(){
    $this->getLogger()->info(Color::RED.self::NAME." が無効化されました。");
  }
}

<?php
declare(strict_types=1);

namespace alvin0319\Stat;

use alvin0319\LevelAPI\event\PlayerLevelUpEvent;
use alvin0319\Stat\command\StatCommand;
use alvin0319\Stat\form\StatUpForm;
use Content\event\ContentClearEvent;
use EasterEgg\event\FindEasterEggEvent;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use OnixUtils\OnixUtils;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Crops;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\IntTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use Quest\event\QuestClearEvent;
use function array_keys;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function floor;
use function json_decode;
use function json_encode;
use function mt_rand;

class Stat extends PluginBase implements Listener{
	use SingletonTrait;

	public const STAT_FARM_FORTUNE = "farm";

	public const STAT_MINE_FORTUNE = "mine";

	public const STAT_EVENT_FORTUNE = "event";

	public const STAT_MAX = [
		self::STAT_FARM_FORTUNE => 10,
		self::STAT_MINE_FORTUNE => 10,
		self::STAT_EVENT_FORTUNE => 5
	];

	/** @var array<string, array<string, string>> */
	protected array $db = [];

	protected function onLoad() : void{
		self::setInstance($this);
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		if(file_exists($file = $this->getDataFolder() . "Stats.json")){
			$this->db = json_decode(file_get_contents($file), true);
		}
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}

		$this->getServer()->getCommandMap()->register("stat", new StatCommand());
	}

	protected function onDisable() : void{
		file_put_contents($this->getDataFolder() . "Stats.json", json_encode($this->db));
	}

	public function addStat(Player $player, string $stat, int $amount) : void{
		$this->db[$player->getName()]["stats"][$stat] += $amount;
	}

	public function addStatPoint(Player $player, int $amount = 1) : void{
		$this->db[$player->getName()]["point"] += $amount;
		OnixUtils::message($player, "{$amount} 스탯 포인트를 얻었습니다.");
	}

	public function reduceStatPoint(Player $player, int $amount = 1) : void{
		$this->db[$player->getName()]["point"] -= $amount;
	}

	public function clearStat(Player $player) : void{
		$this->db[$player->getName()]["stats"] = [
			self::STAT_FARM_FORTUNE => 0,
			self::STAT_MINE_FORTUNE => 0,
			self::STAT_EVENT_FORTUNE => 0
		];
	}

	public function getStat(Player $player, string $stat) : int{
		return $this->db[$player->getName()]["stats"][$stat] ?? -1;
	}

	public function getStatPoint(Player $player) : int{
		return $this->db[$player->getName()]["point"] ?? -1;
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		if(!isset($this->db[$player->getName()])){
			$this->db[$player->getName()] = [
				"stats" => [],
				"point" => 0
			];
			foreach(array_keys(self::STAT_MAX) as $stat){
				$this->db[$player->getName()]["stats"][$stat] = 0;
			}
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$drops = $event->getDrops();
		if($player->getWorld()->getFolderName() === "mine"){
			if($this->getStat($player, self::STAT_MINE_FORTUNE) > 0){
				$rand = mt_rand(0, self::STAT_MAX[self::STAT_MINE_FORTUNE] + 3);

				if($rand <= $this->getStat($player, self::STAT_MINE_FORTUNE)){
					if(count($drops) > 0){
						for($i = 0; $i < count($drops); $i++){
							if((bool) mt_rand(0, 1)){
								$drops[$i]->setCount(mt_rand(1, 2));
								break;
							}
						}
					}
					$event->setDrops($drops);
				}
			}
		}elseif($block instanceof Crops){
			if($block->getMeta() < 0x07){
				return;
			}
			$stat = $this->getStat($player, self::STAT_FARM_FORTUNE);
			$rand = mt_rand(0, self::STAT_MAX[self::STAT_FARM_FORTUNE] + 3);
			if($rand > $stat){
				return;
			}

			if(count($drops) < 1){
				return;
			}
			for($i = 0; $i < count($drops); $i++){
				if((bool) mt_rand(0, 1)){
					$drops[$i]->setCount(mt_rand(1, 2));
					break;
				}
			}
			$event->setDrops($drops);
		}
	}

	public function onQuestClear(QuestClearEvent $event) : void{
		$player = $event->getPlayer();
		$rewards = $event->getRewards();

		if(count($rewards) > 0){
			$stat = $this->getStat($player, self::STAT_EVENT_FORTUNE);
			$rand = mt_rand(0, self::STAT_MAX[self::STAT_EVENT_FORTUNE] + 3);
			if($rand <= $stat){
				for($i = 0; $i < count($rewards); $i++){
					if((bool) mt_rand(0, 1)){
						$rewards[$i]->setCount(mt_rand(1, 2));
						break;
					}
				}
			}
			$event->setRewards($rewards);
		}
	}

	public function onContentClear(ContentClearEvent $event) : void{
		$player = $event->getPlayer();
		$rewards = $event->getRewards();

		if(count($rewards) > 0){
			$stat = $this->getStat($player, self::STAT_EVENT_FORTUNE);
			$rand = mt_rand(0, self::STAT_MAX[self::STAT_EVENT_FORTUNE] + 3);
			if($rand <= $stat){
				for($i = 0; $i < count($rewards); $i++){
					if((bool) mt_rand(0, 1)){
						$rewards[$i]->setCount(mt_rand(1, 2));
						break;
					}
				}
			}
			$event->setRewards($rewards);
		}
	}

	public function onEasterEggFind(FindEasterEggEvent $event) : void{
		$player = $event->getPlayer();
		$rewards = $event->getRewards();

		$stat = $this->getStat($player, self::STAT_EVENT_FORTUNE);
		$rand = mt_rand(0, self::STAT_MAX[self::STAT_EVENT_FORTUNE] + 3);

		$success = false;

		if(count($rewards) > 0){
			if($rand <= $stat){
				for($i = 0; $i < count($rewards); $i++){
					if((bool) mt_rand(0, 1)){
						$rewards[$i]->setCount(mt_rand(1, 2));
						$success = true;
						break;
					}
				}
			}
			$event->setRewards($rewards);
		}
		if($event->getMoney() > 0){
			if(!$success){
				if($rand <= $stat){
					$event->setMoney((int) floor($event->getMoney() * 1.5));
				}
			}
		}
	}

	public function sendInv(Player $player) : void{
		$inv = InvMenu::create(InvMenu::TYPE_CHEST);
		$inv->setName("스탯");
		$inventory = $inv->getInventory();
		$inv->setListener(function(InvMenuTransaction $action) use ($inv) : InvMenuTransactionResult{
			$item = $action->getOut();
			if($item->getNamedTag()->getTag("event") !== null){
				$inv->onClose($action->getPlayer());
				return $action->discard()->then(function(Player $player) : void{
					$player->getNetworkSession()->getInvManager()->syncSlot($player->getCursorInventory(), 0);
					$player->sendForm(new StatUpForm($player, self::STAT_EVENT_FORTUNE));
				});
			}elseif($item->getNamedTag()->getTag("farm") !== null){
				$inv->onClose($action->getPlayer());
				return $action->discard()->then(function(Player $player) : void{
					$player->getNetworkSession()->getInvManager()->syncSlot($player->getCursorInventory(), 0);
					$player->sendForm(new StatUpForm($player, self::STAT_FARM_FORTUNE));
				});
			}elseif($item->getNamedTag()->getTag("mine") !== null){
				$inv->onClose($action->getPlayer());
				return $action->discard()->then(function(Player $player) : void{
					$player->getNetworkSession()->getInvManager()->syncSlot($player->getCursorInventory(), 0);
					$player->sendForm(new StatUpForm($player, self::STAT_MINE_FORTUNE));
				});
			}
			return $action->discard()->then(function(Player $player) : void{
				$player->getNetworkSession()->getInvManager()->syncSlot($player->getCursorInventory(), 0);
			});
		});
		//11, 13, 15

		$bed = ItemFactory::getInstance()->get(BlockLegacyIds::BED_BLOCK)->setCustomName("§l ");

		for($i = 0; $i < $inv->getInventory()->getSize(); $i++){
			$inv->getInventory()->setItem($i, $bed);
		}

		$eventStat = ItemFactory::getInstance()->get(ItemIds::ENCHANTED_BOOK, 0, 1);
		$eventStat->setCustomName("§d§l클리어 §f스탯\n현재 수치: {$this->getStat($player, self::STAT_EVENT_FORTUNE)}");
		$eventStat->setLore([
			"§d클리어 §f스탯은 §d컨텐츠 클리어§f,",
			"§d이스터에그 발견§f, §d퀘스트 클리어§f",
			"§f등으로 얻을 수 있는 §d보상§f을",
			"§f늘려주는 스탯입니다."
		]);
		$eventStat->getNamedTag()->setTag("event", new IntTag(1));
		$inv->getInventory()->setItem(11, $eventStat);

		$mineStat = ItemFactory::getInstance()->get(ItemIds::ENCHANTED_BOOK, 0, 1);
		$mineStat->setCustomName("§d광질 §f스탯\n현재 수치: {$this->getStat($player, self::STAT_MINE_FORTUNE)}");
		$mineStat->setLore([
			"§d광질 §f스탯은 §d광산§f에서",
			"§d광질§f을 할 경우에",
			"§f얻을 수 있는 §d광물§f을",
			"§f올려주는 스탯입니다."
		]);
		$mineStat->getNamedTag()->setTag("mine", new IntTag(1));
		$inv->getInventory()->setItem(13, $mineStat);

		$farmStat = ItemFactory::getInstance()->get(ItemIds::ENCHANTED_BOOK, 0, 1);
		$farmStat->setCustomName("§d농사 §f스탯\n현재 수치: {$this->getStat($player, self::STAT_FARM_FORTUNE)}");
		$farmStat->setLore([
			"§d농사 §f스탯은 §d섬§f이나 §d땅 §f등에서",
			"§d농사§f를 할 경우에",
			"§f얻을 수 있는 §d농작물§f을",
			"§f올려주는 스탯입니다."
		]);
		$farmStat->getNamedTag()->setTag("farm", new IntTag(1));
		$inv->getInventory()->setItem(15, $farmStat);

		$inv->send($player);
	}

	public static function convertStatName(string $stat) : string{
		switch($stat){
			case self::STAT_EVENT_FORTUNE:
				return "클리어 스탯";
			case self::STAT_FARM_FORTUNE:
				return "농사 스탯";
			case self::STAT_MINE_FORTUNE:
				return "광질 스탯";
			default:
				return "알 수 없음";
		}
	}

	public function onPlayerLevelUp(PlayerLevelUpEvent $event) : void{
		$player = $event->getPlayer();
		$after = $event->getAfter();

		if($after % 2 === 0){
			$this->addStatPoint($player, $stat = mt_rand(1, 2));
			OnixUtils::message($player, "레벨 업 하여 " . $stat . " 스탯포인트를 얻었습니다.");
		}
	}
}
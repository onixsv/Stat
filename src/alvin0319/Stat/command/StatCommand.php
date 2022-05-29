<?php

declare(strict_types=1);

namespace alvin0319\Stat\command;

use alvin0319\Stat\Stat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StatCommand extends Command{

	public function __construct(){
		parent::__construct("스탯", "스탯 명령어입니다.");
		$this->setPermission("stat.command");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!$sender instanceof Player){
			return false;
		}
		Stat::getInstance()->sendInv($sender);
		return true;
	}
}
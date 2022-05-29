<?php

declare(strict_types=1);

namespace alvin0319\Stat\form;

use alvin0319\Stat\Stat;
use OnixUtils\OnixUtils;
use pocketmine\form\Form;
use pocketmine\player\Player;
use function count;
use function is_array;
use function is_numeric;

class StatUpForm implements Form{
	/** @var Player */
	protected Player $player;
	/** @var string */
	protected string $stat;

	public function __construct(Player $player, string $stat){
		$this->player = $player;
		$this->stat = $stat;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "스탯 " . Stat::convertStatName($this->stat) . " 올리기",
			"content" => [
				[
					"type" => "slider",
					"text" => "현재 내 스탯 포인트: " . Stat::getInstance()->getStatPoint($this->player) . "\n슬라이더를 적당히 조절해서 스탯을 올려주세요.\n스탯이 올라갈 때 소모되는 비용은 스탯 x 5 입니다",
					"min" => 0,
					"max" => Stat::getInstance()->getStatPoint($this->player)
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if(!is_array($data) || count($data) !== 1){
			return;
		}
		$statPoint = $data[0] * 5;
		if(!is_numeric($statPoint)){
			return;
		}
		$nowPoint = Stat::getInstance()->getStatPoint($player);
		if($statPoint > $nowPoint){
			OnixUtils::message($player, "올리려는 스탯의 양이 내 스탯보다 적습니다. ({$statPoint} 필요, {$nowPoint} 소유)");
			return;
		}
		Stat::getInstance()->reduceStatPoint($player, (int) $statPoint);
		Stat::getInstance()->addStat($player, $this->stat, (int) $data[0]);
		OnixUtils::message($player, Stat::convertStatName($this->stat) . "을 {$data[0]}만큼 올렸습니다.");
	}
}
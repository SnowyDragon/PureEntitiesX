<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities\tile;

use pocketmine\level\Level;
use pocketmine\Player;
use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\tile\Spawnable;

class Spawner extends Spawnable{

	protected $entityId = -1;
	protected $spawnRange = 8;
	protected $maxNearbyEntities = 6;
	protected $requiredPlayerRange = 16;

	protected $delay = 0;

	protected $minSpawnDelay = 200;
	protected $maxSpawnDelay = 800;

	public function __construct(Level $level, CompoundTag $nbt){

		parent::__construct($level, $nbt);

		$this->loadNBT();
		$this->scheduleUpdate();
		PureEntities::logOutput("Spawner Created with EntityID of $this->entityId");
	}

	public function onUpdate() : bool{
		if($this->isClosed()){
			return false;
		}
		if($this->entityId === -1){
			PureEntities::logOutput("onUpdate Called with EntityID of -1");
			return false;
		}

		if($this->delay++ >= mt_rand($this->minSpawnDelay, $this->maxSpawnDelay)){
			$this->delay = 0;

			$list = [];
			$isValid = false;
			foreach($this->level->getEntities() as $entity){
				if($entity->distance($this) <= $this->requiredPlayerRange){
					if($entity instanceof Player){
						$isValid = true;
					}
					$list[] = $entity;
					break;
				}
			}

			if($isValid && count($list) <= $this->maxNearbyEntities){
				$y = $this->y;
				$x = $this->x + mt_rand(-$this->spawnRange, $this->spawnRange);
				$z = $this->z + mt_rand(-$this->spawnRange, $this->spawnRange);
				$pos = PureEntities::getSuitableHeightPosition($x, $y, $z, $this->level);
				$pos->y += Data::HEIGHTS[$this->entityId];
				$entity = PureEntities::create($this->entityId, $pos);
				if($entity != null){
					PureEntities::logOutput("Spawner: spawn $entity to $pos", PureEntities::NORM);
					$entity->spawnToAll();
				}
			}
		}
		return true;
	}

	public function saveNBT() : void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			parent::saveNBT();

			$this->namedtag->setByte(NBTConst::NBT_KEY_SPAWNER_IS_MOVABLE, 1);
			$this->namedtag->setShort(NBTConst::NBT_KEY_SPAWNER_DELAY, 0);
			$this->namedtag->setShort(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, $this->maxNearbyEntities);
			$this->namedtag->setShort(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, $this->maxSpawnDelay);
			$this->namedtag->setShort(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, $this->minSpawnDelay);
			$this->namedtag->setShort(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, $this->requiredPlayerRange);
			$this->namedtag->setShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_COUNT, 0);
			$this->namedtag->setShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE, $this->spawnRange);
			$this->namedtag->setInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId);
			$this->namedtag->setFloat(NBTConst::NBT_KEY_SPAWNER_DISPLAY_ENTITY_HEIGHT, 1);
			$this->namedtag->setFloat(NBTConst::NBT_KEY_SPAWNER_DISPLAY_ENTITY_SCALE, 1);
			$this->namedtag->setFloat(NBTConst::NBT_KEY_SPAWNER_DISPLAY_ENTITY_WIDTH, 0.5);
			$spawnData = new CompoundTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA, [new IntTag(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId)]);
			$this->namedtag->setTag($spawnData);
		}
	}

	public function loadNBT(): void{
		if(PluginConfiguration::getInstance()->getEnableNBT()){

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID)){
				$this->setSpawnEntityType($this->namedtag->getInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, -1, true));
			}

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE)){
				$this->spawnRange = $this->namedtag->getShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE, 8, true);
			}

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY)){
				$this->minSpawnDelay = $this->namedtag->getShort(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, 200, true);
			}

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY)){
				$this->maxSpawnDelay = $this->namedtag->getShort(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, 800, true);
			}

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES)){
				$this->maxNearbyEntities = $this->namedtag->getShort(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, 6, true);
			}

			if($this->namedtag->hasTag(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE)){
				$this->requiredPlayerRange = $this->namedtag->getShort(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, 16);
			}

			// TODO: add SpawnData: Contains tags to copy to the next spawned entity(s) after spawning. Any of the entity or
			// mob tags may be used. Note that if a spawner specifies any of these tags, almost all variable data such as mob
			// equipment, villager profession, sheep wool color, etc., will not be automatically generated, and must also be
			// manually specified (note that this does not apply to position data, which will be randomized as normal unless
			// Pos is specified. Similarly, unless Size and Health are specified for a Slime or Magma Cube, these will still
			// be randomized). This, together with EntityId, also determines the appearance of the miniature entity spinning
			// in the spawner cage. Note: this tag is optional: if it does not exist, the next spawned entity will use
			// the default vanilla spawning properties for this mob, including potentially randomized armor (this is true even
			// if SpawnPotentials does exist). Warning: If SpawnPotentials exists, this tag will get overwritten after the
			// next spawning attempt: see above for more details.
			if(!$this->namedtag->hasTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA)){
				$spawnData = new CompoundTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA, [new IntTag(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId)]);
				//$this->namedtag->setTag($spawnData);
			}

			// TODO: add SpawnCount: How many mobs to attempt to spawn each time. Note: Requires the MinSpawnDelay property to also be set.

			$this->saveNBT();
		}
	}

	public function setSpawnEntityType(int $entityId){
		PureEntities::logOutput("setSpawnEntityType called with EntityID of $entityId");
		$this->entityId = $entityId;
		if(PluginConfiguration::getInstance()->getEnableNBT()){
			$this->namedtag->setInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId);
			$spawnData = new CompoundTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA, [new IntTag(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId)]);
			$this->namedtag->setTag($spawnData);
		}
		$this->spawnToAll();
	}

	public function setMinSpawnDelay(int $minDelay){
		if($minDelay > $this->maxSpawnDelay){
			return;
		}

		$this->minSpawnDelay = $minDelay;
	}

	public function setMaxSpawnDelay(int $maxDelay){
		if($this->minSpawnDelay > $maxDelay){
			return;
		}

		$this->maxSpawnDelay = $maxDelay;
	}

	public function setSpawnDelay(int $minDelay, int $maxDelay){
		if($minDelay > $maxDelay){
			return;
		}

		$this->minSpawnDelay = $minDelay;
		$this->maxSpawnDelay = $maxDelay;
	}

	public function setRequiredPlayerRange(int $range){
		$this->requiredPlayerRange = $range;
	}

	public function setMaxNearbyEntities(int $count){
		$this->maxNearbyEntities = $count;
	}

	public function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId);
	}

}
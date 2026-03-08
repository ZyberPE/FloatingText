<?php

namespace FloatingText\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;

class FloatingTextEntity extends Entity{

    public function __construct(Location $location, ?CompoundTag $nbt = null){
        parent::__construct($location, $nbt);
        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible();
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(0.01, 0.01);
    }

    protected function getInitialDragMultiplier(): float{
        return 0.0;
    }

    protected function getInitialGravity(): float{
        return 0.0;
    }

    public static function getNetworkTypeId(): string{
        return "floating_text";
    }
}

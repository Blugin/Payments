<?php

/**
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin-team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 * @name PaymentSCashProvider
 * @api 4.0.0
 * @version 1.0.0
 * @main blugin\api\paymentpool\PaymentSCashProvider
 */

namespace blugin\api\paymentpool;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use SCash\SCash;

class PaymentSCashProvider extends PluginBase{
    private $falied = false;

    public function onLoad(){
        try{
            if(!class_exists(PaymentPool::class))
                throw new \RuntimeException("Could not load provider of 'SCash': PaymentPool missing");
            if(!class_exists(SCash::class))
                throw new \RuntimeException("Could not load provider of 'SCash': Target payment class missing");
        }catch(\RuntimeException $exception){
            $this->getServer()->getLogger()->error($exception->getMessage());
            $this->falied = true;
            return;
        }

        PaymentPool::getInstance()->registerProvider(new class() implements IPaymentProvider{
            public function getName() : string{
                return "scash";
            }

            public function getAll() : array{
                return SCash::runFunction()->db;
            }

            public function exists($player) : bool{
                if($player instanceof Player){
                    $player = $player->getName();
                }

                return isset(SCash::runFunction()->db[$player]);
            }

            public function create($player, float $value) : bool{
                if($this->exists($player))
                    return false;

                if($player instanceof Player){
                    $player = $player->getName();
                }

                SCash::runFunction()->db[$player] = (int) $value;
                return true;
            }

            public function get($player) : ?float{
                if(!$this->exists($player))
                    return null;

                return (float) SCash::runFunction()->getCash($player);
            }

            public function set($player, float $value) : void{
                if(!$this->exists($player))
                    return;

                SCash::runFunction()->setCash($player, (int) $value);
            }

            public function increase($player, float $value) : ?float{
                if(!$this->exists($player))
                    return null;

                SCash::runFunction()->addCash($player, (int) $value);
                return $this->get($player);
            }

            public function decrease($player, float $value) : ?float{
                if(!$this->exists($player))
                    return null;

                SCash::runFunction()->reduceCash($player, (int) $value);
                return $this->get($player);
            }
        }, ["xxx:scach"]);
    }

    public function onEnable(){
        if($this->falied)
            $this->setEnabled(false);
    }
}
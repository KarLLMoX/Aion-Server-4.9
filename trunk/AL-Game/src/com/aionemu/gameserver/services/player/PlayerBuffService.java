package com.aionemu.gameserver.services.player;

import com.aionemu.gameserver.model.Race;
import com.aionemu.gameserver.model.bonus_service.PlayersBonus;
import com.aionemu.gameserver.model.bonus_service.ServiceBuff;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.model.gameobjects.player.PlayerBonusTimeStatus;
import com.aionemu.gameserver.services.item.ItemService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

/**
 * Created by Ace on 31/07/2016.
 */
public class PlayerBuffService {

    private static ServiceBuff serviceBuff;
    private static PlayersBonus playersBonus;
    private static final Logger log = LoggerFactory.getLogger("GAMECONNECTION_LOG");

    public void enterWorld(Player player){
        player.setBonusTime(player.getCommonData().getBonusTime());
        player.setBonusTimeStatus();
        securityBuff(player);
        newPlayerBuff(player);
        returnPlayerBuff(player);
        addReturnStone(player);
    }

    private void securityBuff(Player player){
        //Service Security Buff.
        if (player.getMembership() == 0) {
            serviceBuff = new ServiceBuff(2);
            serviceBuff.applyEffect(player, 2);
        }
    }

    private void newPlayerBuff(Player player) {
        if (player.isNewPlayer()){
            playersBonus = new PlayersBonus(2);
            playersBonus.applyEffect(player, 2);
            log.info("Player " + player.getName() + " Receved Ascension Boost");
        } else {
            playersBonus = new PlayersBonus(1);
            playersBonus.endEffect(player, 1);
        }
    }

    private void returnPlayerBuff(Player player) {
        if (player.getBonusTime().getStatus() == PlayerBonusTimeStatus.RETURN) {
            playersBonus = new PlayersBonus(3);
            playersBonus.applyEffect(player, 3);
            player.setPlayersBonusId(3);
            log.info("Player " + player.getName() + " Receved Return Boost");
        } else {
            playersBonus = new PlayersBonus(1);
            playersBonus.endEffect(player, 1);
        }
    }

    public void addReturnStone(Player player){
        if (player.getLevel() >= 10 && player.getRace() == Race.ASMODIANS && player.getBonusTime().getStatus() == PlayerBonusTimeStatus.RETURN) {
            if (player.getInventory().getItemCountByItemId(164000336) > 0) {
                return;
            }
            ItemService.addItem(player, 164000336, 1); //Abbey Return Stone (30 days)
        }
        if (player.getLevel() >= 10 && player.getRace() == Race.ELYOS && player.getBonusTime().getStatus() == PlayerBonusTimeStatus.RETURN) {
            if (player.getInventory().getItemCountByItemId(164000335) > 0) {
                return;
            }
            ItemService.addItem(player, 164000335, 1); //Abbey Return Stone (30 days)
        }
    }

    public static final PlayerBuffService getInstance() {
        return SingletonHolder.instance;
    }

    private static class SingletonHolder {
        protected static final PlayerBuffService instance = new PlayerBuffService();
    }
}

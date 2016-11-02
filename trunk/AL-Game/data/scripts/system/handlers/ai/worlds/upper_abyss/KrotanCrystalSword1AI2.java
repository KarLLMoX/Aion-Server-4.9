/**
 * This file is part of Aion-Lightning <aion-lightning.org>.
 *
 *  Aion-Lightning is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Aion-Lightning is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details. *
 *  You should have received a copy of the GNU General Public License
 *  along with Aion-Lightning.
 *  If not, see <http://www.gnu.org/licenses/>.
 */
package ai.worlds.upper_abyss;

import java.util.concurrent.Future;

import com.aionemu.gameserver.ai2.AI2Actions;
import com.aionemu.gameserver.ai2.AIName;
import com.aionemu.gameserver.ai2.NpcAI2;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.network.aion.serverpackets.SM_DIALOG_WINDOW;
import com.aionemu.gameserver.network.aion.serverpackets.SM_SYSTEM_MESSAGE;
import com.aionemu.gameserver.utils.PacketSendUtility;
import com.aionemu.gameserver.utils.ThreadPoolManager;
import com.aionemu.gameserver.world.World;
import com.aionemu.gameserver.world.knownlist.Visitor;

/**
 * Spawn Spirit Fortress Upper Abyss Update 4.9
 * Author Phantom_KNA
 */
@AIName("krotansword1") // Npc: 702841
public class KrotanCrystalSword1AI2 extends NpcAI2 {

	private Future<?> taskSpawnSpirit;
	private int timeSpawnDelaySpirit = 30; //30 min 

	@Override
	public boolean onDialogSelect(final Player player, int dialogId, int questId, int extendedRewardIndex) {
		switch (dialogId) {
			case 10000:
				if (player.getInventory().getFirstItemByItemId(182215912) != null) {
					return false;
				}

				if (!this.timerStartSpawnSpirit(player)) {
					return false;
				}

				this.timerStartSpawnSpirit(player);

				break;
		}
		AI2Actions.deleteOwner(this);

		return true;
	}

	private boolean timerStartSpawnSpirit(Player player) {

		if ((this.taskSpawnSpirit != null) && !this.taskSpawnSpirit.isDone()) {
			return false;
		}

		this.taskSpawnSpirit = ThreadPoolManager.getInstance().schedule(new Runnable() {
			@Override
			public void run() {
				announceSpiritBoss();
				spawn(297689, 2075.919f, 1163.7726f, 2961.1465f, (byte) 32); // Spawn Krotan Spirit
			};
		}, ((this.timeSpawnDelaySpirit * 60) * 1000));
		
		spawn(805487, 2075.919f, 1163.7726f, 2961.1465f, (byte) 32); // Spawn Spirit Flag
		return true;
	}

	private void announceSpiritBoss() {
        World.getInstance().doOnAllPlayers(new Visitor<Player>() {
            @Override
            public void visit(Player player) {
				PacketSendUtility.sendPacket(player, new SM_SYSTEM_MESSAGE(1403142));
            }
        });
    }

    @Override
    protected void handleDialogStart(Player player) {
		if (player.getInventory().decreaseByItemId(182215912, 1)) { //Krotan Spirit Pendant ItemID: 182215912
            PacketSendUtility.sendPacket(player, new SM_DIALOG_WINDOW(getObjectId(), 10));
        }
        else {
            PacketSendUtility.sendPacket(player, new SM_DIALOG_WINDOW(getObjectId(), 27));
        }
    }
}

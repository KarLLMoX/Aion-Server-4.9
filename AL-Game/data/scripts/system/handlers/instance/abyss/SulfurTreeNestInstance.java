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
package instance.abyss;

import com.aionemu.commons.network.util.ThreadPoolManager;
import com.aionemu.gameserver.instance.handlers.GeneralInstanceHandler;
import com.aionemu.gameserver.instance.handlers.InstanceID;
import com.aionemu.gameserver.model.Race;
import com.aionemu.gameserver.model.flyring.FlyRing;
import com.aionemu.gameserver.model.gameobjects.Npc;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.model.templates.flyring.FlyRingTemplate;
import com.aionemu.gameserver.model.utils3d.Point3D;
import com.aionemu.gameserver.network.aion.serverpackets.SM_QUEST_ACTION;
import com.aionemu.gameserver.network.aion.serverpackets.SM_SYSTEM_MESSAGE;
import com.aionemu.gameserver.services.teleport.TeleportService2;
import com.aionemu.gameserver.utils.PacketSendUtility;
import com.aionemu.gameserver.world.WorldMapInstance;

import java.util.List;

/**
 * @author xTz
 */
@InstanceID(300060000)
public class SulfurTreeNestInstance extends GeneralInstanceHandler {

    private boolean isStartTimer = false;
    private long startTime;
    private boolean isInstanceDestroyed = false;
    private Race instanceRace;

    @Override
    public void onInstanceCreate(WorldMapInstance instance) {
        super.onInstanceCreate(instance);
        spawnRings();
        spawnGoldChest();
    }

    private void spawnRings() {
        FlyRing f1 = new FlyRing(new FlyRingTemplate("SULFUR_1", mapId,
                new Point3D(462.9394, 380.34888, 168.97256),
                new Point3D(462.9394, 380.34888, 174.97256),
                new Point3D(468.9229, 380.7933, 168.97256), 6), instanceId);
        f1.spawn();
    }

    @Override
    public boolean onPassFlyingRing(Player player, String flyingRing) {
        if (flyingRing.equals("SULFUR_1")) {
            if (!isStartTimer) {
                isStartTimer = true;
                PacketSendUtility.sendPacket(player, SM_SYSTEM_MESSAGE.STR_MSG_INSTANCE_START_IDABRE);
                startTime = System.currentTimeMillis();
                PacketSendUtility.sendPacket(player, new SM_QUEST_ACTION(0, 900));
                ThreadPoolManager.getInstance().schedule(new Runnable() {
                    @Override
                    public void run() {
                        despawnNpcs(getNpcs(214804));
                        despawnNpcs(getNpcs(700462));
                        despawnNpcs(getNpcs(700463));
                        despawnNpcs(getNpcs(700464));
                        despawnNpcs(getNpcs(701485));
                        despawnNpcs(getNpcs(701480));
                    }
                }, 900000);
            }
        }
        return false;
    }

    @Override
    public void onEnterInstance(Player player) {
        if (isStartTimer) {
            long time = System.currentTimeMillis() - startTime;
            if (time < 900000) {
                PacketSendUtility.sendPacket(player, new SM_QUEST_ACTION(0, 900 - (int) time / 1000));
            }
        }
    }

    private List<Npc> getNpcs(int npcId) {
        if (!isInstanceDestroyed) {
            return instance.getNpcs(npcId);
        }
        return null;
    }

    private void despawnNpcs(List<Npc> npcs) {
        for (Npc npc : npcs) {
            npc.getController().onDelete();
        }
    }

    private void spawnGoldChest() {
        final int chestId = instanceRace == Race.ELYOS ? 701480 : 701485;
        spawn(chestId, 459.41647f, 499.55997f, 163.16049f, (byte) 40);
    }

    @Override
    public void onInstanceDestroy() {
        isInstanceDestroyed = true;
    }

    @Override
    public void onPlayerLogOut(Player player) {
        TeleportService2.moveToInstanceExit(player, mapId, player.getRace());
    }
}

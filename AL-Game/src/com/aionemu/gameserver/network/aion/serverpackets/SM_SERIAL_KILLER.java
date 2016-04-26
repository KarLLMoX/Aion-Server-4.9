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
package com.aionemu.gameserver.network.aion.serverpackets;

import com.aionemu.gameserver.GameServer;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.network.aion.AionConnection;
import com.aionemu.gameserver.network.aion.AionServerPacket;

import java.util.Collection;

/**
 * @author Source & xTz
 */
public class SM_SERIAL_KILLER extends AionServerPacket {

	private int type;
    private int debuffLvl;
    private Collection<Player> players;
    private Player player;


    public SM_SERIAL_KILLER(boolean showMsg, int debuffLvl) {
        this.type = showMsg ? 1 : 0;
        this.debuffLvl = debuffLvl;
    }

    public SM_SERIAL_KILLER(Collection<Player> players, boolean intruderRadar) {
        this.type = intruderRadar ? 5 : 4;
        this.players = players;
        GameServer.log.info("Sending SM_SERIAL_KILLER Type: "+type+ " Players Size: "+players.size());
    }

    public SM_SERIAL_KILLER(Player player, boolean isProtector, boolean broadcastPacket, int buffLvl){
        this.player = player;
        if (broadcastPacket){
            this.type = isProtector ? 9 : 6;
        }else{
            this.type = isProtector ? 8 : 1;
        }
        this.debuffLvl = buffLvl;
    }
    
    @Override
    protected void writeImpl(AionConnection con) {
        writeD(type);
        writeD(1); //0x01
        writeD(1); //0x01
    	switch (type) {
            //case 0: // Conqueror Without Msg (not used)
            case 1: // Conqueror With Msg
            case 6: //goes to other players Conquerer
            //case 7: // Protector Without msg  (not used)           
            case 8: // Protector With Msg
            case 9: // goes to other players Protector
                writeH(1); //size ?!
                writeD(debuffLvl); //lvl
                writeD(type == 9 || type == 6 ? player.getObjectId() : 0);
                break;            
            case 5:
            case 4: // Serial Killer
                writeH(players.size());
                for (Player player : players) {
                    writeD(player.getSKInfo().getRank());
                    writeD(player.getObjectId());
                    writeD(0x01); // unk
                    writeD(player.getAbyssRank().getRank().getId());
                    writeH(player.getLevel());
                    writeF(player.getX());
                    writeF(player.getY());
                    writeS(player.getName(), 134);
                    writeH(4); // unk
                }
                break;  
/*            case 5: // Intruder Radar
                writeH(players.size());
                for (Player player : players) 
                {
                	
                }
                break;  
*/        }
    }
}

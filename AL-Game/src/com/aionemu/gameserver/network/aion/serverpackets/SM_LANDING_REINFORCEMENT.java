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

import com.aionemu.gameserver.network.aion.AionConnection;
import com.aionemu.gameserver.network.aion.AionServerPacket;

/**
 * @author
 */
public class SM_LANDING_REINFORCEMENT extends AionServerPacket {

    @Override
    protected void writeImpl(AionConnection con) {
        //Elyos
        writeD(300000); //Quest Completion
        writeD(110000); //Fortress Occupation
        writeD(24000); //Artifact Occupation
        writeD(30000); //Base Occupation
        writeD(100000); //Facility Control
        writeD(40000); //Monument Control
        writeD(50000); //Commander Defense
        //Asmodians
        writeD(185280); //Quest Completion
        writeD(0); //Fortress Occupation
        writeD(8000); //Artifact Occupation
        writeD(12000); //Base Occupation
        writeD(100000); //Facility Control
        writeD(0); //Monument Control
        writeD(0); //Commander Defense
    }
}

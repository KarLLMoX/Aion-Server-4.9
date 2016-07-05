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
package ai.worlds.reshanta;

/**
 * @author Falke_34
 */

import com.aionemu.gameserver.ai2.AIName;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.skillengine.SkillEngine;

import ai.ActionItemNpcAI2;

@AIName("landing_energy")
public class LandingEnergyAI2 extends ActionItemNpcAI2 {

	@Override
    protected void handleUseItemFinish(Player player) {
        switch (getNpcId()) {
            case 883956:
    		case 883960:
                SkillEngine.getInstance().getSkill(player, 22742, 1, player).useWithoutPropSkill(); //Wing Recovery
                break;
			case 883957:
			case 883961:
                SkillEngine.getInstance().getSkill(player, 22741, 1, player).useWithoutPropSkill(); //Boost HP
                break;
			case 883958:
			case 883962:
                SkillEngine.getInstance().getSkill(player, 22740, 1, player).useWithoutPropSkill(); //Boost Interpersonal Attack
                break;
			case 883959:
			case 883963:
                SkillEngine.getInstance().getSkill(player, 22739, 1, player).useWithoutPropSkill(); //Boost Interpersonal Defense
                break;
        }
	}
}
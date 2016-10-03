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
package quest.abyssal_wind;

import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;

/**
 * @author 
 */
public class _25400PontekanesPlight extends QuestHandler {

    public static final int questId = 25400;

    public _25400PontekanesPlight() {
        super(questId);
    }

    @Override
    public void register() {
		qe.registerQuestNpc(805356).addOnQuestStart(questId); //Pontekai
		qe.registerQuestNpc(805357).addOnTalkEvent(questId); //Damia
		qe.registerQuestNpc(805358).addOnTalkEvent(questId); //Batei
    }

	@Override
    public boolean onKillEvent(QuestEnv env) {
		return false;

    }
	
    @Override
    public boolean onDialogEvent(QuestEnv env) {
		return false;
   	
    }
}

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
package quest.important_mission;
 
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
 
/**
 * @author FrozenKiller
 */
 
public class _29643SpecialMissionRequest extends QuestHandler {

    private final static int questId = 29643;

    public _29643SpecialMissionRequest() {
        super(questId);
    }    
	
    @Override
    public void register() {
        qe.registerQuestNpc(804662).addOnQuestStart(questId);
        qe.registerQuestNpc(804662).addOnTalkEvent(questId); // Melanka
		qe.registerQuestNpc(799225).addOnTalkEvent(questId); // Richelle
    }
	
	
	@Override
    public boolean onDialogEvent(final QuestEnv env) {
        final Player player = env.getPlayer();
        int targetId = env.getTargetId();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 804662) { // Melanka
                if (dialog == DialogAction.QUEST_SELECT) {
                    return sendQuestDialog(env, 4762);
                } else {
                    return sendQuestStartDialog(env);
                }
            }
		} else if (qs.getStatus() == QuestStatus.START) {
			if (targetId == 799225) { // Richelle
				if (dialog == DialogAction.QUEST_SELECT) {
					qs.setStatus(QuestStatus.REWARD);
					updateQuestStatus(env);
					return sendQuestDialog(env, 5);
				}
			}
        } else if (qs.getStatus() == QuestStatus.REWARD) {
			if (targetId == 799225) { // Richelle
                return sendQuestEndDialog(env);
			}
		}
        return false;
    }
}

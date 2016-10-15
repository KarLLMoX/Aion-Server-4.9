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
package quest.crafting;

import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;

/**
 * @author Gigi,Modifly by Newlives@aioncore 29-1-2015
 * @rework FrozenKiller
 */
public class _29001ExpertEssencetappingExpert extends QuestHandler {

    private final static int questId = 29001;

    public _29001ExpertEssencetappingExpert() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(204096).addOnQuestStart(questId); // Latatusk
        qe.registerQuestNpc(204096).addOnTalkEvent(questId); 
        qe.registerQuestNpc(798800).addOnTalkEvent(questId); // Vidar
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        final Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();
        int targetId = env.getTargetId();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 204096) { // Latatusk
            	switch (dialog) {
					case QUEST_SELECT: {
						return sendQuestDialog(env, 1011);
					}
					case ASK_QUEST_ACCEPT: {
						return sendQuestDialog(env, 4);
					}
					case QUEST_ACCEPT_1: {
						giveQuestItem(env, 182207141, 1);
						return sendQuestStartDialog(env);
					}
					case QUEST_REFUSE_1: {
						return sendQuestDialog(env, 1004);
					}
				default:
					break;
            		
            	}
            }
        } else if (qs.getStatus() == QuestStatus.START) { 
            if (targetId == 204052) { // Vidar
                switch (env.getDialog()) {
                	case QUEST_SELECT: {
                		return sendQuestDialog(env, 2375);
                	}
                	case SELECT_QUEST_REWARD: {
                		qs.setStatus(QuestStatus.REWARD);
                		updateQuestStatus(env);
                		return sendQuestDialog(env, 5);
                	}
				default:
					break;
                }
            }
        } else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 204052) { // Vidar
            	player.getSkillList().addSkill(player, 30002, 400);
            	removeQuestItem(env, 182207141, 1);
            	return sendQuestEndDialog(env);
            }
        }
        return false;
    }
}

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
package quest.wisplight_abbey;

import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;

/**
 * @author FrozenKiller
 */
public class _29601InstructionOnInstructors extends QuestHandler {

    private final static int questId = 29601;

    public _29601InstructionOnInstructors() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(804662).addOnQuestStart(questId);
        qe.registerQuestNpc(804662).addOnTalkEvent(questId); // Janete
        qe.registerQuestNpc(805305).addOnTalkEvent(questId); // Brunk
		qe.registerQuestNpc(804663).addOnTalkEvent(questId); // Simona
		qe.registerQuestNpc(804664).addOnTalkEvent(questId); // Christian
		qe.registerQuestNpc(804665).addOnTalkEvent(questId); // Letania
		qe.registerQuestNpc(804666).addOnTalkEvent(questId); // Pascal
    }
	
	@Override
    public boolean onDialogEvent(final QuestEnv env) {
        final Player player = env.getPlayer();
        int targetId = env.getTargetId();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();

		if (qs == null || qs.getStatus() == QuestStatus.NONE || qs.canRepeat()) {
			if (targetId == 804662) { // Janete
			   if (env.getDialog() == DialogAction.QUEST_SELECT) {
				   return sendQuestDialog(env, 4762);
			   } else {
				   return sendQuestStartDialog(env);
			   }
			}
		} else if (qs.getStatus() == QuestStatus.START) {
			int var = qs.getQuestVarById(0);
			if (targetId == 805305) { // Brunk
				switch (dialog) {
					case QUEST_SELECT: {
						if (var == 0) {
							return sendQuestDialog(env, 1011);
						} 
					}
					case SETPRO1: {
						qs.setQuestVar(1);
                        updateQuestStatus(env);
						return closeDialogWindow(env);
					}
				default:
					break;
				}
			} else if (targetId == 804663) { // Simona
				switch (dialog) {
					case QUEST_SELECT: {
						if (var == 1) {
							return sendQuestDialog(env, 1352);
						} 
					}
					case SETPRO2: {
						qs.setQuestVar(2);
                        updateQuestStatus(env);
						return closeDialogWindow(env);
					}
				default:
					break;
				}
			} else if (targetId == 804664) { // Christian
				switch (dialog) {
					case QUEST_SELECT: {
						if (var == 2) {
							return sendQuestDialog(env, 1352);
						} 
					}
					case SETPRO2: {
						qs.setQuestVar(2);
                        updateQuestStatus(env);
						return closeDialogWindow(env);
					}
				default:
					break;
				}
			} else if (targetId == 804665) { // Letania
				switch (dialog) {
					case QUEST_SELECT: {
						if (var == 3) {
							return sendQuestDialog(env, 1693);
						} 
					}
					case SET_SUCCEED: {
						return defaultCloseDialog(env, 2, 3, true, false); // Reward
					}
				default:
					break;
				}
			} 
        } else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 804666) { // Pascal
                if (dialog == DialogAction.USE_OBJECT) {
                    return sendQuestDialog(env, 10002);
                } else {
                    return sendQuestEndDialog(env);
                }
            }
        }
        return false;
	}
}

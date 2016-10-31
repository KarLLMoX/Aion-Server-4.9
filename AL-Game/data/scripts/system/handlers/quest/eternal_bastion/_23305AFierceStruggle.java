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
package quest.eternal_bastion;

import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;

/**
 * @author FrozenKiller
 *
 */
public class _23305AFierceStruggle extends QuestHandler {

    private final static int questId = 23305;

    public _23305AFierceStruggle() {
        super(questId);
    }

    public void register() {
        qe.registerQuestNpc(804927).addOnQuestStart(questId);
        qe.registerQuestNpc(804927).addOnTalkEvent(questId); //Bastan
        qe.registerQuestNpc(801280).addOnTalkEvent(questId); //Ludvarr
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();
        int targetId = env.getTargetId();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 804927) { //Bastan
            	switch (dialog) {
            		case QUEST_SELECT: {
            			return sendQuestDialog(env, 1011);
            		}
            		case ASK_QUEST_ACCEPT: {
            			return sendQuestDialog(env, 4);
            		}
            		case QUEST_ACCEPT_SIMPLE: {
            			return sendQuestStartDialog(env);
            		}
            		case QUEST_REFUSE_SIMPLE: {
            			return closeDialogWindow(env);
            		}
				default:
					break;
            	}
            }
        } else if (qs.getStatus() == QuestStatus.START) {
            switch (targetId) {
            	case 801280: { //Ludvarr
            		switch (dialog) {
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
            }
        } else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 801280) { // Ludvarr
                if (dialog == DialogAction.SELECT_QUEST_REWARD) {
                    return sendQuestDialog(env, 5);
                } else {
                    return sendQuestEndDialog(env);
                }
            }
        }
        return false;
    }
}

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
package quest.beluslan;

import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
import com.aionemu.gameserver.services.QuestService;

/**
 * @author FrozenKiller
 */

public class _24151ReclaimingtheDamned extends QuestHandler {

    private final static int questId = 24151;
    private final static int[] mob_ids = {213044, 213045, 214092, 214093};

    public _24151ReclaimingtheDamned() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(204715).addOnQuestStart(questId);
        qe.registerQuestNpc(204715).addOnTalkEvent(questId); // Grundt
        qe.registerQuestNpc(204801).addOnTalkEvent(questId); // Gigrite
        for (int mob_id : mob_ids) {
            qe.registerQuestNpc(mob_id).addOnKillEvent(questId);
        }
    }


    @Override
    public boolean onDialogEvent(final QuestEnv env) {
        final Player player = env.getPlayer();
        int targetId = env.getTargetId();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 204715) { // Grundt
            	switch (dialog) {
        			case QUEST_SELECT: {
        				return sendQuestDialog(env, 1011);
        			}
        			case ASK_QUEST_ACCEPT: {
        				return sendQuestDialog(env, 4);
        			}
        			case QUEST_ACCEPT_1: {
        				QuestService.startQuest(env, 0);
                        qs.setQuestVarById(5, 1);
                        updateQuestStatus(env);
                        return closeDialogWindow(env);
        			}
        			case QUEST_REFUSE_1: {
        				return sendQuestDialog(env, 1004);
        			}
				default:
					break;
        	}
        }
        } else if (qs.getStatus() == QuestStatus.START) {
        	int var = qs.getQuestVarById(0);
            if (targetId == 204801) { // Gigrite
                switch (dialog) {
                    case QUEST_SELECT: {
                    	if (var == 5) {
                    		return sendQuestDialog(env, 2375);
                    	}
                    	return sendQuestDialog(env, 1352);
                    }
                    case SETPRO1: {
                        qs.setQuestVar(0);
                        updateQuestStatus(env);
                        return closeDialogWindow(env);
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
            if (targetId == 204801) { // Gigrite
            	if (dialog == DialogAction.SELECT_QUEST_REWARD) {
            		return sendQuestDialog(env, 5);
            	} else {
            		return sendQuestEndDialog(env);
            	}
            }
        }
        return false;
    }

    @Override
    public boolean onKillEvent(QuestEnv env) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        if (qs == null || qs.getStatus() != QuestStatus.START) {
            return false;
        }

        int var = qs.getQuestVarById(0);

        if (var < 5) {
            return defaultOnKillEvent(env, mob_ids, var, var + 1); // 0 - 5
        }
        return false;
    }
}
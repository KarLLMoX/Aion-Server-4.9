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
package quest.inggison;

import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;

/**
 * @author Leunam
 * @rework FrozenKiller
 */
public class _11107ComfortisaBox extends QuestHandler {

    private final static int questId = 11107;
    private final static int[] npc_ids = {798963, 296489, 296490, 296491};

    public _11107ComfortisaBox() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(798963).addOnQuestStart(questId);
        for (int npc_id : npc_ids) {
            qe.registerQuestNpc(npc_id).addOnTalkEvent(questId);
        }
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        final Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
		DialogAction dialog = env.getDialog();
		int targetId = env.getTargetId();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
			if (targetId == 798963) {
				switch (dialog) {
					case QUEST_SELECT: {
						return sendQuestDialog(env, 1011);
					}
					case QUEST_ACCEPT_1: {
						if (giveQuestItem(env, 182206783, 3)) {
							return sendQuestStartDialog(env);
						} else {
							return true;
						}
					}
					default:
						return sendQuestStartDialog(env);
                }
            }
        } else if (qs.getStatus() == QuestStatus.START) {
			int var = qs.getQuestVarById(0);
			if (targetId == 296489) {
				switch (dialog) {
					case QUEST_SELECT:
						if (var == 0) {
							return sendQuestDialog(env, 1352);
						}
					case SETPRO1:
						if (var == 0) {
							removeQuestItem(env, 182206783, 1);
							qs.setQuestVarById(0, var + 1);
							updateQuestStatus(env);
							return closeDialogWindow(env);
						}
						default:
						break;
				}
			} else if (targetId == 296490) {
				switch (dialog) {
					case QUEST_SELECT:
						if (var == 1) {
							return sendQuestDialog(env, 1693);
						}
					case SETPRO2:
						if (var == 1) {
							removeQuestItem(env, 182206783, 1);
							qs.setQuestVarById(0, var + 1);
							updateQuestStatus(env);
							return closeDialogWindow(env);
						}
					default:
						break;
				}
			} else if (targetId == 296491) {
				switch (dialog) {
					case QUEST_SELECT:
						if (var == 2) {
							return sendQuestDialog(env, 2034);
						}
					case SETPRO3:
						if (var == 2) {
							removeQuestItem(env, 182206783, 1);
							qs.setQuestVarById(0, var + 1);
							qs.setStatus(QuestStatus.REWARD);
							updateQuestStatus(env);
							return closeDialogWindow(env);
						}
					default:
						break;
				}
			}
		} else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 798963) {
				switch (dialog) {
					case USE_OBJECT: {
						return sendQuestDialog(env, 2375);
					}
					case SELECT_QUEST_REWARD: {
						return sendQuestDialog(env, 5);
					}
					default:
						return sendQuestEndDialog(env);
                }
            }
		}
        return false;
    }
}

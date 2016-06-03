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
package quest.daevation;

import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
import com.aionemu.gameserver.services.QuestService;

/**
 * @author Falke_34
 * @reworked FrozenKiller
 */
public class _15301TwoStepsCloser extends QuestHandler {

    private final static int questId = 15301;

    public _15301TwoStepsCloser() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerOnLevelUp(questId);
        qe.registerQuestNpc(805327).addOnQuestStart(questId); //Rike
        qe.registerQuestNpc(805327).addOnTalkEvent(questId); //Rike
        qe.registerQuestNpc(805328).addOnTalkEvent(questId); //Epaion
        qe.registerQuestNpc(805330).addOnTalkEvent(questId); //Potencia
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();
        int targetId = env.getTargetId();
		int var = qs.getQuestVarById(0);
		
        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 805327) {
				switch (dialog) {
                    case QUEST_SELECT: {
                        return sendQuestDialog(env, 4762);
                    }
                    case QUEST_ACCEPT_1:
                    case QUEST_ACCEPT_SIMPLE:
						QuestService.startQuest(env);
                        return sendQuestDialog(env, 1003);
					case QUEST_REFUSE_1:
					case QUEST_REFUSE_SIMPLE:
						return sendQuestDialog(env, 1004);
				default:
					break;
                }
            }
        } else if (qs.getStatus() == QuestStatus.START) {
            if (targetId == 805328) {
				if (var == 0) {
					switch (dialog) {
						case QUEST_SELECT: {
							return sendQuestDialog(env, 1011);
						}
						case SETPRO1: {
							changeQuestStep(env, 0, 1, false);
							return closeDialogWindow(env);
						}
					default:
						break;
					}
				} else {
					switch (dialog) {
						case USE_OBJECT: {
							return sendQuestDialog(env, 1352);
						}
						case CHECK_USER_HAS_QUEST_ITEM: {
							if (QuestService.collectItemCheck(env, true)) {
								changeQuestStep(env, 1, 2, false);
								return sendQuestDialog(env, 1693);
							} else {
								return sendQuestDialog(env, 10001);
							}
						}
						case SET_SUCCEED: {
							changeQuestStep(env, 2, 3, true);
							return closeDialogWindow(env);
						}
					default:
						break;
					}
				}
			}
		} else if (qs.getStatus() == QuestStatus.REWARD) {
			if (targetId == 805327) {
				return sendQuestEndDialog(env);
            }
        }
    return false;
    }
}
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
package quest.reshanta;

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
public class _1851DispatchedShugoCraftsmen extends QuestHandler {

    private final static int questId = 1851;

    public _1851DispatchedShugoCraftsmen() {
        super(questId);
    }

    public void register() {
        qe.registerQuestNpc(278533).addOnQuestStart(questId);
        qe.registerQuestNpc(278533).addOnTalkEvent(questId); //Rentia
        qe.registerQuestNpc(279025).addOnTalkEvent(questId); //Guuminerk
        qe.registerQuestNpc(279036).addOnTalkEvent(questId); //Muirunerk
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();
        int targetId = env.getTargetId();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 278533) { //Rentia
            	switch (dialog) {
            		case QUEST_SELECT: {
            			return sendQuestDialog(env, 1011);
            		}
            		case ASK_QUEST_ACCEPT: {
            			return sendQuestDialog(env, 4);
            		}
            		case QUEST_ACCEPT_1: {
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
            switch (targetId) {
            	case 279025: { //Guuminerk
            		switch (dialog) {
            			case QUEST_SELECT: {
           					return sendQuestDialog(env, 1352);
            			}
            			case SETPRO1: {
            				qs.setQuestVar(1);
            				updateQuestStatus(env);
            				return closeDialogWindow(env);
            			}
            			default:
            				break;
            		}
            	}
            	case 279036: { //Muirunerk
            		switch (dialog) {
        				case QUEST_SELECT: {
        					return sendQuestDialog(env, 1693);
        				}
        				case SETPRO2: {
        					qs.setQuestVar(2);
        					qs.setStatus(QuestStatus.REWARD);
                            updateQuestStatus(env);
        					return closeDialogWindow(env);
        				}
					default:
						break;
        			}
            	}
            }
        } else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 278533) { //Rentia
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

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

/**
 * @author FrozenKiller
 */

public class _24152ThroughtheLookingGlass extends QuestHandler {

    private final static int questId = 24152;

    public _24152ThroughtheLookingGlass() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(204768).addOnQuestStart(questId);
        qe.registerQuestNpc(204768).addOnTalkEvent(questId); // Sleipnir
        qe.registerQuestNpc(204739).addOnTalkEvent(questId); // Baugi
        qe.registerQuestNpc(802364).addOnTalkEvent(questId); // Dojer
        qe.addHandlerSideQuestDrop(questId, 213739, 182215461, 1, 100);
    }


    @Override
    public boolean onDialogEvent(final QuestEnv env) {
        final Player player = env.getPlayer();
        int targetId = env.getTargetId();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 204768) { // Sleipnir
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
            	case 204739: {	// Baugi
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
            	case 802364: { //Dojer
            		switch (dialog) {
        				case QUEST_SELECT: {
        					return sendQuestDialog(env, 2375);
        				}
        				case CHECK_USER_HAS_QUEST_ITEM_SIMPLE: {
        					if (player.getInventory().getItemCountByItemId(182215461) == 1) {
        						qs.setQuestVar(2);
        						qs.setStatus(QuestStatus.REWARD);
        						updateQuestStatus(env);
        						removeQuestItem(env, 182215461, 1);
        						return sendQuestDialog(env, 5);
        					} else {
        						return closeDialogWindow(env);
        					}
        				}
        			default:
        				break;
            		}
            	}
            }
        } else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 802364) { // Dojer
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
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
public class _19003ExpertAethertappingExpert extends QuestHandler {

    private final static int questId = 19003;

    public _19003ExpertAethertappingExpert() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(203782).addOnQuestStart(questId);
        qe.registerQuestNpc(203782).addOnTalkEvent(questId); //Jhaelas
        qe.registerQuestNpc(203700).addOnTalkEvent(questId); //Fasimedes
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        final Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();
        
        int targetId = env.getTargetId();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 203782) { //Jhaelas
            	switch (dialog) {
        			case QUEST_SELECT: {
        				return sendQuestDialog(env, 1011);
        			}
        			case ASK_QUEST_ACCEPT: {
        				return sendQuestDialog(env, 4);
        			}
        			case QUEST_ACCEPT_1: {
        				giveQuestItem(env, 182206128, 1);
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
                case 203700: { //Fasimedes
                    switch (dialog) {
                        case QUEST_SELECT:
                            qs.setStatus(QuestStatus.REWARD);
                            updateQuestStatus(env);
                            return sendQuestDialog(env, 2375);
					default:
						break;
                    }
                }
            }
        } else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 203700) { //Fasimedes
                if (env.getDialogId() == DialogAction.SELECT_QUEST_REWARD.id()) {
                    return sendQuestDialog(env, 5);
                } else {
                    return sendQuestEndDialog(env);
                }
            }
        }
        return false;
    }
}

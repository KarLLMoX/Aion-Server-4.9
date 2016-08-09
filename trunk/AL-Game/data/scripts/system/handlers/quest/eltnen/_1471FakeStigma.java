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
package quest.eltnen;

import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;

/**
 * @author MrPoke remod By Xitanium
 * @rework FrozenKiller
 */
public class _1471FakeStigma extends QuestHandler {

    private final static int questId = 1471;

    public _1471FakeStigma() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(203991).addOnQuestStart(questId); // Dionera
        qe.registerQuestNpc(203991).addOnTalkEvent(questId); // Dionera
        qe.registerQuestNpc(203703).addOnTalkEvent(questId); // Likesan
        qe.registerQuestNpc(798024).addOnTalkEvent(questId); // Kierunerk
        qe.registerQuestNpc(798321).addOnTalkEvent(questId); // Koruchinerk
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        Player player = env.getPlayer();
		QuestState qs = player.getQuestStateList().getQuestState(questId);
		int targetId = env.getTargetId();
		DialogAction dialog = env.getDialog();
		
        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
			if (targetId == 203991) {// Dionera
				if (env.getDialog() == DialogAction.QUEST_SELECT) {
					return sendQuestDialog(env, 1011);
				} else {
					return sendQuestStartDialog(env);
                }
            }
		} else if (qs.getStatus() == QuestStatus.START) {
			int var = qs.getQuestVarById(0);
			if (targetId == 203703) { //Likesan
				switch (dialog) {
					case QUEST_SELECT: {
						if (var == 0) {
							return sendQuestDialog(env, 1352);
						} else if (var == 3) {
							return sendQuestDialog(env, 2375);
						}
					}
					case SETPRO1: {
						return defaultCloseDialog(env, 0, 1);
					}
					case SELECT_QUEST_REWARD: {
						qs.setStatus(QuestStatus.REWARD);
						updateQuestStatus(env);
						return sendQuestEndDialog(env);
					}
					default:
						break;
				}
            } else if (targetId == 798024) { //Kierunerk
				switch (dialog) {
					case QUEST_SELECT: {
						return sendQuestDialog(env, 1693);
					}
					case SETPRO2: {
						return defaultCloseDialog(env, 1, 2);
					}
					default:
						break;
				}
			} else if (targetId == 798321) { //Koruchinerk
				switch (dialog) {
					case QUEST_SELECT: {
						return sendQuestDialog(env, 2034);
					}
					case SETPRO3: {
						return defaultCloseDialog(env, 2, 3);
					}
					default:
						break;
				}
			} 
		} else if (qs.getStatus() == QuestStatus.REWARD) {
			return sendQuestEndDialog(env);
		}
		return false;
	}
}
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
import com.aionemu.gameserver.services.teleport.TeleportService2;

/**
 * @author Balthazar
 * @rework FrozenKiller
 */
public class _1482ATeleportationAdventure extends QuestHandler {

    private final static int questId = 1482;

    public _1482ATeleportationAdventure() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(203919).addOnQuestStart(questId);
        qe.registerQuestNpc(203919).addOnTalkEvent(questId);
        qe.registerQuestNpc(203337).addOnTalkEvent(questId);
    }

    @Override
    public boolean onDialogEvent(QuestEnv env) {
        final Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
		int targetId = env.getTargetId();
		DialogAction dialog = env.getDialog();

        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 203919) {
                if (env.getDialog() == DialogAction.QUEST_SELECT) {
                    return sendQuestDialog(env, 4762);
                } else {
                    return sendQuestStartDialog(env);
                }
            }
        } else if (qs.getStatus() == QuestStatus.START) {
			int var = qs.getQuestVarById(0);
            if (targetId == 203337) {
				switch (dialog) {
                    case QUEST_SELECT: {
						if (var == 0) {
                            return sendQuestDialog(env, 1011);
						} else if (var == 1) {
							return sendQuestDialog(env, 1352);
						} else if (var == 2) {
							return sendQuestDialog(env, 1693);
						}
					}	
					case CHECK_USER_HAS_QUEST_ITEM: {
                        if (player.getInventory().getItemCountByItemId(182201399) >= 3) {
							qs.setQuestVar(2);
							updateQuestStatus(env);
							removeQuestItem(env, 182201399, 3);
							return sendQuestDialog(env, 10000);
						} else {
							return sendQuestDialog(env, 10001);
						}
					}
                    case SETPRO1: {
                        qs.setQuestVarById(0, qs.getQuestVarById(0) + 1);
                        updateQuestStatus(env);
                        return closeDialogWindow(env);
                    }
                    case SETPRO3: {
                        qs.setQuestVar(3);
                        qs.setStatus(QuestStatus.REWARD);
                        updateQuestStatus(env);
                        TeleportService2.teleportTo(player, 220020000, 1, 117, 2212, 589, (byte) 11);
                        return closeDialogWindow(env);
                    }
                    default:
						return sendQuestStartDialog(env);
				}
			}
        } else if (qs.getStatus() == QuestStatus.REWARD) {
            if (targetId == 203337) {
				switch (dialog) {
					case USE_OBJECT: {
						return sendQuestDialog(env, 10002);
						
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

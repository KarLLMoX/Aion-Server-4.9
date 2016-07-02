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
package quest.fatebound_abbey;

import com.aionemu.gameserver.model.ChatType;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.network.aion.serverpackets.SM_MESSAGE;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
import com.aionemu.gameserver.utils.PacketSendUtility;

/**
 * @author FrozenKiller, Falke_34
 */
public class _29623RightTimeForAWar extends QuestHandler {

	private final static int questId = 29623;
	
	public _29623RightTimeForAWar() {
		super(questId);
	}
	
	@Override
	public void register() {
		qe.registerQuestNpc(804666).addOnQuestStart(questId);
		qe.registerQuestNpc(804666).addOnTalkEvent(questId);
		qe.registerQuestNpc(802353).addOnTalkEvent(questId);
	}
	
	@Override
	public boolean onDialogEvent(QuestEnv env) {
		final Player player = env.getPlayer();
		int targetId = env.getTargetId();
		QuestState qs = player.getQuestStateList().getQuestState(questId);
		DialogAction dialog = env.getDialog();
		
		if (qs == null || qs.getStatus() == QuestStatus.NONE || qs.canRepeat()) {
			if (targetId == 804666) {
				switch (dialog) {
					case QUEST_SELECT:
						if (player.getInventory().getItemCountByItemId(164000336) >= 1) { //Abbey Return Stone.
							return sendQuestDialog(env, 4762);
						} else {
							PacketSendUtility.broadcastPacket(player, new SM_MESSAGE(player, "You must have <Abbey Return Stone>", ChatType.BRIGHT_YELLOW_CENTER), true);
							return true;
						}
					case QUEST_ACCEPT_1:
					case QUEST_ACCEPT_SIMPLE:
						return sendQuestStartDialog(env);
					case QUEST_REFUSE_SIMPLE:
				        return closeDialogWindow(env);
				default:
					break;
				}
			}
		} else if (qs.getStatus() == QuestStatus.START) {
			if (targetId == 802353) {
                switch (dialog) {
					case QUEST_SELECT: {
						return sendQuestDialog(env, 10002);
					} 
					case SELECT_QUEST_REWARD: {
						qs.setQuestVar(1);
						qs.setStatus(QuestStatus.REWARD);
						updateQuestStatus(env);
						return closeDialogWindow(env);
					}
					default:
						break;
				}
			}
		} else if (qs.getStatus() == QuestStatus.REWARD) {
		    if (targetId == 802353) {
			    switch (dialog) {
					case SELECT_QUEST_REWARD: {
						return sendQuestDialog(env, 5);
					} default:
						return sendQuestEndDialog(env);
				}
		    }
		}
		return false;
	}
}

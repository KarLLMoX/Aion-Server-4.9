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
//import com.aionemu.gameserver.model.gameobjects.Item;
import com.aionemu.gameserver.model.gameobjects.player.Player;
//import com.aionemu.gameserver.network.aion.serverpackets.SM_ITEM_USAGE_ANIMATION;
//import com.aionemu.gameserver.questEngine.handlers.HandlerResult;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
import com.aionemu.gameserver.services.QuestService;
//import com.aionemu.gameserver.utils.PacketSendUtility;
//import com.aionemu.gameserver.utils.ThreadPoolManager;


/**
 * @author FrozenKiller
 */

public class _24153TheEfficacyofFire extends QuestHandler {

    private final static int questId = 24153;
    private final static int[] mob_ids = {213730, 213788, 213789, 213790, 213791};

    public _24153TheEfficacyofFire() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(204787).addOnQuestStart(questId);
        qe.registerQuestNpc(204787).addOnTalkEvent(questId); // Chieftain Akagitan
        qe.registerQuestNpc(204784).addOnTalkEvent(questId); // Delris
        qe.registerQuestItem(182215462, questId);
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
            if (targetId == 204787) { //Chieftain Akagitan
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
        	switch (targetId) {
        		case 204784: { //Delris
        			switch (dialog) {
        				case QUEST_SELECT: {
        					return sendQuestDialog(env, 1352);
        				}
        				case SETPRO2: {
        					giveQuestItem(env, 182215462, 1); 
        					qs.setQuestVar(0);
        					updateQuestStatus(env);
        					return closeDialogWindow(env);
        				}
        				default:
        					break;
        			}
        		}
        		case 204787: { //Chieftain Akagitan
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
            if (targetId == 204787) { //Chieftain Akagitan
            	if (dialog == DialogAction.SELECT_QUEST_REWARD) {
            		return sendQuestDialog(env, 5);
            	} else {
            		return sendQuestEndDialog(env);
            	}
            }
        }
        return false;
    }
// TODO BOMB ?
//    @Override
//    public HandlerResult onItemUseEvent(final QuestEnv env, Item item) {
//        final Player player = env.getPlayer();
//        final int id = item.getItemTemplate().getTemplateId();
//        final int itemObjId = item.getObjectId();
//
//        if (id != 182215462) {
//            return HandlerResult.UNKNOWN;
//        }
//
//        final QuestState qs = player.getQuestStateList().getQuestState(questId);
//        if (qs == null || qs.getStatus() != QuestStatus.START) {
//            return HandlerResult.UNKNOWN;
//        }
//
//        PacketSendUtility.broadcastPacket(player, new SM_ITEM_USAGE_ANIMATION(player.getObjectId(), itemObjId, id, 1000, 0, 0), true);
//        ThreadPoolManager.getInstance().schedule(new Runnable() {
//        	@Override
//            public void run() {
//                PacketSendUtility.broadcastPacket(player, new SM_ITEM_USAGE_ANIMATION(player.getObjectId(), itemObjId, id, 0, 1, 0), true);
//            }
//        }, 1000);
//        return HandlerResult.SUCCESS;
//    }
    	

    @Override
    public boolean onKillEvent(QuestEnv env) {
        Player player = env.getPlayer();
        int targetId = env.getTargetId();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        if (qs == null || qs.getStatus() != QuestStatus.START) {
            return false;
        }
        
        int var = qs.getQuestVarById(0);
        int var1 = qs.getQuestVarById(1);
        int var2 = qs.getQuestVarById(2);
        int var3 = qs.getQuestVarById(3);
        int var4 = qs.getQuestVarById(4);

        if (targetId == 213730 && var == 0 && var < 1) { //Glaciont the Hardy
            qs.setQuestVarById(0, 1);
            updateQuestStatus(env);
        } else if (targetId == 213788 && var1 == 0 && var1 < 1) { //Frostfist
            qs.setQuestVarById(1, 1);
            updateQuestStatus(env);
        } else if (targetId == 213789 && var2 == 0 && var2 < 1) { //Iceback
            qs.setQuestVarById(2, 1);
            updateQuestStatus(env);
        } else if (targetId == 213790 && var3 == 0 && var3 < 1) { //Chillblow
            qs.setQuestVarById(3, 1);
            updateQuestStatus(env);
        } else if (targetId == 213791 && var4 == 0 && var4 < 1) { //Snowfury
            qs.setQuestVarById(4, 1);
            updateQuestStatus(env);
        }
        return false;
    }
}

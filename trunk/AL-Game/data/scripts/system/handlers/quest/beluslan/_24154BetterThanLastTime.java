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

import com.aionemu.gameserver.dataholders.DataManager;
import com.aionemu.gameserver.model.gameobjects.Item;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.network.aion.SystemMessageId;
import com.aionemu.gameserver.network.aion.serverpackets.SM_SYSTEM_MESSAGE;
import com.aionemu.gameserver.questEngine.handlers.HandlerResult;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
import com.aionemu.gameserver.services.teleport.TeleportService2;
import com.aionemu.gameserver.skillengine.SkillEngine;
import com.aionemu.gameserver.utils.PacketSendUtility;
import com.aionemu.gameserver.world.zone.ZoneName;

/**
 * @author FrozenKiller
 */

public class _24154BetterThanLastTime extends QuestHandler {

    private final static int questId = 24154;

    public _24154BetterThanLastTime() {
        super(questId);
    }

    @Override
    public void register() {
    	qe.registerQuestNpc(204774).addOnQuestStart(questId);
        qe.registerQuestNpc(204774).addOnTalkEvent(questId); //Tristran
        qe.registerQuestNpc(204809).addOnTalkEvent(questId); //Stua
        qe.registerQuestNpc(700359).addOnTalkEvent(questId); //Secret Port Entrance
        qe.registerQuestNpc(700349).addOnKillEvent(questId);
        qe.registerQuestItem(182215463, questId);
        qe.registerOnMovieEndQuest(250, questId);
        qe.registerOnDie(questId);
        qe.registerOnLogOut(questId);
        qe.registerOnEnterWorld(questId);
    }

    @Override
    public boolean onDialogEvent(final QuestEnv env) {
        final Player player = env.getPlayer();
        int targetId = env.getTargetId();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();
        
        if (qs == null || qs.getStatus() == QuestStatus.NONE) {
            if (targetId == 204774) { //Tristran
            	switch (dialog) {
        			case QUEST_SELECT: {
        				return sendQuestDialog(env, 4762);
        			}
        			case ASK_QUEST_ACCEPT: {
        				return sendQuestDialog(env, 4);
        			}
        			case QUEST_ACCEPT_1: {
        				playQuestMovie(env, 249);
        				return sendQuestStartDialog(env, 1);
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
            	switch (targetId) {
            		case 204809: { //Stua
            			switch (dialog) {
            				case QUEST_SELECT: {
            					return sendQuestDialog(env, 1352);
            				}
            				case SETPRO2: {
            					qs.setQuestVar(2);
            					updateQuestStatus(env);
            					giveQuestItem(env, 182215463, 1);
            					giveQuestItem(env, 185000006, 1);
            					SkillEngine.getInstance().applyEffectDirectly(267, player, player, (350 * 1000));
            					return closeDialogWindow(env);
            				}
						default:
							break;
            			}
            		}
            		case 700359: { //Secret Port Entrance
            			if (dialog == DialogAction.USE_OBJECT && var == 2) {
            				return playQuestMovie(env, 250);
            			}
            		}
            	}
        	} else if (qs.getStatus() == QuestStatus.REWARD) {
        		if (targetId == 204774) { // Tristran
    				if (dialog == DialogAction.USE_OBJECT) {
    					return sendQuestDialog(env, 10002);
    				} else {
    					return sendQuestEndDialog(env);
    				}
        		}
        	}
        return false;
    }

    @Override
    public boolean onMovieEndEvent(QuestEnv env, int movieId) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
       	if (qs != null && qs.getStatus() == QuestStatus.START) {
    		if (movieId == 250) {
    			TeleportService2.teleportTo(player, player.getWorldId(), player.getInstanceId(), 2452, 2474, 672.25f, (byte) 28);
                changeQuestStep(env, 2, 3, false); // 3
                return true;
    		}
        }
		return false;
    }

    @Override
    public boolean onKillEvent(QuestEnv env) {
        return defaultOnKillEvent(env, 700349, 3, 4); // 4
    }

    @Override
    public HandlerResult onItemUseEvent(final QuestEnv env, Item item) {
        Player player = env.getPlayer();
        if (item.getItemId() != 182215463) {
            return HandlerResult.UNKNOWN;
        }
        if (player.isInsideZone(ZoneName.get("DF3_ITEMUSEAREA_Q2058"))) {
            return HandlerResult.fromBoolean(useQuestItem(env, item, 4, 4, true, 251)); // reward
        }
        return HandlerResult.FAILED;
    }
    
    @Override
    public boolean onDieEvent(QuestEnv env) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        if (qs != null && qs.getStatus() == QuestStatus.START) {
            int var = qs.getQuestVarById(0);
            if (var == 2) {
                player.getEffectController().removeEffect(1865);
                qs.setQuestVar(1);
                updateQuestStatus(env);
                return true;
            }
        }
        return false;
    }

    @Override
    public boolean onLogOutEvent(QuestEnv env) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        if (qs != null && qs.getStatus() == QuestStatus.START) {
            int var = qs.getQuestVarById(0);
            if (var == 2) {
                player.getEffectController().removeEffect(1865);
                qs.setQuestVar(1);
                updateQuestStatus(env);
            }
        }
        return false;
    }

    @Override
    public boolean onEnterWorldEvent(QuestEnv env) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        if (qs != null && qs.getStatus() == QuestStatus.START) {
            if (player.getWorldId() != 320110000) {
                int var = qs.getQuestVarById(0);
                if (var == 3) {
                    qs.setQuestVar(1);
                    updateQuestStatus(env);
                    PacketSendUtility.sendPacket(player, new SM_SYSTEM_MESSAGE(SystemMessageId.QUEST_FAILED_$1,DataManager.QUEST_DATA.getQuestById(questId).getName()));
                    return true;
                }
            }
        }
        return false;
    }
}

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
package quest.sanctum;

import com.aionemu.gameserver.dataholders.DataManager;
import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.model.EmotionType;
import com.aionemu.gameserver.model.TeleportAnimation;
import com.aionemu.gameserver.model.gameobjects.Item;
import com.aionemu.gameserver.model.gameobjects.Npc;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.model.gameobjects.state.CreatureState;
import com.aionemu.gameserver.network.aion.SystemMessageId;
import com.aionemu.gameserver.network.aion.serverpackets.SM_EMOTION;
import com.aionemu.gameserver.network.aion.serverpackets.SM_SYSTEM_MESSAGE;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
import com.aionemu.gameserver.services.QuestService;
import com.aionemu.gameserver.services.instance.InstanceService;
import com.aionemu.gameserver.services.teleport.TeleportService2;
import com.aionemu.gameserver.utils.PacketSendUtility;
import com.aionemu.gameserver.world.WorldMapInstance;

/**
 * @author Mr. Poke
 * @modified Rolandas
 * @reworked vlog
 * @modified apozema
 * @rework FrozenKiller
 */
public class _1929ASliverofDarkness extends QuestHandler {

	private final static int questId = 1929;

	public _1929ASliverofDarkness() {
		super(questId);
	}

	@Override
	public void register() {
		int[] npcs = { 203752, 203852, 203164, 205110, 700240, 205111, 203701, 203711 };
		int[] stigmas = { 140000001, 140000002, 140000003, 140000004 };
		qe.registerOnLevelUp(questId);
		qe.registerOnMovieEndQuest(155, questId);
		qe.registerQuestNpc(212992).addOnKillEvent(questId);
		qe.registerOnEnterWorld(questId);
		qe.registerOnDie(questId);
		for (int npc : npcs) {
			qe.registerQuestNpc(npc).addOnTalkEvent(questId);
		}
		for (int stigma : stigmas) {
			qe.registerOnEquipItem(stigma, questId);
		}
	}

	@Override
	public boolean onDialogEvent(final QuestEnv env) {
		Player player = env.getPlayer();
		QuestState qs = player.getQuestStateList().getQuestState(questId);
		if (qs == null) {
			return false;
		}
		DialogAction dialog = env.getDialog();
		int targetId = env.getTargetId();
		int var = qs.getQuestVars().getQuestVars();

		if (qs.getStatus() == QuestStatus.START) {
			switch (targetId) {
				case 203752: { // Jucleas
					switch (dialog) {
						case QUEST_SELECT: {
							return sendQuestDialog(env, 1011);
						}
						case SETPRO1: {
							changeQuestStep(env, 0, 1, false); // 1
							return closeDialogWindow(env);
						}
						default:
							break;
					}
					break;
				}
				case 203852: { // Ludina
					switch (dialog) {
						case QUEST_SELECT: {
							return sendQuestDialog(env, 1352);
						}
						case SETPRO2: {
							TeleportService2.teleportTo(player, 210030000, 2325.1685f, 1808.1615f, 194.2152f, (byte)77, TeleportAnimation.BEAM_ANIMATION);
							changeQuestStep(env, 1, 2, false); // 2
							return closeDialogWindow(env);
						}
					default:
						break;
					}
					break;
				}
				case 203164: { // Morai
					switch (dialog) {
						case QUEST_SELECT: {
							if (var == 2) {
								return sendQuestDialog(env, 1693);
							} else if (var == 8) {
								return sendQuestDialog(env, 3057);
							}
						}
						case SETPRO3: {
							changeQuestStep(env, 2, 93, false); // 93
							WorldMapInstance newInstance = InstanceService.getNextAvailableInstance(310070000);
							InstanceService.registerPlayerWithInstance(newInstance, player);
							TeleportService2.teleportTo(player, 310070000, newInstance.getInstanceId(), 338f, 101f, 1191f, (byte)45, TeleportAnimation.BEAM_ANIMATION);
							return closeDialogWindow(env);
						}
						case SETPRO7: {
							changeQuestStep(env, 8, 9, false); // 9
							TeleportService2.teleportTo(player, 110010000, 1876.0033f, 1513.4586f, 812.6755f, (byte)45, TeleportAnimation.BEAM_ANIMATION);
							return closeDialogWindow(env);
						}
					default:
						break;
					}
					break;
				}	
				case 205110: { // Icaronix
					switch (dialog) {
						case QUEST_SELECT: {
							return sendQuestDialog(env, 2034);
						}
						case SETPRO4: {
							changeQuestStep(env, 93, 94, false); // 94
							player.setState(CreatureState.FLIGHT_TELEPORT);
							player.unsetState(CreatureState.ACTIVE);
							player.setFlightTeleportId(31001);
							PacketSendUtility.sendPacket(player, new SM_EMOTION(player, EmotionType.START_FLYTELEPORT, 31001, 0));
							return true;
						}
						default:
							break;
					}
					break;
				}
				case 700240: { // Icaronix's Box
					if (dialog == DialogAction.USE_OBJECT) {
						if (var == 94) {
							playQuestMovie(env, 155);
							return true;
						} else {
							return closeDialogWindow(env);
						}
					}
					break;
				}
				case 205111: { // Ecus
					switch (dialog) {
						case QUEST_SELECT: {
							if (var == 95) {
								return sendQuestDialog(env, 2375);
							} else if (var == 96) {
								if (isStigmaEquipped(env)) {
									return sendQuestDialog(env, 2716);
								} else {
									return sendQuestDialog(env, 1);
								}
							}
						}
						case SELECT_ACTION_2546: {
							if (var == 95) {
								if (giveQuestItem(env, getStoneId(player), 1)) {
									qs.setQuestVar(98);
									updateQuestStatus(env);
									return sendQuestDialog(env, 1);
								}	
							}
						}
						case SELECT_ACTION_2720: {
							if (var == 96) {
								Npc npc = (Npc) env.getVisibleObject();
								npc.getController().onDelete();
								QuestService.addNewSpawn(310070000, player.getInstanceId(), 212992, (float) 191.9, (float) 267.68, 1374, (byte) 0);
								qs.setQuestVar(97);
								updateQuestStatus(env);
								return closeDialogWindow(env);
							}
						}
						default:
							break;
					}
					break;
				}
				case 203701: { // Lavirintos
					switch (dialog) {
						case QUEST_SELECT: {
							return sendQuestDialog(env, 3398);
						}
						case SETPRO8: {
							return defaultCloseDialog(env, 9, 9, true, false); // reward
						}
						default:
							break;
					}
					break;
				}
			}
		} else if (qs.getStatus() == QuestStatus.REWARD) {
			if (targetId == 203711) { // Miriya
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
		if (movieId == 155) {
			QuestService.addNewSpawn(310070000, player.getInstanceId(), 205111, (float) 197.6, (float) 265.9, (float) 1374.0, (byte) 0);
			qs.setQuestVar(95);
			updateQuestStatus(env);
			return true;
		}
		return false;
	}

	@Override
	public boolean onEquipItemEvent(QuestEnv env, int itemId) {
		Player player = env.getPlayer();
		QuestState qs = player.getQuestStateList().getQuestState(questId);
		qs.setQuestVar(96);
		updateQuestStatus(env);
		return closeDialogWindow(env);
	}

	@Override
	public boolean onKillEvent(QuestEnv env) {
		Player player = env.getPlayer();
		QuestState qs = player.getQuestStateList().getQuestState(questId);
		if (qs.getStatus() == QuestStatus.START) {
			int var = qs.getQuestVarById(0);
			int var1 = qs.getQuestVarById(1);
			if (var == 33 && var1 == 1) {
				qs.setQuestVar(8);
				updateQuestStatus(env);
				TeleportService2.teleportTo(player, 210030000, 1, 2315.9f, 1800f, 195.2f);
				return true;
			}
		}
		return false;
	}

	@Override
	public boolean onDieEvent(QuestEnv env) {
		Player player = env.getPlayer();
		QuestState qs = player.getQuestStateList().getQuestState(questId);
		if (qs != null && qs.getStatus() == QuestStatus.START) {
			int var = qs.getQuestVars().getQuestVars();
			if (var >= 93 && var <= 98) {
				removeStigma(env);
				qs.setQuestVar(2);
				updateQuestStatus(env);
				PacketSendUtility.sendPacket(player, new SM_SYSTEM_MESSAGE(SystemMessageId.QUEST_FAILED_$1, DataManager.QUEST_DATA.getQuestById(questId)
						.getName()));
				return true;
			}
		}
		return false;
	}

	@Override
	public boolean onEnterWorldEvent(QuestEnv env) {
		Player player = env.getPlayer();
		QuestState qs = player.getQuestStateList().getQuestState(questId);
		if (qs != null && qs.getStatus() == QuestStatus.START) {
			int var = qs.getQuestVars().getQuestVars();
			if (player.getWorldId() != 310070000) {
				if (var >= 93 && var <= 98) {
					removeStigma(env);
					qs.setQuestVar(2);
					updateQuestStatus(env);
					PacketSendUtility.sendPacket(player, new SM_SYSTEM_MESSAGE(SystemMessageId.QUEST_FAILED_$1, DataManager.QUEST_DATA.getQuestById(questId)
							.getName()));
					return true;
				} else if (var == 8) {
					removeStigma(env);
					return true;
				}
			}
		}
		return false;
	}

	private int getStoneId(Player player) {
		switch (player.getCommonData().getPlayerClass()) {
			case GLADIATOR: {
				return 140000003; //Ferocious Strike III
			} case TEMPLAR: {
				return 140000003; //Ferocious Strike III
			} case ASSASSIN: {
				return 140000003; //Ferocious Strike III
			} case RANGER: {
				return 140000003; //Ferocious Strike III
			} case SORCERER: {
				return 140000002; //Flame Cage I
			} case SPIRIT_MASTER: {
				return 140000002; //Flame Cage I
			} case CLERIC: {
				return 140000002; //Flame Cage I
			} case CHANTER: {
				return 140000003; //Ferocious Strike III
			} case GUNNER: {
				return 140000004; //Hydro Eruption II
			} case BARD: {
				return 140000004; //Hydro Eruption II
			} case RIDER: {
				return 140000004; //Hydro Eruption II
			} default: {
				return 0;
			}
		}
	}

	private boolean isStigmaEquipped(QuestEnv env) {
		Player player = env.getPlayer();
		for (Item i : player.getEquipment().getEquippedItemsAllStigma()) {
			if (i.getItemId() == getStoneId(player)) {
				return true;
			}
		}
		return false;
	}

	private void removeStigma(QuestEnv env) {
		Player player = env.getPlayer();
		for (Item item : player.getEquipment().getEquippedItemsByItemId(getStoneId(player))) {
			player.getEquipment().unEquipItem(item.getObjectId(), 0);
		}
		removeQuestItem(env, getStoneId(player), 1);
	}

	@Override
	public boolean onLvlUpEvent(QuestEnv env) {
		return defaultOnLvlUpEvent(env);
	}
}

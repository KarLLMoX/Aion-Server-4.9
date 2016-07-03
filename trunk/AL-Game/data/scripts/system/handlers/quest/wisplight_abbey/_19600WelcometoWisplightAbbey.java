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
package quest.wisplight_abbey;

import com.aionemu.gameserver.model.DialogAction;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.questEngine.handlers.QuestHandler;
import com.aionemu.gameserver.questEngine.model.QuestEnv;
import com.aionemu.gameserver.questEngine.model.QuestState;
import com.aionemu.gameserver.questEngine.model.QuestStatus;
import com.aionemu.gameserver.world.zone.ZoneName;

/**
 * @author FrozenKiller
 */
public class _19600WelcometoWisplightAbbey extends QuestHandler {

    private final static int questId = 19600;

    public _19600WelcometoWisplightAbbey() {
        super(questId);
    }

    @Override
    public void register() {
        qe.registerQuestNpc(804651).addOnTalkEvent(questId);
        qe.registerOnMovieEndQuest(908, questId);
        qe.registerOnEnterZone(ZoneName.get("WISPLIGHT_ABBEY_130090000"), questId);
    }

	@Override
    public boolean onDialogEvent(final QuestEnv env) {
        final Player player = env.getPlayer();
        int targetId = env.getTargetId();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        DialogAction dialog = env.getDialog();

        if (targetId != 804651) {
            return false;
        }
        if (qs.getStatus() == QuestStatus.START) {
        	switch (dialog) {
       			case QUEST_SELECT: {
             		return sendQuestDialog(env, 1011);
       			}
       			case SETPRO1: {
       				playQuestMovie(env, 908);
       				return true;
       			}
       			default:
       				break;
       		}
        } else if (qs.getStatus() == QuestStatus.REWARD) {
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
        return false;
    }
	    
	@Override
    public boolean onMovieEndEvent(QuestEnv env, int movieId) {
        Player player = env.getPlayer();
        QuestState qs = player.getQuestStateList().getQuestState(questId);
        if (qs.getStatus() == QuestStatus.START && movieId == 908) {
			qs.setQuestVar(1);
			qs.setStatus(QuestStatus.REWARD);
            updateQuestStatus(env);
            return closeDialogWindow(env);
        }
        return false;
    }

    @Override
    public boolean onEnterZoneEvent(QuestEnv env, ZoneName zoneName) {
    	return defaultOnEnterZoneEvent(env, zoneName, ZoneName.get("WISPLIGHT_ABBEY_130090000"));
    }
}
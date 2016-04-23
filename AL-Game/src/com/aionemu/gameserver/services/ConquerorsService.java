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
package com.aionemu.gameserver.services;

import java.util.Iterator;
import java.util.concurrent.Future;

import javolution.util.FastMap;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import com.aionemu.gameserver.GameServer;
import com.aionemu.gameserver.configs.main.ConquerorProtectorConfig;
import com.aionemu.gameserver.model.Race;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.model.gameobjects.player.PlayerConquererDefenderData;
import com.aionemu.gameserver.network.aion.serverpackets.SM_SERIAL_KILLER;
import com.aionemu.gameserver.network.aion.serverpackets.SM_SYSTEM_MESSAGE;
import com.aionemu.gameserver.services.serialguards.SerialGuardDebuff;
import com.aionemu.gameserver.services.serialkillers.SerialKillerDebuff;
import com.aionemu.gameserver.utils.PacketSendUtility;
import com.aionemu.gameserver.utils.ThreadPoolManager;
import com.aionemu.gameserver.world.World;
import com.aionemu.gameserver.world.knownlist.Visitor;

/**
 * @author Kill3r
 * @modify Elo
 */
public class ConquerorsService {

    private FastMap<Integer, MapTypes> usedWorldMaps = new FastMap<Integer, MapTypes>();
    private FastMap<Integer, PlayerConquererDefenderData> players = new FastMap<Integer, PlayerConquererDefenderData>();
    private FastMap<Integer, Future<?>> pduration = new FastMap<Integer, Future<?>>();
    private FastMap<Integer, Future<?>> cduration = new FastMap<Integer, Future<?>>();
    // PlayerObjId , IsOnTheirMap , KillCount
    //private FastMap<Integer, FastMap<Boolean, Integer>> getCorrectKillCForMap = new FastMap<Integer, FastMap<Boolean, Integer>>();
    private SerialGuardDebuff sGbuff;
    private SerialKillerDebuff sKbuff;
    private Future<?> pbuffLvl1; //Protectors Buffs
    private Future<?> pbuffLvl2;
    private Future<?> pbuffLvl3;
    private Future<?> cbuffLvl1; // Conquerors buffs
    private Future<?> cbuffLvl2;
    private Future<?> cbuffLvl3;

    private static final Logger log = LoggerFactory.getLogger(SerialKillerService.class);

    public enum MapTypes{
        ELYOS,
        ASMODIANS;
    }

    public enum BuffState{
        UPDATE_REQUIRED,
        FINE;
    }

    public void initConquerorPvPSystem(){
        if(!ConquerorProtectorConfig.ENABLE_GUARDIAN_PVP){
            return;
        }

        GameServer.log.info("[ConquerorsService] Initializing Conqueror/Protector Buff System...");

        sGbuff = new SerialGuardDebuff();
        sKbuff = new SerialKillerDebuff();
        if(!ConquerorProtectorConfig.IGNORE_MAPS){
            for (String worldids : ConquerorProtectorConfig.ENABLED_MAPS_GUARDIAN.split(",")){
                if(worldids.equals("")){
                    break;
                }

                int worldId = Integer.parseInt(worldids); // world Id from Config
                int type = Integer.parseInt(String.valueOf(worldids.charAt(1))); // 220000000 , second number from worldId , to get Wat Type of World

                if (!(type == 1 || type == 2)){
                    type = 1;
                    log.info("[ConquerorsService] [CONQUEROR NOTE] Please Verify the Map Id's Given in conqueror.properties!!");
                }

                MapTypes mType = type == 1 ? MapTypes.ELYOS : MapTypes.ASMODIANS;

                usedWorldMaps.put(worldId, mType);

            }
        }
    }

    public boolean isOnConquerorPvPMap(int worldid){
        if(ConquerorProtectorConfig.IGNORE_MAPS){
            return true;
        }
        return usedWorldMaps.containsKey(worldid);
    }
    
    public void setKills(Player player, int kills)
    {
        if (isOnEnemyMap(player))
            player.getConquerorDefenderData().setKillCountAsConquerer(kills);
        else
        	player.getConquerorDefenderData().setKillCountAsProtector(kills);
    }
    
    public int getKills(Player player)
    {
        if (isOnEnemyMap(player))
            return player.getConquerorDefenderData().getKillCountAsConquerer();
        else
        	return player.getConquerorDefenderData().getKillCountAsProtector();
    }
    
    public void addKills(Player player, int kills)
    {
        if (isOnEnemyMap(player))
            player.getConquerorDefenderData().setKillCountAsConquerer(player.getConquerorDefenderData().getKillCountAsConquerer() + kills);
        else
        	player.getConquerorDefenderData().setKillCountAsProtector(player.getConquerorDefenderData().getKillCountAsProtector() + kills);
    }    

    public boolean isOnEnemyMap(Player player){
        if (ConquerorProtectorConfig.IGNORE_MAPS){
            String worldidsAsString = String.valueOf(player.getWorldId());
            //int worldId = Integer.parseInt(worldidsAsString);
            int type = worldidsAsString.charAt(1);

            if (!(type == 1 || type == 2)){
                type = 3;
                log.info("[ConquerorsService] [CONQUEROR NOTE] Please Verify the Map Id's Given in conqueror.properties OR In an Instance?");
            }

            MapTypes mType = type == 1 ? MapTypes.ELYOS : MapTypes.ASMODIANS;
            MapTypes tt = player.getRace().equals(Race.ASMODIANS) ? MapTypes.ASMODIANS : MapTypes.ELYOS;

            return !mType.equals(tt);
        }
        if(usedWorldMaps.containsKey(player.getWorldId()))
        {
            MapTypes mType = player.getRace().equals(Race.ASMODIANS) ? MapTypes.ASMODIANS : MapTypes.ELYOS;
            return !usedWorldMaps.get(player.getWorldId()).equals(mType);
        }
        return false;
    }

    public void onKill(Player player, Player diedPlayer){
        
    	PlayerConquererDefenderData pcdd_killer = player.getConquerorDefenderData();
    	//PlayerConquererDefenderData pcdd_died = diedPlayer.getConquerorDefenderData();
    	// Add new player if he's not in players and give first buff lvl
    	if (!players.containsKey(player.getObjectId()))
        {
            players.put(player.getObjectId(), pcdd_killer);
            msgLog("Added New Player : " + player.getName() + " to playerList of PvP.");
            msgLog(player.getName() + "'s Buff Lvl's " + players.get(player.getObjectId()) + " {ProtectorBuffLvl = ConquerorBuffLvl}.");
        }
        //check if player got enough kills for next bufflevel and update it in case.
    	else
        {
            updateBuffLvls(player);
        }

        msgLog("Current Kill Count : " + getKills(player));
        addKills(player,1);
        msgLog("New Kill Count : " + getKills(player));

        //set ActualBuffLevel
        checkKillCountAndUpdateBuffLvls(player);
        //set timer for buff deactivate and start buff
        addToKillAndStartCounting(player);
        //only for Msg
        checkIfTargetIsHighestRankIntruder(player, diedPlayer);
    }

    public void updateTagPacketToNearby(final Player player){ // NEED TO RE-CHECK
        World.getInstance().getWorldMap(player.getWorldId()).getWorldMapInstanceById(player.getInstanceId()).doOnAllPlayers(new Visitor<Player>() {
            @Override
            public void visit(Player p) {
                if (!player.getRace().equals(p.getRace()) && player != p) {
                    if (isOnEnemyMap(player)){ //conquerer
                    	PacketSendUtility.sendPacket(p, new SM_SERIAL_KILLER(player, false, false, player.getConquerorDefenderData().getConquerorBuffId()));
                    }
                    if (!isOnEnemyMap(player)){ //protector
                        PacketSendUtility.sendPacket(p, new SM_SERIAL_KILLER(player, true, false, player.getConquerorDefenderData().getProtectorBuffId()));
                    }
                }
            }
        });
    }

    public void sendPacketToEveryoneInMap(Player player, Player diedPlayer){
        Iterator<Player> ita = World.getInstance().getPlayersIterator();
        while(ita.hasNext()){
            Player p1 = ita.next();
            if(player.getWorldId() == p1.getWorldId()){
                if(diedPlayer.getRace() == Race.ELYOS && !isOnEnemyMap(player)){
                    // Hero of Asmodian %0 killed the Divinely Punished Intruder %1.
                    PacketSendUtility.sendPacket(p1, new SM_SYSTEM_MESSAGE(1400141, player.getName(), diedPlayer.getName()));
                }else if(diedPlayer.getRace() == Race.ASMODIANS && !isOnEnemyMap(player)){
                    // Hero of Elyos %0 killed the Divinely Punished Intruder %1.
                    PacketSendUtility.sendPacket(p1, new SM_SYSTEM_MESSAGE(1400142, player.getName(), diedPlayer.getName()));
                }
            }
        }
    }

    public void checkIfTargetIsHighestRankIntruder(Player player, Player diedPlayer){
        if (diedPlayer.getConquerorDefenderData().getProtectorBuffId() == 3 || diedPlayer.getConquerorDefenderData().getConquerorBuffId() == 3){
            sendPacketToEveryoneInMap(player, diedPlayer);
        }
    }

    public void checkKillCountAndUpdateBuffLvls(Player player){
        
    	PlayerConquererDefenderData pcdd = player.getConquerorDefenderData();
    	int kills = getKills(player);
    	boolean isProtector = pcdd.isProtector();
        int buffLevel = isProtector ? pcdd.getProtectorBuffId() : pcdd.getConquerorBuffId();
        int oldBuffLevel = buffLevel;
        BuffState buffUpdate = BuffState.FINE;

        if (isProtector)
        {
            if ((kills >= ConquerorProtectorConfig.PROTECTOR_LVL1_KILLCOUNT && kills < ConquerorProtectorConfig.PROTECTOR_LVL2_KILLCOUNT))            
                buffLevel = 1; // sets Protector lvl 1 buff            
            else if((kills >= ConquerorProtectorConfig.PROTECTOR_LVL2_KILLCOUNT && kills < ConquerorProtectorConfig.PROTECTOR_LVL3_KILLCOUNT))
            	buffLevel = 2; // sets Protector lvl 2 Buff            
            else if(kills >= ConquerorProtectorConfig.PROTECTOR_LVL3_KILLCOUNT)
            	buffLevel = 3; // sets Protector lvl 3 Buff
            if (buffLevel != oldBuffLevel)    
            	buffUpdate = BuffState.UPDATE_REQUIRED;
        }
        else 
        {
            if ((kills >= ConquerorProtectorConfig.CONQUEROR_LVL1_KILLCOUNT && kills < ConquerorProtectorConfig.CONQUEROR_LVL2_KILLCOUNT))
            	buffLevel = 1; // sets Conquerur lvl 1 buff  
            else if((kills >= ConquerorProtectorConfig.CONQUEROR_LVL2_KILLCOUNT && kills < ConquerorProtectorConfig.CONQUEROR_LVL3_KILLCOUNT))
            	buffLevel = 2; //sets Conquerur lvl 2 buff
            else if(kills >= ConquerorProtectorConfig.CONQUEROR_LVL3_KILLCOUNT)
            	buffLevel = 3; //sets Conquerur lvl 3 buff
            
            if (buffLevel != oldBuffLevel)
            	buffUpdate = BuffState.UPDATE_REQUIRED;           
        }

        if (buffUpdate == BuffState.UPDATE_REQUIRED) 
        {
            msgLog("Setting ProtectorBuffLvl : " + pcdd.getProtectorBuffId() + " , Setting ConquerorBuffLvl : " + pcdd.getConquerorBuffId() + " Player " + player.getName());
            msgLog("KILL COUNT : " + getKills(player) + " on Enemy Map : " + isOnEnemyMap(player)+" is Protector: "+pcdd.isProtector());
            setBuffLvls(player, pcdd.getProtectorBuffId(), pcdd.getConquerorBuffId());
        }
    }

    public void onLogOut(Player player){
        if(!ConquerorProtectorConfig.ENABLE_GUARDIAN_PVP){
            return;
        }
        //hmm.. don't know what this should have a sense ... 
    }

/*    public FastMap<Integer, Integer> getPlayerProtectorConquerorLvl(Player player){
        FastMap<Integer, Integer> bufflvls = new FastMap<Integer, Integer>();
        bufflvls.put(player.getProtectorBuffId(), player.getConquerorBuffId());
        return bufflvls;
    }
*/
    public void onLogin(Player player){
    	PlayerConquererDefenderData pcdd = player.getConquerorDefenderData();
    	checkKillCountAndUpdateBuffLvls(player);

        sendPacket(player, pcdd.getProtectorBuffId(), pcdd.getConquerorBuffId());
        msgLog("==You're Last Activity==");
        msgLog("Kills : " + getKills(player));
        msgLog("Buffs Protector : " + pcdd.getProtectorBuffId());
        msgLog("Buffs Conqueror : " + pcdd.getConquerorBuffId());
    }

    public void onEnterWorld(Player player){
        if (!ConquerorProtectorConfig.ENABLE_GUARDIAN_PVP){
            return;
        }

        onLogin(player);
        updateTagPacketToNearby(player);
    }

    public void addToKillAndStartCounting(final Player player){
    	final PlayerConquererDefenderData pcdd = player.getConquerorDefenderData();
    	//Protector
    	switch (pcdd.getProtectorBuffId()){
            case 0:
                break;
            case 1:
                if (pbuffLvl1 != null)
                    pbuffLvl1.cancel(true);// After a kill is taken, the time resets and start again.
                
                pbuffLvl1 = ThreadPoolManager.getInstance().schedule(new Runnable() {
                    @Override
                    public void run() {
                    	
                        if(pcdd.getProtectorBuffId() != 0 && pcdd.getProtectorBuffId() == 1){
                            setBuffLvls(player, pcdd.getProtectorBuffId() - 1, pcdd.getConquerorBuffId());
                            setKills(player, 0); // Player is not PvPing, so times up, decreasing BuffLvl and reseting Kill to killCount of decreased lvl
                        }
                    }
                }, ConquerorProtectorConfig.DURATION_PBUFF1 * 1000 * 60); // Need to find retail time , for how long it takes to wear off the buff
                
                // kill old timers
                if (pbuffLvl2 != null){
                    pbuffLvl2.cancel(true);
                }
                if(pbuffLvl3 != null){
                    pbuffLvl3.cancel(true);
                }
                pduration.put(player.getObjectId(), pbuffLvl1);
                break;
            case 2:
                if (pbuffLvl2 != null){
                    pbuffLvl2.cancel(true); // After a kill is taken, the time resets and start again.
                }
                pbuffLvl2 = ThreadPoolManager.getInstance().schedule(new Runnable() {
                    @Override
                    public void run() {
                        if(pcdd.getProtectorBuffId() != 0 && pcdd.getProtectorBuffId() == 2){
                            setBuffLvls(player, pcdd.getProtectorBuffId() - 1, pcdd.getConquerorBuffId());
                            setKills(player, ConquerorProtectorConfig.PROTECTOR_LVL2_KILLCOUNT);  // Player is not PvPing, so times up, decreasing BuffLvl and reseting Kill to killCount of decreased lvl
                        }
                    }
                }, ConquerorProtectorConfig.DURATION_PBUFF2 * 1000 * 60);
                if (pbuffLvl1 != null){
                    pbuffLvl1.cancel(true);
                }
                pduration.put(player.getObjectId(), pbuffLvl2);
                break;
            case 3:
                if (pbuffLvl3 != null){
                    pbuffLvl3.cancel(true); // After a kill is taken, the time resets and start again.
                }
                pbuffLvl3 = ThreadPoolManager.getInstance().schedule(new Runnable() {
                    @Override
                    public void run() {
                        if(pcdd.getProtectorBuffId() != 0 && pcdd.getProtectorBuffId() == 3){
                            setBuffLvls(player, pcdd.getProtectorBuffId() - 1, pcdd.getConquerorBuffId());
                            setKills(player, ConquerorProtectorConfig.PROTECTOR_LVL3_KILLCOUNT);  // Player is not PvPing, so times up, decreasing BuffLvl and reseting Kill to killCount of decreased lvl
                        }
                    }
                }, ConquerorProtectorConfig.DURATION_PBUFF3 * 1000 * 60);
                if (pbuffLvl2 != null){
                    pbuffLvl2.cancel(true);
                }
                pduration.put(player.getObjectId(), pbuffLvl3);
                break;
        }

        switch (pcdd.getConquerorBuffId()){
            case 0:
                break;
            case 1:
                if(cbuffLvl1 != null){
                    cbuffLvl1.cancel(true);
                }
                cbuffLvl1 = ThreadPoolManager.getInstance().schedule(new Runnable() {
                    @Override
                    public void run() {
                        if(pcdd.getConquerorBuffId() != 0 && pcdd.getConquerorBuffId() == 1){
                            setBuffLvls(player, pcdd.getProtectorBuffId(), pcdd.getConquerorBuffId() - 1);
                            setKills(player,0); // Player is not PvPing, so times up, decreasing BuffLvl and reseting Kill to killCount of decreased lvl
                        }
                    }
                }, ConquerorProtectorConfig.DURATION_CBUFF1 * 1000 * 60); // Need to find retail time , for how long it takes to wear off the buff
                if (cbuffLvl2 != null){
                    cbuffLvl2.cancel(true);
                }
                if(cbuffLvl3 != null){
                    cbuffLvl3.cancel(true);
                }
                cduration.put(player.getObjectId(), cbuffLvl1);
                break;
            case 2:
                if (cbuffLvl2 != null){
                    cbuffLvl2.cancel(true);
                }
                cbuffLvl2 = ThreadPoolManager.getInstance().schedule(new Runnable() {
                    @Override
                    public void run() {
                        if(pcdd.getConquerorBuffId() != 0 && pcdd.getConquerorBuffId() == 1){
                            setBuffLvls(player, pcdd.getProtectorBuffId(), pcdd.getConquerorBuffId() - 1);
                            setKills(player, ConquerorProtectorConfig.CONQUEROR_LVL2_KILLCOUNT); // Player is not PvPing, so times up, decreasing BuffLvl and reseting Kill to killCount of decreased lvl
                        }
                    }
                }, ConquerorProtectorConfig.DURATION_CBUFF2 * 1000 * 60);
                if (cbuffLvl1 != null){
                    cbuffLvl1.cancel(true);
                }
                cduration.put(player.getObjectId(), cbuffLvl2);
                break;
            case 3:
                if (cbuffLvl3 != null){
                    cbuffLvl3.cancel(true);
                }
                cbuffLvl3 = ThreadPoolManager.getInstance().schedule(new Runnable() {
                    @Override
                    public void run() {
                        if(pcdd.getConquerorBuffId() != 0 && pcdd.getConquerorBuffId() == 1){
                            setBuffLvls(player, pcdd.getProtectorBuffId(), pcdd.getConquerorBuffId() - 1);
                            setKills(player, ConquerorProtectorConfig.CONQUEROR_LVL3_KILLCOUNT); // Player is not PvPing, so times up, decreasing BuffLvl and reseting Kill to killCount of decreased lvl
                        }
                    }
                }, ConquerorProtectorConfig.DURATION_CBUFF3 * 1000 * 60);
                if (cbuffLvl2 != null){
                    cbuffLvl2.cancel(true);
                }
                cduration.put(player.getObjectId(), cbuffLvl3);
                break;
        }
    }

    public void setBuffLvls(Player player, int ProtectorBuffLvl, int ConquerorBuffLvl) {
        player.getConquerorDefenderData().setConquerorBuffId(ConquerorBuffLvl);
        player.getConquerorDefenderData().setProtectorBuffId(ProtectorBuffLvl);
        updateBuffLvls(player);
        updateTagPacketToNearby(player);
        msgLog("Updated Player " + player.getName() + "'s Buffs to Protector : " + ProtectorBuffLvl + " || Conquerors : " + ConquerorBuffLvl);
    }

    public void updateBuffLvls(Player player){
    	PlayerConquererDefenderData pcdd = player.getConquerorDefenderData();
        sendPacket(player, pcdd.getProtectorBuffId(), pcdd.getConquerorBuffId());
        sGbuff.applyEffect(player, pcdd.getProtectorBuffId());
        sKbuff.applyEffect(player, pcdd.getConquerorBuffId());
        player.getGameStats().updateStatsAndSpeedVisually();
    }

    public void sendPacket(Player player, int ProtectorLvl, int ConquerorLvl){
        //ShowBuff ? Packet for Conqueror
        PacketSendUtility.sendPacket(player, new SM_SERIAL_KILLER(player, false, true, ConquerorLvl));
        //ShowBuff ? Packet for Protector
        PacketSendUtility.sendPacket(player, new SM_SERIAL_KILLER(player, true, true, ProtectorLvl));
    }

    public void msgLog(String msg){
        if (ConquerorProtectorConfig.ENABLE_CONQUEROR_DEBUGMODE){
            log.info("[ConquerorsService] " + msg);
        }
    }

    public void sendNormalLogMsg(String msg){
        log.info("[ConquerorsService] " + msg);
    }


    public static ConquerorsService getInstance() {
        return ConquerorsService.SingletonHolder.instance;
    }

    private static class SingletonHolder {

        protected static final ConquerorsService instance = new ConquerorsService();
    }

}

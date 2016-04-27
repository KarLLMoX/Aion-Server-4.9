/**
 * 
 */
package com.aionemu.gameserver.services.territory;

import java.util.ArrayList;
import java.util.Collection;
import java.util.Iterator;
import java.util.TreeMap;

import javolution.util.FastMap;

import com.aionemu.commons.database.dao.DAOManager;
import com.aionemu.gameserver.GameServer;
import com.aionemu.gameserver.dao.LegionDAO;
import com.aionemu.gameserver.model.gameobjects.player.Player;
import com.aionemu.gameserver.model.team.legion.Legion;
import com.aionemu.gameserver.model.team.legion.LegionTerritory;
import com.aionemu.gameserver.network.aion.serverpackets.SM_CONQUEROR_PROTECTOR;
import com.aionemu.gameserver.network.aion.serverpackets.SM_LEGION_INFO;
import com.aionemu.gameserver.network.aion.serverpackets.SM_TERRITORY_LIST;
import com.aionemu.gameserver.services.LegionService;
import com.aionemu.gameserver.utils.PacketSendUtility;
import com.aionemu.gameserver.world.World;

/**
 * @author CoolyT
 *
 */
public class TerritoryService 
{
    private TerritoryBuff territoryBuff;
    private FastMap<Integer, TerritoryBuff> buffs = new FastMap<Integer,TerritoryBuff>();
    private TreeMap<Integer,LegionTerritory> territories = new TreeMap<Integer,LegionTerritory>(); 
    
    public void init()
    {
    	LegionService ls = LegionService.getInstance();
    	Collection<Legion> legions = new ArrayList<Legion>();
    	int counter = 0;
    	
    	//Fill Map with dummies, because client wants 6 entries .. 
    	for (int i = 1; i <= 6; i++)
    	{
    		territories.put(i, new LegionTerritory(i));
    	}    	
    	//Fill the LegionList
    	for (Integer legionId : DAOManager.getDAO(LegionDAO.class).getLegionIdswithTerritories())
    	{
    		legions.add(ls.getLegion(legionId));
    	}
    	//replace the dummies with realdata, if a legion owns an territory    	
    	for (Legion legion : legions)
    	{
			LegionTerritory territory = legion.getTerritory();
			//Because .replace is only supported @ java 1.8 or higher, we use the old variant :)
			territories.remove(territory.getId());
			territories.put(territory.getId(),territory);
			counter++;
    	}
    	GameServer.log.info("[TerritoryService] "+counter +" Legions owns a Territory..");
    }
    
    public void onEnterWorld(Player player)
    {
    	PacketSendUtility.sendPacket(player, new SM_TERRITORY_LIST(territories.values()));
    	//PacketSendUtility.sendPacket(player, new SM_STONESPEAR_SIEGE());
    }
    
    public void onEnterTerritory(Player player)
    {
    	if (player.getLegion() == null || player.getLegion().getTerritory().getId() == 0)
    		return;
    	
    	territoryBuff = new TerritoryBuff();
    	territoryBuff.applyEffect(player);
    	buffs.put(player.getObjectId(), territoryBuff);
    }
    
    public void onLeaveTerritory(Player player)
    {
    	if (player.getLegion() == null || player.getLegion().getTerritory().getId() == 0)
    		return;
    	
    	if (buffs.containsKey(player.getObjectId()))
    	{
    		buffs.get(player.getObjectId()).endEffect(player);
    		buffs.remove(player.getObjectId());
    	}
    }
    
	public void scanForIntruders(Player player)
    {
    	Collection<Player> players = new ArrayList<Player>();
    	Iterator<Player> playerIt = World.getInstance().getPlayersIterator();
        while(playerIt.hasNext())
        {
            Player enemy = playerIt.next();
            if (player.getWorldId() == enemy.getWorldId()&& player.getRace() != enemy.getRace()) 
            	players.add(enemy);
        }
        PacketSendUtility.sendPacket(player, new SM_CONQUEROR_PROTECTOR(players, false));
    }
	
	public void onConquerTerritory(Legion legion, int id)
	{
		if (legion.ownsTerretory())
		{
			onLooseTerritory(legion);
		}
		
		LegionTerritory territory = new LegionTerritory(id);
		territory.setLegionId(legion.getLegionId());
		territory.setLegionName(legion.getLegionName());
		legion.setTerritory(territory);
		
		territories.remove(id);
		territories.put(id,territory);
		PacketSendUtility.broadcastPacketToLegion(legion , new SM_LEGION_INFO(legion));
		broadcastTerritoryList(territories);
	}
	
	public void onLooseTerritory(Legion legion)
	{				
		int oldTerritoryId = legion.getTerritory().getId();
		legion.setTerritory(new LegionTerritory());
						
		if (oldTerritoryId == 0)
			GameServer.log.info("TerritoryId is 0 !!!");
		
		LegionTerritory territory = new LegionTerritory(oldTerritoryId);
		territories.remove(oldTerritoryId);
		territories.put(oldTerritoryId, territory);		

		TreeMap<Integer,LegionTerritory> lostTerr = new TreeMap<Integer,LegionTerritory>();
		lostTerr.put(oldTerritoryId, territory);
		broadcastTerritoryList(lostTerr);
		legion.getTerritory().setTerritoryId(0);
		PacketSendUtility.broadcastPacketToLegion(legion ,new SM_LEGION_INFO(legion));
	}
	
	public void broadcastTerritoryList(TreeMap<Integer,LegionTerritory> terr)
	{
		Collection<Player> players = World.getInstance().getAllPlayers();
		for (Player player : players)
		{
			if (!player.isOnline())
				return;
			
			PacketSendUtility.sendPacket(player, new SM_TERRITORY_LIST(terr.values()));
		}
	}
	
	public Collection<LegionTerritory> getTerritories()
	{
		return territories.values();
	}
    
    public static TerritoryService getInstance()
    {
        return TerritoryService.SingletonHolder.instance;
    }

    private static class SingletonHolder
    {
        protected static final TerritoryService instance = new TerritoryService();
    }
}

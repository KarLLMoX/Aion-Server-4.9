/**
 * 
 */
package com.aionemu.gameserver.model.gameobjects.player;

/**
 * @author CoolyT
 *
 */
public class PlayerConquererDefenderData 
{
    private boolean isProtector = false;
    private boolean isIntruder = false;
    private int ProtectorBuffId = 0;
    private int ConquerorBuffId = 0;
    private int killCountAsConquerer = 0;
    private int killCountAsProtector = 0;
	
    public boolean isProtector() {
		return isProtector;
	}
	private void setProtector(boolean isProtector) {
		this.isProtector = isProtector;
	}
	public boolean isIntruder() {
		return isIntruder;
	}
	private void setIntruder(boolean isIntruder) {
		this.isIntruder = isIntruder;
	}
	public int getProtectorBuffId() {
		return ProtectorBuffId;
	}
	public void setProtectorBuffId(int protectorBuffId) {
		ProtectorBuffId = protectorBuffId;
	}
	public int getConquerorBuffId() {
		return ConquerorBuffId;
	}
	public void setConquerorBuffId(int conquerorBuffId) {
		ConquerorBuffId = conquerorBuffId;
	}
	public int getKillCountAsConquerer() {
		return killCountAsConquerer;
	}
	public void setKillCountAsConquerer(int killCountAsConquerer) {
		setProtector(false);
		setIntruder(true);
		this.killCountAsConquerer = killCountAsConquerer;
	}
	public int getKillCountAsProtector() {
		return killCountAsProtector;
	}
	public void setKillCountAsProtector(int killCountAsProtector) {
		setProtector(true);
		setIntruder(false);
		this.killCountAsProtector = killCountAsProtector;
	}

}

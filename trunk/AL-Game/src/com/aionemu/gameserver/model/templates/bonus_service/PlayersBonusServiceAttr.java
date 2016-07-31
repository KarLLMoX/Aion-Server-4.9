package com.aionemu.gameserver.model.templates.bonus_service;

import javax.xml.bind.annotation.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Created by Ace on 31/07/2016.
 */

@XmlAccessorType(XmlAccessType.FIELD)
@XmlType(name = "PlayersBonusServiceAttr", propOrder = {"playersBonusAttr"})
public class PlayersBonusServiceAttr
{
    @XmlElement(name = "apply_bonus")
    protected List<PlayersBonusPenaltyAttr> playersBonusAttr;

    @XmlAttribute(name = "buff_id", required = true)
    protected int buffId;

    public List<PlayersBonusPenaltyAttr> getPenaltyAttr() {
        if (playersBonusAttr == null) {
            playersBonusAttr = new ArrayList<PlayersBonusPenaltyAttr>();
        }
        return playersBonusAttr;
    }

    public int getBuffId() {
        return buffId;
    }

    public void setBuffId(int value) {
        buffId = value;
    }
}

